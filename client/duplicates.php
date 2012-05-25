#!/usr/bin/php
<?php

define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/mw-svn"; # root of your mw installation ... we use the HTTPClient class

require_once 'bugzilla.php';
require_once 'wmf-terms.php'
$u = parse_ini_file(getenv('HOME')."/.bugzilla.ini");
$bz = new BugzillaWebClient( $u['url'].'/jsonrpc.cgi', $u['email'], $u['password'], $u['debug']);
#$iter = new BugzillaSearchIterator( $bz, array( 'id' => $id ) );

$bugList = array();
$iter = new BugzillaSearchIterator( $bz, $terms );
$bugCount = 0;
$db = new SQLite3("./dupes");
$r = $db->query("CREATE TABLE IF NOT EXISTS dupes ( bug INTEGER, dupe INTEGER )");
if($r === false) {
	echo $r->lastErrorMsg();
	exit;
}
$r = $db->query("SELECT dupe FROM dupes");
$seen = array();
while($s = $r->fetchArray(SQLITE3_NUM)) {
	$seen[$s[0]] = 1;
}
$db->close();

foreach($iter as $bug) {
	if ( isset($seen[$bug->getId()]) ) {
		echo ".";
	} else {
		if($bug->isDuplicate()) {
			$bugList[$bug->isDuplicate()][] = $bug->getId();
		} else {
			$bugList[$bug->getID()][] = $bug->getId();
		}
		$bugCount++;
		if($bugCount % 100 == 0) {
			echo "$bugCount   \n";
			$db = new SQLite3("./dupes");
			foreach($bugList as $main => $dupe) {
				foreach($dupe as $d) {
					$r = $db->query("INSERT INTO dupes (bug, dupe) VALUES (".$main.",".$d.");");
					if( $r === false ) {
						echo "oops! '$main' -> '$d'\n";
						echo $r->lastErrorMsg();
						exit;
					}
				}
			}
			$db->close();
		}
	}
}
echo "Bug count: $bugCount\n";
