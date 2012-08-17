#!/usr/bin/php -r
<?php

# Bugzilla is set on UTC
date_default_timezone_set( 'UTC' );

# root of your mw installation ... we use the HTTPClient class
$IP = "/var/www/wiki/mediawiki/core";
define( 'MEDIAWIKI', true );

# Parse the ~/.bugzilla.ini file for the settings for your bot
# (Also lets us keep the username/password out of the VCS).
$u = parse_ini_file( getenv( 'HOME' ) . "/.bugzilla.ini" );

if( !isset( $u['email'] ) || !isset( $u['password'] ) ) {
	echo "Please create a ~/.bugzilla.ini file with your email and password!";
	exit(1);
}

# The JSON RPC doesn't *require* login, but I have one just in case I
# need it to get at certain info or make some modifications.
require( "bugzilla.php" );
$bz = new BugzillaWebClient( $u['url'] . '/jsonrpc.cgi', $u['email'], $u['password'], $u['debug'] ? $u['debug'] : 0 );

# The queries we want to count the results for.
# 'Query Title' is used to print out the results, and will be removed
# from the actual query.
$queries = array(
	// https://bugzilla.wikimedia.org/buglist.cgi?keywords=shell&query_format=advanced&keywords_type=allwords&list_id=85394&bug_status=UNCONFIRMED&bug_status=NEW&bug_status=ASSIGNED&bug_status=REOPENED&known_name=Shell%3A%20All%20Open%20Requests&query_based_on=Shell%3A%20All%20Open%20Requests
	array(
		'Query Title' => 'All currently open shell bugs',
		'status' => array( 'UNCONFIRMED', 'NEW', 'ASSIGNED', 'REOPENED' ),
		// 'keywords' => array( 'shell' ), // Currently no search by keyword support
	),
	// https://bugzilla.wikimedia.org/buglist.cgi?keywords=shell&query_format=advanced&keywords_type=allwords&list_id=80453&bug_status=RESOLVED&bug_status=VERIFIED&bug_status=CLOSED&known_name=Shell%3A%20All%20Open%20Requests
	array(
		'Query Title' => 'All closed/resolved/verified shell bugs',
		'status' => array( 'CLOSED', 'RESOLVED', 'VERIFIED' ),
		// 'keywords' => array( 'shell' ), // Currently no search by keyword support
	),
	// https://bugzilla.wikimedia.org/buglist.cgi?keywords=shell&query_format=advanced&keywords_type=allwords&list_id=80456&bug_status=RESOLVED&bug_status=VERIFIED&bug_status=CLOSED&resolution=FIXED
	array(
		'Query Title' => 'All closed/resolved/verified fixed shell bugs',
		'status' => array( 'CLOSED', 'RESOLVED', 'VERIFIED' ),
		'resolution' => array( 'FIXED' ),
		// 'keywords' => array( 'shell' ), // Currently no search by keyword support
	),
);

$now = new DateTime( 'now' );
// https://bugzilla.wikimedia.org/buglist.cgi?chfieldto=2012-07-30&keywords=shell&chfield=bug_status&query_format=advanced&keywords_type=allwords&chfieldfrom=2012-07-01&list_id=85568&bug_status=RESOLVED&bug_status=VERIFIED&bug_status=CLOSED
$queries[] = array(
	'Query Title' => 'All closed/resolved/verified shell bugs from last month',
	'status' => array( 'CLOSED', 'RESOLVED', 'VERIFIED' ),
	'resolution' => array( 'FIXED' ),
	// 'keywords' => array( 'shell' ), // Currently no search by keyword support
	'last_change_time' => $now->modify( 'first day of last month' )->format( 'Y-m-d' ), // Searches for bugs that were modified at this time or later.
	// 'chfieldto' => $now->modify( 'last day of this month' )->format( 'Y-m-d' ),
),

# iterate over a list of patches
foreach( $queries as $terms ) {

	# Print the title if there is one.
	if( isset( $terms['Query Title'] ) ) {
		echo $terms['Query Title'], ": ";
		unset( $terms['Query Title'] );
	}

	# Start the iterator
	$iter = new BugzillaSearchIterator( $bz, $terms );
	$count = 0;
	foreach( $iter as $bug ) {
		# only counting the bugs, could print them out here.
		$count++;
	}

	# Print the count
	echo "$count\n";
}
