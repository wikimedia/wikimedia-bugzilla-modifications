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
# See: https://bugzilla.wikimedia.org/show_bug.cgi?id=25637#c3
# Original Source code from http://websvn.kde.org/trunk/www/sites/bugs/

package Bugzilla::Extension::WeeklyReport;
use strict;

use constant NAME => 'WeeklyReport';

use constant REQUIRED_MODULES => [
];

use constant OPTIONAL_MODULES => [
];

__PACKAGE__->NAME;
