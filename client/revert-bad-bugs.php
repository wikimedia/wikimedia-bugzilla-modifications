#!/usr/bin/php
<?php

define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/mw-svn"; # root of your mw installation ... we use the HTTPClient class

require_once 'bugzilla.php';
$u = parse_ini_file(getenv('HOME')."/.bugzilla.ini");
$bz = new BugzillaWebClient( $u['url'].'/jsonrpc.cgi', $u['email'], $u['password'], $u['debug']);

$terms = array(
	"component" => array("Generic"),
	"product" => array("Wikipedia App"),
);
$iter = new BugzillaSearchIterator( $bz, $terms );

$found = array();
foreach($iter as $bug) {
	foreach( $bug->getHistory() as $message ) {
		$message = $message[0]['history'];

		$not_found = true;
		$action = array_pop( $message );
		while( $not_found && $action ) {

			if( $action['who']                      === 'mah@everybody.org' &&
				substr( $action['when'], 0, 10 )    === '2012-02-17' ) {

				foreach( $action['changes'] as $c ) {
					if( $c['removed']    === 'generic' &&
						$c['added']      === 'Wikimedia'   &&
						$c['field_name'] === 'component' ) {
						$not_found = false;
					}
				}
			}

			$action = array_pop( $message );
		}

		if( ! $not_found ) {
			$found[] = $bug->getID();
			echo "*** {$bug->getID()}: {$bug->getSummary()}\n";
		}
	}
}
echo implode( ",", $found ), "\n";
