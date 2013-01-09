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
# The Original Code is the Sitemap Bugzilla Extension.
#
# The Initial Developer of the Original Code is Everything Solved, Inc.
# Portions created by the Initial Developer are Copyright (C) 2010 the
# Initial Developer. All Rights Reserved.
#
# Contributor(s):
#   Max Kanat-Alexander <mkanat@bugzilla.org>

package Bugzilla::Extension::Sitemap;
use strict;
use base qw(Bugzilla::Extension);

our $VERSION = '1.0';

use Bugzilla::Extension::Sitemap::Constants;
use Bugzilla::Extension::Sitemap::Util;
use Bugzilla::Constants qw(bz_locations);
use Bugzilla::Util qw(correct_urlbase get_text);

use DateTime;
use File::Copy;
use IO::File;

#########
# Pages #
#########

sub template_before_process {
    my ($self, $args) = @_;
    my ($vars, $file) = @$args{qw(vars file)};

    return if !$file eq 'global/header.html.tmpl';
    return unless (exists $vars->{bug} or exists $vars->{bugs});
    my $bugs = exists $vars->{bugs} ? $vars->{bugs} : [$vars->{bug}];
    return if !ref $bugs eq 'ARRAY';

    foreach my $bug (@$bugs) {
        if (!bug_is_ok_to_index($bug)) {
            $vars->{sitemap_noindex} = 1;
            last;
        }
    }
}

sub page_before_template {
    my ($self, $args) = @_;
    my $page = $args->{page_id};

    if ($page =~ m{^sitemap/sitemap\.}) {
        _page_sitemap();
    }
}

sub _page_sitemap {
    my $map = generate_sitemap();
    print Bugzilla->cgi->header('text/xml');
    print $map->xml;
    exit;
}

################
# Installation #
################

sub install_before_final_checks {
    my ($self) = @_;
    if (!correct_urlbase()) {
        print STDERR get_text('sitemap_no_urlbase'), "\n";
        return;
    }
    if (Bugzilla->params->{'requirelogin'}) {
        print STDERR get_text('sitemap_requirelogin'), "\n";
        return;
    }
    $self->_fix_robots_txt();
    _do_ping();
}

sub _fix_robots_txt {
    my ($self) = @_;
    my $cgi_path = bz_locations()->{'cgi_path'};
    my $robots_file = "$cgi_path/robots.txt";
    my $current = new IO::File("$cgi_path/robots.txt", 'r');
    if (!$current) {
        warn "$robots_file: $!";
        return;
    }

    my $current_contents;
    { local $/; $current_contents = <$current> }
    $current->close();

    return if $current_contents =~ m{^Allow: \/\*show_bug\.cgi}ms;
    my $backup_name = "$cgi_path/robots.txt.old";
    print get_text('sitemap_fixing_robots', { current => $robots_file,
                                              backup  => $backup_name }), "\n";
    rename $robots_file, $backup_name or die "backup failed: $!";
    my $new_file = $self->package_dir . '/robots.txt';
    copy($new_file, $robots_file) or die "$new_file -> $robots_file: $!";
}

sub _do_ping {
    my $done_filename = bz_locations()->{'datadir'} . '/sitemaps_done';
    if (-e $done_filename) {
        my $done = new IO::File($done_filename, 'r');
        if ($done) {
            my $done_content = <$done>;
            chomp($done_content);
            $done->close();
            return if $done_content eq correct_urlbase();
        }
        else {
            warn "$done_filename: $!";
        }
    }

    print get_text('sitemap_pinging'), "\n";

    require Search::Sitemap::Ping;
    my $url  = correct_urlbase() . SITEMAP_URL;
    my $ping = Search::Sitemap::Ping->new($url);
    my $failures = 0;
    foreach my $engine ($ping->engines) {
        $engine->add_trigger('success', \&_ping_success);
        $engine->add_trigger('failure', sub { _ping_failure(\$failures, @_) });
    }
    $ping->submit();

    if ($failures) {
        print get_text('sitemap_some_failures'), "\n\n";
    }
    else {
        print get_text('sitemap_no_failures', { done_file => $done_filename }),
              "\n\n";
        my $done = new IO::File($done_filename, '>') or die "$done_filename: $!";
        print $done correct_urlbase();
    }
}

sub _ping_success {
    my ($engine) = @_;
    _ping_message($engine, 'sitemap_ok');
}

sub _ping_failure {
    my ($failures, $engine) = @_;
    $$failures++;
    _ping_message($engine, 'sitemap_failed');
}

sub _ping_message {
    my ($engine, $message) = @_;
    my $type = ref $engine;
    $type =~ /::(\w+)$/;
    my $name = $1;
    my $result = get_text($message, { engine => $name });
    $name = sprintf('%10s', $name);
    print "$name$result\n";
}
__PACKAGE__->NAME;
