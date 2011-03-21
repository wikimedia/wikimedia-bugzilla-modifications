#!/usr/bin/perl -w
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
# The Original Code is the VCS Bugzilla Extension.
#
# The Initial Developer of the Original Code is Red Hat, Inc.
# Portions created by the Initial Developer are Copyright (C) 2010 the
# Initial Developer. All Rights Reserved.
#
# Contributor(s):
#   Max Kanat-Alexander <mkanat@everythingsolved.com>

use strict;
use diagnostics;
use warnings;
use lib qw(lib);
use Getopt::Long;
use Pod::Usage qq/pod2usage/;
use DateTime;
use Bugzilla;

BEGIN { Bugzilla->extensions }

my %switch;
my $end_date = DateTime->now;
my $begin_date = $end_date->clone();
$begin_date = $begin_date->subtract( days => 7 );

GetOptions(\%switch, 'help|h|?', 'noemail' ) || die $@;

if ( $switch{'help'} ) {
    pod2usage({-exitval => 1});
}

foreach my $report ( Bugzilla::Extension::WeeklyReporting->get_report_list( $begin_date, $end_date ) ) {
	if( $switch{'noemail'} ) {
		$report->printReport();	
	} else {
		$report->emailReport();
	}
}

__END__

=head1 NAME

 sendReports.pl - Send reports scheduled for dispatch

=head1 SYNOPSIS

 sendReports.pl --noemail

=head1 OPTIONS

=over

=item B<--noemail>

Just print the report, rather than sending the e-mail

=back

=head1 DESCRIPTION

This script can be used to send reports on Bugzilla activity
