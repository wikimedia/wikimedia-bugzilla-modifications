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

our $VERSION = '0.1';

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
		match => qr{\[\[([^<>\[\]\|\{\}]+)(\||\]\])},
		replace => \&_createWikipediaLink
	};
	my $replacerCR = {
		match => qr{\br(\d+)},
		replace => \&_createCodeReviewLink
	};
	my $replacerRT = {
		match => qr{\brt\ ?\#?(\d+)}i,
		replace => \&_createRTLink
	};
# Testcase: Gerrit 2.5
# seen in: Summary of https://bugzilla.wikimedia.org/show_bug.cgi?id=41321
	my $replacerGerritChangeset = {
		match => qr{\bgerrit(\schange(set)?)?\s?\#?(\d{2,})}i,
		replace => \&_createGerritChangesetLink
	};
# Testcase: https://gerrit.wikimedia.org/r/#q,I0d6c654a7354ba77e65e338423952a6a78c1150f,n,z
# seen in: https://bugzilla.wikimedia.org/show_bug.cgi?id=18195#c14
	my $replacerGerritChangeId = {
		match => qr{(?:^|(?<=[\s\[\{\(]))(I[0-9a-f]{8,40})}i,
		replace => \&_createGerritChangeidLink
	};
# Testcase: https://gerrit.wikimedia.org/r/gitweb?p=operations/mediawiki-config.git;a=blob;f=wmf-config/CommonSettings.php;h=954509678eeb4c1079fb7addfa189001671c6671;hb=HEAD#l1530
# seen in: https://bugzilla.wikimedia.org/show_bug.cgi?id=41745#c5
	my $replacerGitCommit = {
		match => qr{(?:^|(?<=[\s\[\{\(]))([a-f0-9]{8,40})}i,
		replace => \&_createGitCommitLink
	};

	# Test case: https://bugzilla.wikimedia.org/60112#c8
	my $replacerCVE = {
		match => qr{(?<!http://people\.canonical\.com/~ubuntu-security/cve/2013/)\b(CVE-\d{4}-\d+)},
		replace => \&_createCVELink
	};

	push( @$regexes, $replacerWP );
	push( @$regexes, $replacerCR );
	push( @$regexes, $replacerRT );
	push( @$regexes, $replacerGerritChangeset );
	push( @$regexes, $replacerGerritChangeId );
	push( @$regexes, $replacerGitCommit );
	push( @$regexes, $replacerCVE );
}

sub _createWikipediaLink {
	my $match_str = $1;
	my $tail = $2;
	my $linktext = html_quote($match_str);
	my $searchstring = html_quote(url_quote($match_str));
	my $wikipedia_link = "[[<a href=\"https://en.wikipedia.org/w/index.php?title=Special:Search&go=Go&search=$searchstring\">$linktext</a>$tail";
	return $wikipedia_link;
};

sub _createCodeReviewLink {
	my $rev_link = "<a href=\"https://www.mediawiki.org/wiki/Special:Code/MediaWiki/$1\" title=\"revision $1 in SVN\">r$1</a>";
	return $rev_link;
};

sub _createRTLink {
	my $rt_link = "<a href=\"https://rt.wikimedia.org/Ticket/Display.html?id=$1\" title=\"RT #$1\">RT #$1</a>";
	return $rt_link;
};

sub _createGerritChangesetLink {
	my $change_link = "<a href=\"https://gerrit.wikimedia.org/r/$3\" title=\"Gerrit change #$3\">Gerrit change #$3</a>";
	return $change_link;
};

sub _createGerritChangeidLink {
	my $change_link = "<a href=\"https://gerrit.wikimedia.org/r/#q,$1,n,z\" title=\"Gerrit Change-Id: $1\">$1</a>";
	return $change_link;
};

sub _createGitCommitLink {
	my $commit_link = "<a href=\"https://gerrit.wikimedia.org/r/#q,$1,n,z\" title=\"Git commit $1\">$1</a>";
	return $commit_link;
};

sub _createCVELink {
	my $cve_link = "<a href=\"https://cve.mitre.org/cgi-bin/cvename.cgi?name=$1\" title=\"$1\">$1</a>";
	return $cve_link;
}

__PACKAGE__->NAME;
