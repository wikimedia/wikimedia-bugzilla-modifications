#!/usr/bin/php
<?php

define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/mw-svn"; # root of your mw installation ... we use the HTTPClient class

require_once 'bugzilla.php';
$u = parse_ini_file(getenv('HOME')."/.bugzilla.ini");
$bz = new BugzillaWebClient( $u['url'].'/jsonrpc.cgi', $u['email'], $u['password'], $u['debug']);

#$bug = new BugzillaBug( 29917, $bz );
$bug = new BugzillaBug( 1, $bz );
var_dump($bug->hasDefaultAssignee());
var_dump($bug->getAssignee());
