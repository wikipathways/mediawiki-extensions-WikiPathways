<?php

class ontologyindex extends SpecialPage {
	public static $ONTOLOGY_TABLE = "ontology";

	function __construct() {
		parent::__construct( 'ontologyindex' );
	}

	function execute( $par ) {
		global $wgRequest, $wgOut;
		$this->setHeaders();
		$this->init();
	}

	function init() {
		global $wgOut, $wgRequest, $wgOntologiesJSON, $wgStylePath;
		$mode = $wgRequest->getVal( 'mode' );
		$mode = ( $mode == "" ) ? "list" : $mode;

		$oldStylePath = $wgStylePath;
		$wgOut->addStyle( "otagindex.css" );
		// Update with resourceloader if used
		// $wgOut->addScript( '<script type="text/javascript" src="' . $opath . '/yui.js"></script>' );
		$wgOut->addHTML( "<div id='index_container'></div>" );
		// $wgOut->addScript(
		// 	"<script type='text/javascript'>var opath=\"$opath\";
		// 	var page_mode = \"$mode\";
		// 	var ontologiesJSON = '$wgOntologiesJSON';
		// 	</script>"
		// );

		$wgStylePath = $oldStylePath;

		// $wgOut->addScript( "<script type='text/javascript' src='$opath/ontologyindex.js'></script>" );
	}
}
