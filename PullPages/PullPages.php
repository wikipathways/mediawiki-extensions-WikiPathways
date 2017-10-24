<?php

$pullSite = "http://test.wikipathways.org/index.php";
$pullPages = "MediaWiki:PagesToPull";

# credit the extension
$wgExtensionCredits['other'][] = [
	'name' => 'PullPages',
	'url' => 'http://www.mediawiki.org/wiki/Extension:PullPages',
	'author' => '[[User:MarkAHershberger Mark A. Hershberger]]',
	'description' => 'Pull a selected list of on wiki pages from another wiki',
];

$wgSpecialPages['PullPages'] = "PullPages";
$wgSpecialPageGroups['PullPages'] = "pagetools";
$wgGroupPermissions['sysop']['pullpage'] = true;
$wgAvailableRights[] = 'pullpage';

$wgExtensionMessagesFiles['PullPages'] = __DIR__ . '/PullPages.i18n.php';
$wgAutoloadClasses['PullPages'] = __DIR__ . '/PullPages_class.php';
$wgAutoloadClasses['PagePuller'] = __DIR__ . '/PagePuller.php';
$wgExtensionFunctions[] = 'PullPages::initMsg';
