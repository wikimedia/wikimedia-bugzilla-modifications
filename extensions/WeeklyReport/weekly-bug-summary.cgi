#!/usr/bin/perl -wT

# its stolen from somewhere but was mostly re-written by Dirk Mueller <mueller@kde.org>, 08/2006
# templatized by Matt Rogers <mattr@kde.org>, 12/2007

# Upstream: https://projects.kde.org/projects/websites/bugs-kde-org/repository/revisions/kde/entry/weekly-bug-summary.cgi
use strict;
use lib ".";

use Bugzilla;
use Bugzilla::Constants;
use Bugzilla::Util;
use Bugzilla::Error;
use Bugzilla::Field;

sub total_bugs_in_bugzilla()
{
    my $dbh = Bugzilla->dbh;

    # figure out total bugs
    my (@totalbugs) = $dbh->selectrow_array(
        "SELECT count(bugs.bug_id) FROM bugs WHERE bugs.bug_severity != 'enhancement' AND
         ( bugs.bug_status = 'NEW' or bugs.bug_status = 'ASSIGNED' or
         bugs.bug_status = 'REOPENED' or bugs.bug_status = 'UNCONFIRMED')"
    );

    # figure out total number of wishes
    my (@totalwishes) = $dbh->selectrow_array (
        "SELECT count(bugs.bug_id) FROM bugs WHERE bugs.bug_severity = 'enhancement' AND
         ( bugs.bug_status = 'NEW' or bugs.bug_status = 'ASSIGNED' or
          bugs.bug_status = 'REOPENED' or bugs.bug_status = 'UNCONFIRMED')"
    );

    return ($totalbugs[0], $totalwishes[0]);
}

sub bugs_opened()
{
    my($product, $days) = @_;

    my $sqlproduct = "";
    $sqlproduct = "AND bugs.product_id=$product"
        if(defined $product and $product ne "%");

    my ($count) = Bugzilla->dbh->selectrow_array(
        "SELECT count(bugs.bug_id) FROM bugs
         WHERE creation_ts >= from_days(to_days(NOW())-?)
         $sqlproduct AND bugs.bug_severity != 'enhancement'", undef, ($days)
    );

    return $count;
}

sub wishes_opened()
{
    my($product, $days) = @_;

    my $sqlproduct = "";
    $sqlproduct = "AND bugs.product_id=" . Bugzilla->dbh->quote($product)
        if(defined $product and $product ne "%");

    my ($count) = Bugzilla->dbh->selectrow_array(
        "SELECT count(bugs.bug_id) FROM bugs
         WHERE creation_ts >= from_days(to_days(NOW())-?)
         $sqlproduct AND bugs.bug_severity = 'enhancement'", undef, ($days)
    );

    return $count;
}

