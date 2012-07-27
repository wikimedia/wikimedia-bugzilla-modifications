#!/usr/bin/php
<?php

define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/core"; # root of your mw installation ... we use the HTTPClient class

require_once 'bugzilla.php';
$u = parse_ini_file(getenv('HOME')."/.bugzilla.ini");
$bz = new BugzillaWebClient( $u['url'].'/jsonrpc.cgi', $u['email'], $u['password'], $u['debug']);

$message = "";
$iter = new BugzillaSearchIterator( $bz, array("last_change_time" => "2010-06-01T00:00:00Z"));
$fp = fopen('changes.csv', 'a');
fputcsv($fp, array("bug id", "time", "who", "field", "removed", "added"));
foreach( $iter as $bug ) {
	$id = $bug->getID();
	echo "Checking bug #$id ...\n";

	foreach($bug->getHistory() as $txn) {
		$time = $txn['when'];
		$who  = $txn['who'];
		foreach($txn['changes'] as $change) {
			$field = $change['field_name'];
			$removed = $change['removed'];
			$added = $change['added'];
			fputcsv($fp, array($id, $time, $who, $field, $removed, $added));
		}
	}
}
