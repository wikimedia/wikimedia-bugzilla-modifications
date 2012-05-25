#!/usr/bin/php -f
<?php
define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/mw-svn";
require_once "gmaneWebClient.php";

$csv = fopen("first-bug.csv", "w");
$fp = fopen("first-bug.txt", "r");

fputcsv($csv, array("id", 'date', "subject", "reason", 'type', 'watch-reason', 'product', 'component',
		'keywords', 'severity', 'who', 'status', 'priority', 'assigned-to', 'target-milestone', 'changed-fields'));

while($f = fgets($fp)) {
	$f = chop($f);
	$match = array();
	$subject = "";
	if ( file_exists( $f ) ) {
		$bug = new GmaneMessage(file_get_contents($f));
		$subject = quoted_printable_decode( $bug->header["subject"][0] );
		if(substr($subject, 0, 15) == "=?iso-8859-1?q?") {
			$subject = str_replace(array("_"), " ", substr($subject, 15));
		}

		preg_match('#.Bug (\d*).\s*New:\s*(.*)#ms', $subject, $match);
	}
	if(isset($match[1])) {
		$b[] = $match[1];
		$b[] = $bug->header['date'][0];
		$b[] = str_replace(array(" ", "\n"), ' ', $match[2]);
		foreach($bug->header as $name => $val) {
			if( substr( $name, 0, 11 ) === "x-bugzilla-" ) {
				$head = substr( $name, 11 );
				$header[$head] = $val[0];
			}
		}
		$b[] = $header['reason'];
		$b[] = $header['type'];
		$b[] = $header['watch-reason'];
		$b[] = $header['product'];
		$b[] = $header['component'];
		$b[] = $header['keywords'];
		$b[] = $header['severity'];
		$b[] = $header['who'];
		$b[] = $header['status'];
		$b[] = $header['priority'];
		$b[] = $header['assigned-to'];
		$b[] = $header['target-milestone'];
		$b[] = $header['changed-fields'];

		fputcsv($csv, $b);
		$b = array();
	} else {
		echo "$f: $subject\n";
	}
}
