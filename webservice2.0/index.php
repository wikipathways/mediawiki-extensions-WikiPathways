<?php

error_reporting( E_ALL & ~E_DEPRECATED );
ini_set( 'display_errors', 1 );

$IP = getenv( "MW_INSTALL_PATH" );

define( 'MW_NO_OUTPUT_COMPRESSION', 1 );
require "$IP/includes/WebStart.php";

$_wservices['listOrganisms'] = [
	"metatags" => [ "Organism list", "All functions" ],
];

$_wservices['listPathways'] = [
	"metatags" => [ "Pathway list", "All functions" ],
];

$_wservices['getPathway'] = [
	"metatags" => [ "Pathway information", "All functions" ],
];

/* Array(
		'fieldDescription' => Array(
										'pwId' => 'Whatever you want to say',
										'revision' => 'Whatever you want to say 2'
								)
);*/

$_wservices["getPathwayInfo"] = [
	"description" => "Get some general info about the pathway, such as the name, species, without downloading the GPML.",
	"metatags" => [ "Pathway information","All functions" ],
];

$_wservices["getPathwayHistory"] = [
	"description" => "Get the revision history of a pathway.",
	"metatags" => [ "History", "All functions" ],
];

$_wservices["getRecentChanges"] = [
	"description" => "Get the recently changed pathways.<br>Note: the recent changes table only retains items for a limited time (2 months), so there is no guarantee that you will get all changes when the timestamp points to a date that is more than 2 months in the past.",
	"metatags" => [ "History","All functions" ],
];

;
$_wservices["login"] = [
	"description" => "Start a logged in session, using an existing WikiPathways account. This function will return an authentication code that can be used to excecute methods that need authentication (e.g. updatePathway).",
	"metatags" => [ "User management", "All functions" ],
];

$_wservices["getPathwayAs"] = [
	"description" => "Download a pathway in the specified file format.",
	"metatags" => [ "Download", "All functions" ],
];

$_wservices["updatePathway"] = [
	"description" => "Update a pathway on the wiki with the given GPML code.<br>Note: To create/modify pathways via the web service, you need to have an account with web service write permissions. Please contact us to request write access for the web service.",
	"metatags" => [ "Write (create/update/delete)", "All functions" ],
];

$_wservices["createPathway"] = [
	"description" => "Create a new pathway on the wiki with the given GPML code.<br>Note: To create/modify pathways via the web service, you need to have an account with web service write permissions. Please contact us to request write access for the web service.",
	"method" => 'post',
	"metatags" => [ "All functions", "Write (create/update/delete)" ],
];
/*Array(
  'method'=>'post',
  'fieldtype'=>Array("gpml"=>"textarea")
  );*/

$_wservices["findPathwaysByText"] = [
	"metatags" => [ "All functions", "Search" ]
];

$_wservices["findPathwaysByXref"] = [
	"description" => "",
	'fieldtype' => [ "ids" => "array","codes" => "array" ],
	"metatags" => [ "All functions", "Search" ]
];

$_wservices["removeCurationTag"] = [
	"description" => "Remove a curation tag from a pathway.",
	"metatags" => [ "All functions", "Search" ]
];

$_wservices["saveCurationTag"] = [
	"metatags" => [ "All functions", "Write (create/update/delete)", "Curation tags" ]
];

$_wservices["getCurationTags"] = [
	"description" => "Get all curation tags for the given tag name. Use this method if you want to find all pathways that are tagged with a specific curation tag.",
	'fieldtype' => [ "pwId" => "string" ],
	'fieldexample' => [ "pwId" => "WP4" ],
	"metatags" => [ "All functions", 'Pathway information', 'Curation tags' ]
];

$_wservices["getCurationTagsByName"] = [
	"description" => "Get all curation tags for the given tag name. Use this method if you want to find all pathways that are tagged with a specific curation tag.",
	"metatags" => [ "All functions", "Pathway list", "Curation tags" ],
];

$_wservices["getCurationTagHistory"] = [
	"description" => "",
	"metatags" => [ "All functions", 'History', 'Curation tags' ]
];

$_wservices["getColoredPathway"] = [
	"description" => "Get a colored image version of the pathway.",
	"fieldtype" => [ "graphId" => "array","color" => "array" ],
	"metatags" => [ "All functions", "Download" ]
];

$_wservices["findInteractions"] = [
	"description" => "Find interactions defined in WikiPathways pathways.",
	"metatags" => [ "Search","All functions" ],
];

$_wservices["getXrefList"] = [
	"metatags" => [ "Download", "All functions" ],
];
$_wservices["findPathwaysByLiterature"] = [
	"metatags" => [ "Search", "All functions" ],
];
$_wservices["saveOntologyTag"] = [
	"metatags" => [ "Write (create/update/delete)", "Ontology tags", "All functions" ],
];
$_wservices["removeOntologyTag"] = [
	"metatags" => [ "Write (create/update/delete)", "Ontology tags", "All functions" ],
];
$_wservices["getOntologyTermsByPathway"] = [
	"metatags" => [ "Pathway information", "Curation tags", "All functions" ],
];
// $_wservices["getOntologyTermsByOntology"] = '';
$_wservices["getPathwaysByOntologyTerm"] = [
	"metatags" => [ "Pathway list", "Ontology tags", "All functions" ],
];

$_wservices["getPathwaysByParentOntologyTerm"] = [
	"metatags" => [ "Pathway list", "Ontology tags", "All functions" ],
];
$_wservices["getUserByOrcid"] = [
	"metatags" => [ "User management", "All functions" ],
];

$ws = new WikiPathways\WebService\BC( $_wservices );
$ws->setExceptionHandler(
	function ( $except ){
		header("HTTP/1.1 500 Internal Server Error");

		return [ "error", $except->getCode(), $except->getMessage() ];
	}
);
$ws->listen();
