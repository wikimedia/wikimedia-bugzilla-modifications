#!/usr/bin/php
<?php
define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/core"; # root of your mw installation ... we use the HTTPClient class

require_once 'bugzilla.php';
$u = parse_ini_file(getenv('HOME')."/.bugzilla.ini");
$bz = new BugzillaWebClient( $u['url'].'/jsonrpc.cgi', $u['email'], $u['password'], $u['debug']);

$terms = array(
	"resolution"       => array( "" ),
	"product"          => array( "MediaWiki", "MediaWiki extensions" ),
);

$bugList = array();
$iter = new BugzillaSearchIterator( $bz, $terms );
$message = "";
$count = 0 ;
foreach($iter as $bug) {
	$message .= "http://bugzilla.wikimedia.org/{$bug->getID()} -- {$bug->getAssignee()}\n";
	$message .= "    {$bug->getSummary()}\n";
	$count++;
	if ($count == 10) exit;
}

echo "$message\n";
