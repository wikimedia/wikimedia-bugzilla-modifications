#!/usr/bin/php
<?php

define('MEDIAWIKI', true);
$IP = "/home/mah/work/code/mediawiki/mw-svn"; # root of your mw installation ... we use the HTTPClient class

require_once 'bugzilla.php';
$u = parse_ini_file(getenv('HOME')."/.bugzilla.ini");
$bz = new BugzillaWebClient( $u['url'].'/jsonrpc.cgi', $u['email'], $u['password'], $u['debug']);
#$iter = new BugzillaSearchIterator( $bz, array( 'id' => $id ) );

$terms = array(
	"component" => array(
		# From Mobile:
		'android', 'iphone', 'server',

		# From MediaWiki:
		'API', 'Blocking', 'Categories', 'Change Tagging', 'Database', 'Deleting', 'Device compatibility', 'DjVu',
		'Documentation', 'Email', 'Export/Import', 'General/Unknown', 'History/Diffs', 'Images and files', 'Installation',
		'Internationalization', 'Javascript', 'Language converter', 'Maintenance scripts', 'Modern skin', 'Page editing',
		'Page protection', 'Page rendering', 'Recent changes', 'Redirects', 'Resource Loader', 'Revision deletion', 'Search',
		'Special pages', 'Syndication', 'Templates', 'Unit tests', 'Uploading', 'User interface', 'User login', 'User preferences',
		'Vector Skin', 'Watchlist',

		# From WMF extensions
		"AbuseFilter",
		"AntiSpoof",
		"ArticleAssessmentPilot",
		"ArticleFeedback",
		"CategoryTree",
		"CentralAuth",
		"CentralNotice",
		"CharInsert",
		"CheckUser",
		"Cite",
		"ClickTracking",
		"ClientSide",
		"CodeReview",
		"Collection",
		"CommunityVoice",
		"ConfirmEdit",
		"ContactPage",
		"ContributionTracking",
		"DismissableSiteNotice",
		"Donation","Form",
		"DynamicPageList",
		"FundraiserPortal",
		"Gadgets",
		"GlobalBlocking",
		"GlobalUsage",
		"ImageMap",
		"Inputbox",
		"Installation",
		"Internationalization",
		"LabeledSectionTransclusion",
		"LandingCheck",
		"LiquidThreads",
		"LocalisationUpdate",
		"Lucene Search",
		"Narayam",
		"NewUserMessage",
		"Nuke",
		"OAI",
		"OggHandler",
		"Oversight",
		"PagedTiffHandler",
		"ParserFunctions",
		"PdfHandler",
		"PhotoCommons",
		"Poem",
		"PrefStats",
		"PrefSwitch",
		"PrefSwitch",
		"ProofreadPage",
		"Quiz",
		"RSS",
		"ReaderFeedback",
		"Renameuser",
		"SecurePoll",
		"SiteMatrix",
		"Spam Blacklist",
		"Syndication",
		"SyntaxHighlight (GeSHi)",
		"TitleBlacklist",
		"TitleKey",
		"TorBlock",
		"UploadWizard",
		"UploadWizard",
		"UsabilityInitiative",
		"Use","SpecialCite",
		"UserDailyContribs",
		"UsernameBlacklist",
		"VariablePage",
		"Vector",
		"WikiEditor",
		"WikiHiero",
		"WikimediaMobile",
		"[other]",

		# From Security
		"General",

		# From Wikimedia Tools
		'PhotoCommons', 'WikiSnaps',

		# From Wikipedia
		# 'AcaWiki', 'Bugzilla',
		'DNS', 'Downloads', 'Extension setup', 'Fundraising Requirements',
		'Interwiki links', 'IRC', 'Language setup', 'lucene-search-2', 'Mailing lists', 'OTRS', 'Prototype server',
		#'Site logos', 'Site requests',
		'SSL related', 'Subversion', 'Usage Statistics', 'User survey', 'WAP mobile gateway',
		#'wikibugs IRC bot'
	),
	"product" => array("MediaWiki", "MediaWiki extensions", "Security", "Wikimedia", "Wikimedia Tools", "Wikipedia Mobile"),
	"severity" => array("blocker", "critical", "major", "normal", "minor", "trivial"),
	"status" => array("UNCONFIRMED", "NEW", "ASSIGNED", "REOPENED"),
	"priority" => array("Highest", "High", "Normal"),
);

$bugList = array();
$iter = new BugzillaSearchIterator( $bz, $terms );
foreach($iter as $bug) {
	$bugList[$bug->getAssignee()][] = $bug;
}

foreach($bugList as $assignee => $bugs) {
#	$message = "$assignee\n";
	$message = "";
	foreach($bugs as $bug) {
		$message .= " http://bugzilla.wikimedia.org/{$bug->getID()}\n";
#		$message .= "   {$bug->getPriorityText()} {$bug->getStatus()} {$bug->getSummary()}\n";
	}
	echo $message;
}
