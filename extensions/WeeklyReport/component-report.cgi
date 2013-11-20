#!/usr/bin/perl -wT
# -*- Mode: perl; indent-tabs-mode: nil -*-
#
# The contents of this file are subject to the Mozilla Public
# License Version 1.1 (the "License"); you may not use this file
# except in compliance with the License. You may obtain a copy of
# the License at http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS
# IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or
# implied. See the License for the specific language governing
# rights and limitations under the License.
#
# The Original Code is the Bugzilla Bug Tracking System.
#
# The Initial Developer of the Original Code is Netscape Communications
# Corporation. Portions created by Netscape are
# Copyright (C) 1998 Netscape Communications Corporation. All
# Rights Reserved.
#
# Contributor(s): Harrison Page <harrison@netscape.com>,
# Terry Weissman <terry@mozilla.org>,
# Dawn Endico <endico@mozilla.org>
# Bryce Nesbitt <bryce@nextbus.COM>,
#    Added -All- report, change "nobanner" to "banner" (it is strange to have a
#    list with 2 positive and 1 negative choice), default links on, add show
#    sql comment.
# Joe Robins <jmrobins@tgix.com>,
#    If using the usebuggroups parameter, users shouldn't be able to see
#    reports for products they don't have access to.
# Gervase Markham <gerv@gerv.net> and Adam Spiers <adam@spiers.net>
#    Added ability to chart any combination of resolutions/statuses.
#    Derive the choice of resolutions/statuses from the -All- data file
#    Removed hardcoded order of resolutions/statuses when reading from
#    daily stats file, so now works independently of collectstats.pl
#    version
#    Added image caching by date and datasets
# Myk Melez <myk@mozilla.org):
#    Implemented form field validation and reorganized code.
#
# Luis Villa <louie@ximian.com>:
#    modified it to report things in a new format
# Matt Rogers <mattr@kde.org>:
#    Rewritten for bugzilla 3.0 including template

use strict;
use lib qw(.);

use Bugzilla;
use Bugzilla::Product;
use Bugzilla::Constants;
use Bugzilla::Error;
use Bugzilla::Util;

use vars qw($vars $template);

# If we're using bug groups for products, we should apply those restrictions
# to viewing reports, as well.  Time to check the login in that case.

my $cgi = Bugzilla->cgi;
my $template = Bugzilla->template;
my $vars = {};
my $user = Bugzilla->login(LOGIN_OPTIONAL);

print $cgi->header(-type => 'text/html', -expires => '+3M');

my $query = <<EOF
SELECT   count(*), products.name, components.name, bug_severity
  FROM   bugs, products, components
 WHERE   ( bug_status = 'NEW' or bug_status = 'ASSIGNED' or bug_status = 'REOPENED' or bug_status = 'UNCONFIRMED' )
   AND   products.name = ?
   AND   bugs.product_id = products.id
   AND   bugs.component_id = components.id
   AND   products.id = components.product_id
GROUP BY products.name, components.name, bug_severity
ORDER BY components.name
EOF
; 

#Report on components by severity and priority
my $sth = Bugzilla->dbh->prepare($query);
my $product = trim($cgi->param('product'));

# FIXME: Print a "error" message
ThrowUserError('product_blank_name') if !$product;

if ($product =~ m/^([\w.-]+)$/) { $product = $1; }
trick_taint($product);
$sth->execute($product);

my (@bug_counts, %bugs, %total_bugs);
my $disp_component;
my $total_bug_count;

my $product_obj = new Bugzilla::Product({ 'name' => $product });
ThrowUserError('invalid_product_name', {product => $product}) if !$product_obj;

$vars->{'product'} = $product;
$vars->{'all_severities'} = Bugzilla::Field::get_legal_field_values('bug_severity');
my @compnames;
for my $comp (@{$product_obj->components}) {
  push @compnames, $comp->name
}
$vars->{'all_components'} = \@compnames;

while (my ($bcount, $product, $component, $sever) = $sth->fetchrow_array) {
  $bugs{$component}{$sever} = $bcount;
  $bugs{$component}{'total'} += $bcount;
}

$vars->{'bug_sev_counts'} = \%bugs;

$template->process("weeklyreport/component-report.html.tmpl", $vars)
  || ThrowTemplateError($template->error());
