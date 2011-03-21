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
# The Original Code is the WeeklyReporting Bugzilla Extension.
#
# The Initial Developer of the Original Code is Chad Horohoe
# Portions created by the Initial Developer are Copyright (C) 2010 the
# Initial Developer. All Rights Reserved.
#
# Contributor(s):
#   Chad Horohoe <chad@anyonecanedit.org>

package Bugzilla::Extension::WeeklyReporting;
use strict;
use base qw(Bugzilla::Extension);

our $VERSION = '0.1';

sub config_add_panels {
	my ($self, $args) = @_;
	my $modules = $args->{'panel_modules'};
	$modules->{'WeeklyReporting'} = 'Bugzilla::Extension::WeeklyReporting::Params';
}

# List of reports used below
use Bugzilla::Extension::WeeklyReporting::WeekAtAGlance;

# When adding a report module above, be sure to push it into the array below
sub get_report_list {
	my( $self, $begin, $end ) = @_;
	my @reportList;
#	push @reportList, new Bugzilla::Extension::WeeklyReporting::WeekAtAGlance( $begin, $end );
	return @reportList;
}

__PACKAGE__->NAME;
