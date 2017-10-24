<?php

require_once "Maintenance.php";

$dbr =& wfGetDB( DB_SLAVE );
$res = $dbr->select( "page", [ "page_title" ], [ "page_namespace" => NS_PATHWAY ] );
while ( $row = $dbr->fetchRow( $res ) ) {
	try {
		$pathway = Pathway::newFromTitle( $row[0] );
		echo( $pathway->getTitleObject()->getFullText() . "\n<BR>" );
		if ( $doit ) {
					$pathway->updateCache();
		}
	} catch ( Exception $e ) {
		echo "Exception: {$e->getMessage()}<BR>\n";
	}
}
