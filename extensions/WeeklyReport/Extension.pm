# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.
#
# This Source Code Form is "Incompatible With Secondary Licenses", as
# defined by the Mozilla Public License, v. 2.0.
#
# See: https://bugzilla.wikimedia.org/show_bug.cgi?id=25637#c3
# Original Source code from http://websvn.kde.org/trunk/www/sites/bugs/


package Bugzilla::Extension::WeeklyReport;
use strict;
use base qw(Bugzilla::Extension);

our $VERSION = '0.01';

# See the documentation of Bugzilla::Hook ("perldoc Bugzilla::Hook" 
# in the bugzilla directory) for a list of all available hooks.
sub install_update_db {
    my ($self, $args) = @_;

}

__PACKAGE__->NAME;
