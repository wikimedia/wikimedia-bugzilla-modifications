# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.
#
# This Source Code Form is "Incompatible With Secondary Licenses", as
# defined by the Mozilla Public License, v. 2.0.
#
# The Original Code is the Bugzilla Bug Tracking System.
#
# Contributor(s): Kunal Mehta <legoktm@gmail.com>

package Bugzilla::Extension::MoreBugUrl::SourceForgeAllura;

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
    # Allura URLs have this form:
    #   https://sourceforge.net/p/pywikipediabot/*/349/
    return (lc($uri->authority) eq 'sourceforge.net'
           and $uri->path =~ m|^/p/[0-9a-zA-Z_]+/[0-9a-zA-Z_-]+/\d+/?$|) ? 1 : 0;
}

sub _check_value {
    my $class = shift;

    my $uri = $class->SUPER::_check_value(@_);
    # Always make the link https:
    $uri->scheme('https');
    return $uri;
}

1;
