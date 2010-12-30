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

package Bugzilla::Extension::WeeklyReporting::ReportBase;
use strict;
use DateTime;
use Bugzilla::Mailer;
use Bugzilla::DB;

sub new {
    my($class, $begin, $end) = @_;
    my $self = { 
    	start => $begin,
    	stop => $end,
    	dbh => Bugzilla->dbh
    };
    bless($self, $class);
    return $self;
}

sub emailReport {
	MessageToMTA( get_report_text() );
}

sub printReport {
	print get_report_text();
}

sub get_report_text { return "Test\n"; }

1;
