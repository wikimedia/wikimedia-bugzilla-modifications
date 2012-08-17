#!/usr/bin/php -r
<?php

# This is the file we use to store the timestamp of the last time we
# ran the script.  If the file doesn't exist (e.g. the first time we
# run the script), then the last 24 hours is checked.
define('LAST_CHECK', 'bz-simple-queries.txt');

# The default time to use if this is the first time this has been run.
# Bugzilla is set on UTC
date_default_timezone_set( 'UTC' );
$default_time = strftime( '%FT%T%Z', time() - 24*3600 ); /* past 24 hours */

# root of your mw installation ... we use the HTTPClient class
$IP = "/home/mah/work/code/mediawiki/core";
define('MEDIAWIKI', true);

# Parse the ~/.bugzilla.ini file for the settings for your bot
# (Also lets us keep the username/password out of the VCS).
$u = parse_ini_file(getenv('HOME')."/.bugzilla.ini");

# My ~/.bugzilla.ini file contains:
#     email = mah@everybody.org
#     password = XXXXXX
#     debug = 0
#     url = https://bugzilla.wikimedia.org/

if( !isset( $u['email'] ) || !isset( $u['password'] ) ) {
	echo "Please create a ~/.bugzilla.ini file with your email and password!";
	exit(1);
}

# The JSON RPC doesn't *require* login, but I have one just in case I
# need it to get at certain info or make some modifications.
require( "bugzilla.php" );
$bz = new BugzillaWebClient( $u['url'].'/jsonrpc.cgi', $u['email'], $u['password'], $u['debug'] ? $u['debug'] : 0);

# Check if we've run before and have stored the time.
if( file_exists( LAST_CHECK ) ) {
	$time = file_get_contents( LAST_CHECK );
	if( $time === false ) {
		throw new Exception( "No time" );
	}
	$time = unserialize( $time );
} else {
	$time = $default_time;
}

# The queries we want to count the results for.
# 'Query Title' is used to print out the results, and will be removed
# from the actual query.
$queries = array(
	array(
		'Query Title' => "All bugs that have changed since $time",
		'last_change_time' => $time,
	),
	array(
		'Query Title' => "New bugs since $time",
		'creation_time' => $time,
	),
	array(
		'Query Title' => "New, as-yet-unresolved bugs against MW Core since $time",
		'creation_time' => $time,
		'product' => array( 'MediaWiki' ),
		'resolution' => array( '' )
	),
);


# Store the starting time before we begin so no bug reports slip
# through the cracks.  May have dupes, though.
$started_time = serialize( strftime( '%FT%T-0000' ) );

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

# Store the time for next time.
file_put_contents( LAST_CHECK, $started_time );
