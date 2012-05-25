#!/usr/bin/php
<?php

define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/mw-svn"; # root of your mw installation ... we use the HTTPClient class

require_once 'bugzilla.php';
$u = parse_ini_file(getenv('HOME')."/.bugzilla.ini");
$bz = new BugzillaWebClient( $u['url'].'/jsonrpc.cgi', $u['email'], $u['password'], $u['debug']);
$terms = array(
#3922, 12344, 13602, 18463, 18526, 18861, 19262, 20476, 23730, 25763, 26233, 27418, 
	"id" => array( 27488, 29038, 29197, 29277, 29408, 29461, 29574, 29731, 29784, 29921, 30052, 30235, 30425, 30787, 31122, 31173, 31255, 31680, 31697, 31795, 31945, 31962, 32013, 32023, 32056, 32229, 32551, 32711, 32760, 32827, 32868, 32949, 32951, 33322, 33388, 33437, 33506, 33564, 33580, 33762, 34055 ),
);

$troll = "john.next@gmx.com";
$iter = new BugzillaSearchIterator( $bz, $terms );
foreach($iter as $bug) {
	echo "Reverting {$bug->getID()}\n";
	$bug->undoLastChangeIfBy( $troll );
	foreach($bug->getComments() as $c) {
		if( $c['author'] === $troll ) {
			echo "\tdeleting {$c['id']}\n";
			$bug->deleteComment( $c['id'] );
		}
	}
}
