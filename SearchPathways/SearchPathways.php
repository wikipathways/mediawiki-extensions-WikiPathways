<?php

$wgAutoloadClasses['SearchPathways'] = __DIR__ . '/SearchPathways_body.php';
$wgAutoloadClasses['SearchPathwaysAjax'] = __DIR__ . '/SearchPathwaysAjax.php';
$wgSpecialPages['SearchPathways'] = 'SearchPathways';
$wgExtensionMessagesFiles['SearchPathways'] = __DIR__ . '/SearchPathways.i18n.php';
$wfSearchPagePath = WPI_URL . "/extensions/SearchPathways";
$wgAjaxExportList[] = "SearchPathwaysAjax::doSearch";
$wgAjaxExportList[] = "SearchPathwaysAjax::getResults";
