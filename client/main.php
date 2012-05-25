#!/usr/bin/php
<?php

define('LAST_CHECK', 'bz-last-check.txt');

define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/core"; # root of your mw installation ... we use the HTTPClient class

require_once 'bugzilla.php';
$u = parse_ini_file(getenv('HOME')."/.bugzilla.ini");
$bz = new BugzillaWebClient( $u['url'].'/jsonrpc.cgi', $u['email'], $u['password'], $u['debug']);
date_default_timezone_set( 'UTC' );
if( file_exists( LAST_CHECK ) ) {
	$time = file_get_contents( LAST_CHECK );
	if( $time === false ) {
		throw new Exception( "No time" );
	}
	$time = unserialize( $time );
} else {
	$time = strftime( '%FT00:00:00-0000' );
}

$terms = array(
	'last_change_time' => $time,
);

$iter = new BugzillaPatchIterator( $bz, $terms );

# iterate over a list of patches
foreach( $iter as $it ) {
#   Find the files modified
	var_dump( $it );

#   Branch with bug/#/comment#
#   Apply patch
#   Commit with comment + message patch comes from
#   git-review
}

file_put_contents( LAST_CHECK, serialize( strftime( '%FT%T-0000' ) ) );
