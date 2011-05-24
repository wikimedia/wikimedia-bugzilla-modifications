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
# The Original Code is the Mediawiki Bugzilla Extension.
#
# The Initial Developer of the Original Code is Priyanka Dhanda.
# Portions created by the Initial Developer are Copyright (C) 2010 the
# Initial Developer. All Rights Reserved.
#
# Contributor(s):
#   Priyanka Dhanda <pdhanda@wikimedia.org>

package Bugzilla::Extension::Wikimedia;
use strict;
use base qw(Bugzilla::Extension);

use Bugzilla::Util;

our $VERSION = '0.01';

# See the documentation of Bugzilla::Hook ("perldoc Bugzilla::Hook" 
# in the bugzilla directory) for a list of all available hooks.
sub install_update_db {
    my ($self, $args) = @_;

}

sub bug_format_comment {
        my ($self, $args) = @_;
        my $regexes = $args->{'regexes'};
        my $text = $args->{'text'};
        my $replacerWP = {
                match => qr{\[\[([a-zA-Z0-9_ ,./'()!#\*\$%:\x80-\xff-]+)\]\]},
                replace => \&_createWikipediaLink
        };
	my $replacerCR = {
                match => qr{r(\d+)},
                replace => \&_createCodeReviewLink
        };
         #~\br(\d+)\b
         #      "<a href=\"http://www.mediawiki.org" .
         #       "/wiki/Special:Code/MediaWiki/$1\" " .
         #       "title=\"revision $1 in SVN\">r$1</a>"
 
        push( @$regexes, $replacerWP );
	push( @$regexes, $replacerCR );
}

sub _createWikipediaLink {
        my $match_str = $1;
        my $tmp = html_quote($match_str);
        my $wikipedia_link = "[[<a href='http://en.wikipedia.org/w/index.php?title=Special:Search&go=Go&search=$tmp'>$tmp</a>]]";
       return $wikipedia_link;
};  

sub _createCodeReviewLink {
        my $rev_link = "<a href=\"http://www.mediawiki.org/wiki/Special:Code/MediaWiki/$1\" title=\"revision $1 in SVN\">r$1</a>";
       return $rev_link;
};
 
__PACKAGE__->NAME;
