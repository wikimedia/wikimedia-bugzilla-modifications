#!/usr/bin/php -r
<?php

define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/mw-svn"; # root of your mw installation ... we use the HTTPClient class

require_once 'gmaneWebClient.php';
$u = parse_ini_file(getenv('HOME')."/.bugzilla.ini");
$gmane = new GmaneWebClient( );
if( file_exists("count") ) {
	$start = file_get_contents("count") - 100;
} else {
	$start = 0;
}

$iter = $gmane->getMbox( $u['group'], 100, $start );

$count = $start;
$out = "";
$len = 0;
$bad = 0;
$skip = 10; # print a nl every 10th item
foreach( $iter as $mail ) {
	$count++;
	if( preg_match( "/bugzilla.wikimedia.org/", $mail->body ) ) {
		/* echo "\nCount: $count\n"; */
		/* echo $mail->header["subject"][0], "\n"; */
		/* echo $mail->header["from"][0], "\n"; */
		/* echo $mail->header["to"][0], "\n\n"; */
		/* echo $mail->body; */
		$dir = "bugzilla-mail";

		$md5 = md5($mail->raw);
		$old_subdir = substr($md5, 0, 2);
		$subdir = substr($md5, 0, 2) . "/". substr($md5, 2, 2);
		if( !file_exists( "$dir/$subdir" ) ) {
			mkdir( "$dir/$subdir", 0755, true );
		}

		$m = trim( $mail->header["message-id"][0], "<>/" );
		$out = "$count: $m";
		if( file_exists( "$dir/$old_subdir/$m" ) ) {
			if( !rename( "$dir/$old_subdir/$m", "$dir/$subdir/$m" ) ) {
				echo "\nfailure moving $dir/$old_subdir/$m\n";
				exit;
			}
			$out = "moving from one deep: $out";
		} else if( file_exists( "$dir/$m" ) ) {
			if( !rename( "$dir/$m", "$dir/$subdir/$m" ) ) {
				echo "\nfailure moving $dir/$m\n";
				exit;
			}
			$out = "moving: $out";
		} else if( ! file_exists( "$dir/$subdir/$m" ) ) {
			file_put_contents( "$dir/$subdir/$m", $mail->raw );
			file_put_contents( "count", $count );
		} else {
			$out = "already got it: $out";
		}

		if( $count % $skip == 0 ) {
			echo "\n";
		} else {
			echo "\r". str_repeat( " ", $len ). "\r";
		}
		echo $out;
		$len = strlen($out);
	}
}
