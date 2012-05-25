#!/usr/bin/php
<?php

define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/mw-svn"; # root of your mw installation ... we use the HTTPClient class

require_once 'mwApiClient.php';
require_once 'bugzilla.php';
$u = parse_ini_file(getenv('HOME')."/.bugzilla.ini");

# find FIXMEs
$iter = new mwApiClientIterator(new mwApiClient("http://bugzilla.wikimedia.org/", $u['email'], $u['password']));
$iter->findState("FIXME");

# iterate over list, group committers
#
foreach($iter as $bug) {
	$message = "http://bugzilla.wikimedia.org/{$bug->getID()}";
#    $message .= "   {$Bug->getPriorityText()} {$bug->getStatus()} {$bug->getSummary()}\n";
	$message .= " ({$bug->getPriorityText()}) -- {$bug->getSummary()}\n";
	$bugList[$bug->getPriority()][] = $message;
}
foreach($bugList as $cmp => $bugs) {
	echo "== $cmp ==\n";
	foreach($bugs as $m) {
		echo $m;
	}
	echo "\n";
}