sub bugs_closed()
{
    my($product, $days) = @_;
    my $query = "";
    my $sqlproduct = "";
    $sqlproduct = "AND bugs.product_id=" . Bugzilla->dbh->quote($product)
        if(defined $product and $product ne "%");

    my ($count) = Bugzilla->dbh->selectrow_array("
select
    count(distinct bugs.bug_id)
from
    bugs, bugs_activity
where
    bugs.bug_severity != 'enhancement' AND
    (bugs_activity.added='RESOLVED' or bugs_activity.added='CLOSED' or
     bugs_activity.added='NEEDSINFO')
and
    bugs_activity.bug_when >= FROM_DAYS(TO_DAYS(NOW())-?)
and
    bugs.bug_id = bugs_activity.bug_id
    $sqlproduct
    ", undef, ($days));

    return($count);
}

sub wishes_closed()
{
    my($product, $days) = @_;
    my $query = "";
    my $sqlproduct = "";
    $sqlproduct = "AND bugs.product_id=" . Bugzilla->dbh->quote($product)
        if(defined $product and $product ne "%");

    # We are going to build a long SQL query.
    my ($count) = Bugzilla->dbh->selectrow_array("
select
    count(distinct bugs.bug_id)
from
    bugs, bugs_activity
where
    bugs.bug_severity = 'enhancement' AND
    (bugs_activity.added='RESOLVED' or bugs_activity.added='CLOSED' or
     bugs_activity.added='NEEDSINFO')
and
    bugs_activity.bug_when >= FROM_DAYS(TO_DAYS(NOW())-?)
and
    bugs.bug_id = bugs_activity.bug_id
    $sqlproduct
    ", undef, ($days));

    return($count);
}

sub open_wishes()
{
    my($product) = @_;

    my $sqlproduct = "";
    $sqlproduct = "AND bugs.product_id=" . Bugzilla->dbh->quote($product)
        if(defined $product and $product ne "%");

    # We are going to build a long SQL query.
    my ($count) = Bugzilla->dbh->selectrow_array("
SELECT
    count(bugs.bug_id)
FROM bugs
WHERE bugs.bug_severity = 'enhancement' AND
      (bugs.bug_status = 'NEW' or bugs.bug_status = 'ASSIGNED' or
       bugs.bug_status = 'REOPENED' or bugs.bug_status = 'UNCONFIRMED')
$sqlproduct");

    return $count;
}


# $format can be HTML or XML
sub print_product_bug_lists() {
    my($number, $days, $format, $fh) = @_;

    my $query;

    my @results;

    # We are going to build a long SQL query.
    my $sth = Bugzilla->dbh->prepare("
select
    products.name, bugs.product_id, count(bugs.product_id) as n
from
    bugs, products
where
    (bugs.bug_status = 'NEW' or bugs.bug_status = 'ASSIGNED' or
    bugs.bug_status = 'REOPENED' or bugs.bug_status = 'UNCONFIRMED')
and
    bugs.bug_severity != 'enhancement'
and
    products.id = bugs.product_id

group by product_id
order by n desc
limit $number
    ");
    $sth->execute;

    # For each product we want to show the difference in the last period.
    # But this will involve two sql connections at once, which the bugzilla
    # functions don't handle too nicely.
    # So lets collect the data first and then print the table.
    my %product_count;
    my %product_id;

    while (my ($product, $p_id, $count) = $sth->fetchrow_array) {
        $product_count{$product} = $count;
        $product_id{$product} = $p_id;
    }


    foreach my $product (reverse sort
                    {$product_count{$a} <=> $product_count{$b}}
                                    keys (%product_count)) {
        my %product_results;
        my $bopened = &bugs_opened($product_id{$product}, $days);
        my $bclosed = &bugs_closed($product_id{$product}, $days);
        $product_results{'id'} = $product_id{$product};
        $product_results{'name'} = $product;
        $product_results{'count'} = $product_count{$product};
        $product_results{'bugs_opened'} = $bopened;
        $product_results{'bugs_closed'} = $bclosed;
        $product_results{'bugs_change'} = $bopened - $bclosed;
        if( $product_results{'bugs_change'} > 0 ) {
            $product_results{'bugs_change_color'} = "#FF9999";
        } elsif( $product_results{'bugs_change'} < 0 ) {
            $product_results{'bugs_change_color'} = "#99FF99";
        }
        $product_results{'total_wishes'} = &open_wishes($product_id{$product});
        $product_results{'open_wishes'} = &wishes_opened($product_id{$product}, $days);
        $product_results{'closed_wishes'} = &wishes_closed($product_id{$product}, $days);
        $product_results{'wishes_change'} = $product_results{'open_wishes'} -
                                            $product_results{'closed_wishes'};
        if( $product_results{'wishes_change'} > 0 ) {
            $product_results{'wish_change_color'} = "#FF9999";
        } elsif( $product_results{'wishes_change'} < 0 ) {
            $product_results{'wish_change_color'} = "#99FF99";
        }
        push @results, \%product_results;
    }

    return \@results;
}

sub print_bug_hunters_list() {
    my($number, $days) = @_;
    my @results;
    my $query;

    my $sth = Bugzilla->dbh->prepare("
select
    assign.login_name, count(assign.login_name), count(assign.login_name) as n
from
    bugs, bugs_activity, profiles assign
where
    (bugs_activity.added='RESOLVED' or bugs_activity.added = 'CLOSED' or
     bugs_activity.added='NEEDSINFO')
and
    bugs_activity.bug_when >= from_days(TO_DAYS(NOW()) - ?)
and
    bugs_activity.who = assign.userid
and
    bugs.bug_id = bugs_activity.bug_id
and
    (bugs.bug_status = 'RESOLVED' or bugs.bug_status = 'CLOSED')
group by assign.login_name
order by n desc
limit ?
    ");

    $sth->execute($days, $number);
    while (my ($user, $count, $n) = $sth->fetchrow_array()) {

        my %bh_results;

        # defang the email address
        $user =~ y/\@\./  / if (Bugzilla->user->id == 0);
        $bh_results{'user'} = $user;
        $bh_results{'count'} = $count;

        push @results, \%bh_results;
    }

    return \@results;
}

sub print_bug_fixers_list() {
    my($number, $days) = @_;
    my @results;

    my $sth = Bugzilla->dbh->prepare("
SELECT
    profiles.login_name, bugs.bug_id,
    MIN(UNIX_TIMESTAMP(bugs_activity.bug_when)-UNIX_TIMESTAMP(bugs.creation_ts))
        AS open_time
FROM
    bugs, bugs_activity, profiles, longdescs
WHERE
    bugs.resolution='FIXED'
AND
    bugs.bug_status='RESOLVED'
AND
    bugs_activity.bug_when  >= FROM_DAYS(TO_DAYS(NOW())-?)
AND
    bugs.bug_id=bugs_activity.bug_id
AND
    bugs_activity.added='FIXED'
AND
    bugs_activity.who=profiles.userid
AND
    bugs.reporter != bugs_activity.who
AND
    longdescs.bug_id = bugs.bug_id
AND
    longdescs.who = bugs_activity.who
AND
    longdescs.thetext like \"%change APPROVED and MERGED%\"
GROUP BY
    profiles.login_name, bugs.bug_id
ORDER BY
    open_time ASC
LIMIT ?");

    $sth->execute($days, $number);

    while (my ($user, $bugid, $elapsed) = $sth->fetchrow_array) {

        my %bf_results;

        # defang the email address
        $user =~ y/\@\./  / if (Bugzilla->user->id == 0);
        $bf_results{'name'} = $user;
        $bf_results{'elapsed'} = ${elapsed};
        my $html_elapsed = "${elapsed}s";
        $html_elapsed = int($elapsed/60) . " min" if ($elapsed > 60);
        $html_elapsed = int($elapsed/(60*60)) . " h" if ($elapsed > (4*60*60));
        $html_elapsed = int($elapsed/(60*60*24)) . " days" if ($elapsed > (58*60*60));
        $bf_results{'formatted_elapsed'} = $html_elapsed;
        $bf_results{'bugid'} = $bugid;

        push @results, \%bf_results;
    }

    return \@results;
}


Bugzilla->login(LOGIN_OPTIONAL);

# For most scripts we don't make $cgi and $template global variables. But
# when preparing Bugzilla for mod_perl, this script used these
# variables in so many subroutines that it was easier to just
# make them globals.
local our $cgi = Bugzilla->cgi;
local our $template = Bugzilla->template;
local our $vars = {};

# Output appropriate HTTP response headers
print $cgi->header(-type => 'text/html', -expires => '+3M');

my %defaults;

# If they didn't tell us a time period we choose the last week.
my $current_days = 7;
$current_days = $cgi->param('days') if (defined $cgi->param('days'));
$current_days = 7 if (!detaint_natural($current_days));
$defaults{'days'} = $current_days;

my $current_tops = 20;
$current_tops = $cgi->param('tops') if (defined $cgi->param('tops'));
$current_tops = 20 if (!detaint_natural($current_tops));

# Reset tops parameter to default if very long
my $length_cgi_tops = length ($cgi->param('tops'));
$current_tops = 20 if ($length_cgi_tops > 15);

$defaults{'tops'} = $current_tops;

$vars->{'duration'} = $current_days;
$vars->{'top_number'} = $current_tops;

($vars->{'totalbugs'}, $vars->{'totalwishes'}) = &total_bugs_in_bugzilla();

my $bo = &bugs_opened("%", $current_days);
my $wo = &wishes_opened("%", $current_days);
my $bc = &bugs_closed("%", $current_days);
my $wc = &wishes_closed("%", $current_days);

$vars->{'new_open_bugs'} = $bo;
$vars->{'new_closed_bugs'} = $bc;
$vars->{'new_open_wishes'} = $wo;
$vars->{'new_closed_wishes'} = $wc;

my @tops = (10, 20, 30, 50, 100);
$vars->{'tops'} = \@tops;

my @days = (1, 2, 7, 14, 31, 180, 365);
$vars->{'days'} = \@days;

$vars->{'default'} = \%defaults;

$vars->{'product_bug_lists'} = &print_product_bug_lists($current_tops, $current_days, "HTML");

$vars->{'bug_hunters_list'} = &print_bug_hunters_list($current_tops, $current_days);

$vars->{'bug_fixers_list'} = &print_bug_fixers_list($current_tops, $current_days);

$template->process("weeklyreport/weekly-bug-summary.html.tmpl", $vars)
  || ThrowTemplateError($template->error());





print "</div>\n";

