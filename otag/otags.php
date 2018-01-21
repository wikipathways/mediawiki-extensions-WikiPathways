<?php
error_reporting( E_ALL & ~E_DEPRECATED );
ini_set( 'display_errors', 1 );
define( 'MW_NO_OUTPUT_COMPRESSION', 1 );
require_once getenv( "MW_INSTALL_PATH" ) . '/includes/WebStart.php';

require_once 'OntologyFunctions.php';

// Reminder: anywhere you see ??, it is php7+
$title = $title ?? null;
$tagId = $_POST['tagId'] ?? null;
$gTagId = $_GET['tagId'] ?? null;
$tag = $_POST['tag'] ?? null;
$searchTerm = $_GET['searchTerm'] ?? null;
$action = $_REQUEST['action'] ?? null;

switch ( $action ) {
	case 'remove' :
		echo OntologyFunctions::removeOntologyTag( $tagId, $title );
		break;

	case 'add' :
		echo OntologyFunctions::addOntologyTag( $tagId, $tag, $title );
		break;

	case 'search' :
		echo OntologyFunctions::getBioPortalSearchResults( $searchTerm );
		break;

	case 'fetch' :
		echo OntologyFunctions::getOntologyTags( $title );
		break;

	case 'tree' :
		echo OntologyFunctions::getBioPortalTreeResults( $gTagId );
		break;
}
