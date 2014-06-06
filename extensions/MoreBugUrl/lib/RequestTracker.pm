# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.
#
# This Source Code Form is "Incompatible With Secondary Licenses", as
# defined by the Mozilla Public License, v. 2.0.
#
# The Original Code is the Bugzilla Bug Tracking System.
#
# Contributor(s): Andre Klapper <ak-47@gmx.net>

package Bugzilla::Extension::MoreBugUrl::RequestTracker;

use 5.10.1;
use strict;
use parent qw(Bugzilla::BugUrl);

use Bugzilla::Error;
use Bugzilla::Util;

###############################
####        Methods        ####
###############################

sub should_handle {
    my ($class, $uri) = @_;
    # RT URLs have this form:
    #   http[s]://rt.wikimedia.org/Ticket/Display.html?id=12345
    return (lc($uri->authority) eq 'rt.wikimedia.org'
           and $uri->path =~ m|^/Ticket/Display\.html$|
           and $uri->query_param('id') =~ /^\d+$/) ? 1 : 0;
}

sub _check_value {
    my $class = shift;

    my $uri = $class->SUPER::_check_value(@_);
    # Always make the link https:
    $uri->scheme('https');
    return $uri;
}

1;
