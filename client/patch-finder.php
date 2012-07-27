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
	"last_change_time" => "2012-07-01T00:00:00Z"
);

class BugzillaPatchIterator extends BugzillaSearchIterator {
	public function getItem( $bug ) {
		$check = BugzillaBug::newFromQuery($this->bz, $bug);
		foreach( $check->getPatches( $this->conditions[ 'last_change_time' ] ) as $patch) {
			$this->data[] = $patch;
		}
		if( count( $this->data ) ) {
			return $check;
		}
		return false;
	}
}

$bugList = array();
$iter = new BugzillaPatchIterator( $bz, $terms );
$message = "";
$count = 0 ;
foreach($iter as $bug) {
	var_dump($bug);
	$message .= "http://bugzilla.wikimedia.org/{$bug->getID()} -- {$bug->getAssignee()}\n";
	$message .= "    {$bug->getSummary()}\n";
	echo $message;
	$message = "";
}

echo "$message\n";
