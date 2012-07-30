#!/usr/bin/php
<?php
define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/core"; # root of your mw installation ... we use the HTTPClient class

require_once 'bugzilla.php';
$u = parse_ini_file(getenv('HOME')."/.bugzilla.ini");
$bz = new BugzillaWebClient( $u['url'].'/jsonrpc.cgi', $u['email'], $u['password'], $u['debug']);

$terms = array(
	"product"          => array( "MediaWiki", "MediaWiki extensions" ),
	"last_change_time" => "2012-04-01T00:00:00Z"
);

class BugzillaPatchIterator extends BugzillaSearchIterator {
	private $last_change = null;
	private $patch = array();
	private $patchIndex = 0;

	private function parseDate( $str ) {
		return DateTime::createFromFormat("Y-m-d?H:i:s?", $str);
	}

	private function dateCompare( $date ) {
		if ($this->last_change === null) {
			global $terms;
			$this->last_change = $this->parseDate( $terms['last_change_time'] );
		}

		$obj = $this->parseDate( $date );

		$x = $this->last_change->diff( $obj );
		if(! $x->invert) {
			return 1;
		}
	}

	public function current( $bug ) {

		$check = BugzillaBug::newFromQuery($this->bz, $bug);
		foreach( $check->getPatches( ) as $patch) {
			if ( $this->dateCompare( $patch['last_change_time'] ) > 0 ) {
				$this->patch[] = $patch;
			}
		}

		if( count( $this->patch ) ) {
			return $check;
		}
		return false;
	}

	public function current( )  {
		return $this->patch[$this->patchOffset];
	}

	public function key ( )  {
		return $this->patchOffset;
	}

	public function next ( ) {
		$this->nextValue();
		if($this->patchOffset < count($this->patch)) $this->patchOffset++;
	}

	public function rewind ( ) {
		parent::rewind();
		$this->patchOffset = 0;
	}

	// override this b/c patches come in odd numbers
	public function valid ( ) {
		return parent::valid();
	}
}

$bugList = array();
$iter = new BugzillaPatchIterator( $bz, $terms );
$message = "";
$count = 0 ;
foreach($iter as $patch) {
	$field = array("bug_id", "creator", "attacher", "last_change_time", "summary");
	foreach($field as $f) {
		echo "$f: $patch[$f]\n";
	}

}

