#!/usr/bin/php
<?php

define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/mw-svn"; # root of your mw installation ... we use the HTTPClient class

foreach(getFixmes() as $author => $revs) {
	sendMail($author, $revs);
}

function getFixmes() {
	ini_set("user_agent", "hexmode's FIXME mailer");
	$page = file_get_contents("http://www.mediawiki.org/wiki/Special:Code/MediaWiki/status/fixme?limit=100");

	$fixes = explode("<tr class=\"mw-codereview-status-fixme\">\n", $page);
	array_shift($fixes);			/* We don't care about what comes before the table of FIXMEs */

	$bit = array();
	foreach($fixes as $fix) {
		$f = explode("</td>\n", $fix);
		$r = array();
		preg_match("/>([0-9]+)</", $f[0], $r);
		$rev = $r[1];
		preg_match('/class="TablePager_col_cr_message">(.*)/', $f[4], $r);
		$msg = preg_replace("/<[^>]*>/", "", html_entity_decode($r[1]));
		preg_match('/class="TablePager_col_cr_author.*author=([^"]+)"/', $f[5], $r);
		$author = $r[1];

		$bit[$author][$rev] = $msg;
	}

	return $bit;
}

function getUserinfo( $author ) {
	$ui = file_get_contents("http://svn.wikimedia.org/svnroot/mediawiki/USERINFO/$author");
	$ret = array();
	foreach(explode("\n", $ui) as $l) {
		if($l != "") {
			list($name, $data) = explode(":", $l, 2);
			$data = trim($data);
			$name = trim($name);
			if($name == "email") {
				$data = preg_replace("/ .?dot.? /i", '.',
					preg_replace("/ .?at.? /i", '@',
						preg_replace("/ who is a user at the host called /i", '@', $data)));
			}
			$ret[$name] = $data;
		}
	}

	if(!isset($ret['name'])) {
		$ret['name'] = $author;
	}

	return $ret;
}

function sendMail($author, $revs) {
	static $template;

	if ($template == null) {
		$template = file_get_contents("template.txt");
	}
	$user = getUserinfo($author);

	$commits = " Rev #: Commit message\n";
	foreach($revs as $r => $msg) {
		$commits .= "r{$r}: $msg\n";
	}

	$msg = sprintf($template, $user['name'], $author, $commits);

	if( !isset($user['email']) || stristr( $user['email'], '@' ) !== false ) {
		echo "Please send a message to $author:\n$commits";
	} else {
		#mail( $user['email'], "Please fix your FIXMEs", $commits, false, "-f mhershberger@wikimedia.org"
	}
}