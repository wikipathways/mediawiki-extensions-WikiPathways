<?php

// To be called directly
if ( realpath( $_SERVER['SCRIPT_FILENAME'] ) == realpath( __FILE__ ) ) {
	wfDebug( "PROCESSING BATCH DOWNLOAD\n" );
	$species      = isset( $_GET['species'] ) ? $_GET['species'] : "";
	$fileType     = isset( $_GET['fileType'] ) ? $_GET['fileType'] : "";
	$listPage     = isset( $_GET['listPage'] ) ? $_GET['listPage'] : "";
	$tag          = isset( $_GET['tag'] ) ? $_GET['tag'] : "";
	$excludeTags  = isset( $_GET['tag_excl'] ) ? $_GET['tag_excl'] : "";
	$displayStats = isset( $_GET['stats'] ) ? $_GET['stats'] : "";

	if ( $species ) {
		try {
			$batch = new BatchDownloader(
				$species, $fileType, $listPage, $tag, split( ';', $excludeTags ), $displayStats
			);
			$batch->download();
		} catch ( Exception $e ) {
			ob_clean();
			header( "Location: " . SITE_URL . "/index.php?title=Special:ShowError&error=" . urlencode( $e->getMessage() ) );
			exit;
		}
	}
}
