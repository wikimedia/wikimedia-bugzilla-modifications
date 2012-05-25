#!/usr/bin/php -f
<?php

define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/mw-svn"; # root of your mw installation ... we use the HTTPClient class

require_once 'mwApiClient.php';

function lastLine($file, $numLines = 1) {
	if(file_exists($file)) {
		$fp = fopen($file, "r");
		$chunk = 4096;
		$fs = sprintf("%u", filesize($file));
		$max = (intval($fs) == PHP_INT_MAX) ? PHP_INT_MAX : filesize($file);

		$data = "";
		for ($len = 0; $len < $max; $len += $chunk) {
			$seekSize = ($max - $len > $chunk) ? $chunk : $max - $len;

			fseek($fp, ($len + $seekSize) * -1, SEEK_END);
			$data = fread($fp, $seekSize) . $data;

			if (substr_count($data, "\n") >= $numLines + 1) {
				preg_match("!(.*?\n){".($numLines)."}$!", $data, $match);
				fclose($fp);
				return $match[0];
			}
		}
		fclose($fp);
		return $data;
	} else {
		return null;
	}
}

$u = parse_ini_file(getenv('HOME')."/.wikimedia.ini");
$api = new mwApiClient( $u['url'].'api.php', $u['user'], $u['password'], $u['debug']);

$ret = lastLine("chart.csv");
$f = array(null, null, null, null, null, null);
if($ret)  {
	$f = str_getcsv($ret);
}
#$iter = $api->listUploadsFrom( "BotMultichillT" );

$offset = $f[5];

$otime = null;
$fp = null;
if ( !file_exists("chart.csv") ) {
	$fp = fopen("chart.csv", "w");
	fputcsv($fp, array("pageid", "User", "Title", "Comment", "Unix Epoch", "Timestamp on file", "size", "Delta in s", "rate (size/delta)"));
} else {
	$fp = fopen("chart.csv", "a");
}

while(1) {
	try {
		$iter = $api->listAllUploads( $offset );

		foreach( $iter as $i ) {

			if ( isset( $i['imageinfo'] ) &&
				isset( $i['imageinfo'][0] ) &&
				isset( $i['imageinfo'][0]['size'] ) ) {
				$row = array($i['pageid']);
				$row[] = $i['user'];
				$row[] = $i['title'];
				$row[] = str_replace("\n", '\n', $i['comment']);
				$row[] = strtotime($i['timestamp']);
				$row[] = $i['timestamp'];
				$offset = $i['timestamp'];
				$row[] = $i['imageinfo'][0]['size'];
				$time = strtotime($i['timestamp']);
				$delta = 0;
				$rate = 0;
				if($otime) {			/* skip first response since we have no delta */
					$delta = $otime-$time;
					if( $delta !== 0 ) {
						$rate = $i['imageinfo'][0]['size']/$delta;
					} else {
						$rate = 0;
					}

					$row[] = $delta;
					$row[] = $rate;

					echo "{$row[2]}\n";
					fputcsv($fp, $row);
				}

				$otime = $time;
			} else {
				echo "Problem!\n";
				var_dump($i);
			}
		}
	} catch (Exception $e) {
		echo "Got an exception!\n";
		echo $e->getTraceAsString();
	}
}
