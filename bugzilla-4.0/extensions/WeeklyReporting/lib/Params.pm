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

package Bugzilla::Extension::WeeklyReporting::Params;
use strict;

use Bugzilla::Constants;
use Bugzilla::Error;
use Bugzilla::Util;

use constant get_param_list => (
  {
   name => 'weeklyreporting_email',
   type => 't',
   default => '',
   checker => \&_check_email_list,
  }
);

# Validate the list of e-mail addresses
sub _check_email_list {
	my ($value) = @_;
	foreach my $email (split ",", $value) {
		$email = trim($email);
		next if !$email;
		if ( !validate_email_syntax($email) ) {
			return "Invalid e-mail address";
		}
		my $error_mode = Bugzilla->error_mode;
		Bugzilla->error_mode(ERROR_MODE_DIE);
		my $success = eval {
				trick_taint($email);
				1;
		};
		Bugzilla->error_mode($error_mode);
		return $@ if !$success;
	}
	return "";
}

1;
