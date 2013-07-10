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

package Bugzilla::Extension::Sitemap::Util;
use strict;
use base qw(Exporter);
our @EXPORT = qw(generate_sitemap bug_is_ok_to_index);

use Bugzilla::Extension::Sitemap::Constants;
use Bugzilla::Util qw(correct_urlbase datetime_from);

use Scalar::Util qw(blessed);

# Instead of doing "use Search::Sitemap", we "require" it when we need it.
# This is because Search::Sitemap uses Moose, and we don't want it
# slowing down normal mod_cgi page loads.

sub too_young_date {
    my $hours_ago = DateTime->now(time_zone => Bugzilla->local_timezone);
    $hours_ago->subtract(hours => SITEMAP_DELAY);
    return $hours_ago;
}

sub bug_is_ok_to_index {
    my ($bug) = @_;
    return 1 unless blessed($bug) && $bug->isa('Bugzilla::Bug');
    my $creation_ts = datetime_from($bug->creation_ts);
    return ($creation_ts lt too_young_date()) ? 1 : 0;
}

# We put two things in the Sitemap: a list of Browse links for products,
# and links to bugs.
sub generate_sitemap {
    require Search::Sitemap;

    # Sitemaps must never contain private data.
    Bugzilla->logout_request();
    my $user = Bugzilla->user;
    my $products = $user->get_accessible_products;

    my $num_bugs = SITEMAP_MAX - scalar(@$products);
    # We do this date math outside of the database because databases
    # usually do better with a straight comparison value.
    my $hours_ago = too_young_date();
    my $since = $hours_ago->ymd . ' ' . $hours_ago->hms;

    # We don't use Bugzilla::Bug objects, because this could be a tremendous
    # amount of data, and we only want a little. Also, we only display
    # bugs that are not in any group. We show the last $num_bugs
    # most-recently-updated bugs.
    my $dbh = Bugzilla->dbh;
    my $bug_data = $dbh->selectall_arrayref(
        'SELECT bugs.bug_id, bugs.delta_ts
           FROM bugs
                LEFT JOIN bug_group_map ON bugs.bug_id = bug_group_map.bug_id
          WHERE bug_group_map.bug_id IS NULL AND creation_ts < ? 
       ORDER BY delta_ts DESC '
         . $dbh->sql_limit($num_bugs), undef, $hours_ago);

    my $bug_url = correct_urlbase() . 'show_bug.cgi?id=';
    my $product_url = correct_urlbase() . 'describecomponents.cgi?product=';
    my $map = Search::Sitemap->new();
    foreach my $product (@$products) {
        $map->add(
            loc => $product_url . $product->name,
            changefreq => 'daily',
            priority => '0.4',
        );
    }
    foreach my $bug_row (@$bug_data) {
        my ($id, $delta_ts) = @$bug_row;
        $map->add(
            loc      => $bug_url . $id,
            lastmod  => datetime_from($delta_ts, 'UTC')->iso8601 . 'Z',
        );
    }

    return $map;
}

1;
