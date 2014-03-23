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
# Contributor(s): Andre Klapper <ak-47@gmx.net>

package Bugzilla::Extension::MoreBugUrl::Mingle;

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
    # Mingle URLs have this form:
    #   https://wikimedia.mingle.thoughtworks.com/projects/abcdefgh_ijkl/cards/12345
    return (lc($uri->authority) eq 'wikimedia.mingle.thoughtworks.com'
           and $uri->path =~ m|^/projects/[0-9a-zA-Z_]+/cards/\d+$|) ? 1 : 0;
}

sub _check_value {
    my $class = shift;

    my $uri = $class->SUPER::_check_value(@_);
    # Always make the link https:
    $uri->scheme('https');
    return $uri;
}

1;
