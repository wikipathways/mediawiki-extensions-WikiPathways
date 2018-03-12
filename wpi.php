<?php
/**
 * Copyright (C) 2017  J. David Gladstone Institutes
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace WikiPathways;

use Title;

$IP = dirname( dirname( __DIR__ ) ) . "/mediawiki";
putenv( "MW_INSTALL_PATH=$IP" );

define( 'MW_NO_OUTPUT_COMPRESSION', 1 );
require "$IP/includes/WebStart.php";

/**
 * Toy class to hold these otherwise global functions
 */
class wpi {

	/**
	 * Handle the request
	 *
	 * @param array $arg that are passed via $_GET
	 */
	public static function handleRequest( $arg ) {
		if ( !isset( $arg['action'] ) ) {
			throw new Exception( "No action given!" );
		}
		$action = $arg['action'];
		if ( !isset( $arg['pwTitle'] ) ) {
			throw new Exception( "No pwTitle given!" );
		}
		$pwTitle = $arg['pwTitle'];
		if ( !isset( $arg['oldid'] ) && $action !== "downloadFile" &&
			 $action !== "delete" ) {
			throw new Exception( "No oldId given!" );
		}
		$oldId = $arg['oldid'];

		switch ( $action ) {
		case 'launchCytoscape':
			self::launchCytoscape( self::createPathwayObject( $pwTitle, $oldId ) );
			break;
		case 'launchGenMappConverter':
			self::launchGenMappConverter( self::createPathwayObject( $pwTitle, $oldId ) );
			break;
		case 'downloadFile':
			if ( !isset( $arg['type'] ) ) {
				throw new Exception( "No type given!" );
			}
			self::downloadFile( $arg['type'], $pwTitle );
			break;
		case 'revert':
			self::revert( $pwTitle, $oldId );
			break;
		case 'delete':
			self::pwDelete( $pwTitle );
			break;
		default:
			throw new Exception( "'$action' isn't implemented" );
		}
	}

	/**
	 * Utility function to import the required javascript for the xref panel
	 * @param Title $pwTitle the pathway
	 * @param int $oldid the version
	 * @return Pathway
	 */
	public static function createPathwayObject( Title $pwTitle, $oldid ) {
		$pathway = Pathway::newFromTitle( $pwTitle );
		if ( $oldId ) {
			$pathway->setActiveRevision( $oldId );
		}
		return $pathway;
	}

	/**
	 * Delete a pathway
	 *
	 * @param string $title to delete
	 */
	public static function pwDelete( $title ) {
		global $wgUser, $wgOut;
		$pathway = Pathway::newFromTitle( $title );
		if ( $wgUser->isAllowed( 'delete' ) ) {
			$pathway = Pathway::newFromTitle( $title );
			$pathway->delete();
			echo "<h1>Deleted</h1>";
			echo "<p>Pathway $title was deleted, return to <a href='"
				. SITE_URL . "'>wikipathways</a>";
		} else {
			echo "<h1>Error</h1>";
			echo "<p>Pathway $title is not deleted, you have no delete permissions</a>";
			$wgOut->permissionRequired( 'delete' );
		}
	}

	/**
	 * Revert a revision
	 *
	 * @param Title $pwTitle to revert
	 * @param int $oldId revision # to revert
	 */
	public static function revert( $pwTitle, $oldId ) {
		$pathway = Pathway::newFromTitle( Title::newFromText( $pwTitle ) );
		$pathway->revert( $oldId );
		// Redirect to old page
		$url = $pathway->getTitleObject()->getFullURL();
		header( "Location: $url" );
		exit;
	}

	/**
	 * Launch the GenMapp converter
	 *
	 * @param Pathway $pathway object
	 */
	public static function launchGenMappConverter( Pathway $pathway ) {
		$webstart = file_get_contents( WPI_SCRIPT_PATH . "/applet/genmapp.jnlp" );
		$pwUrl = $pathway->getFileURL( FILETYPE_GPML );
		$pwName = substr( $pathway->getFileName( '' ), 0, -1 );
		$arg = "<argument>" . htmlspecialchars( $pwUrl ) . "</argument>";
		$arg .= "<argument>" . htmlspecialchars( $pwName ) . "</argument>";
		$webstart = str_replace( "<!--ARG-->", $arg, $webstart );
		$webstart = str_replace( "CODE_BASE", WPI_URL . "/applet/", $webstart );

		// This exits script
		self::sendWebstart( $webstart, $pathway->name(), "genmapp.jnlp" );
	}

	/**
	 * Launch Cytoscape
	 *
	 * @param Pathway $pathway object
	 */
	public static function launchCytoscape( Pathway $pathway ) {
		$webstart = file_get_contents( WPI_SCRIPT_PATH . "/bin/cytoscape/cy1.jnlp" );
		$arg = self::createJnlpArg( "-N", $pathway->getFileURL( FILETYPE_GPML ) );
		$webstart = str_replace( " <!--ARG-->", $arg, $webstart );
		$webstart = str_replace( "CODE_BASE", WPI_URL . "/bin/cytoscape/", $webstart );

		// This exits script
		self::sendWebstart( $webstart, $pathway->name(), "cytoscape.jnlp" );
	}

	/**
	 * Send some JNLP bits and quit
	 *
	 * @param string $webstart to bootstrap
	 * @param string $tmpname of pathway
	 * @param string $filename of jnlp
	 */
	public static function sendWebstart(
		$webstart, $tmpname, $filename = "wikipathways.jnlp"
	) {
		ob_start();
		ob_clean();
		// return webstart file directly
		header( "Content-type: application/x-java-jnlp-file" );
		header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		header( "Content-Disposition: attachment; filename=\"{$filename}\"" );
		echo $webstart;
		exit;
	}

	/**
	 * Return a JNLP argument
	 *
	 * @param string $flag first arg
	 * @param string $value second arg
	 * @return string
	 */
	public static function createJnlpArg( $flag, $value ) {
		if ( !$flag || !$value ) {
			return '';
		}
		return "<argument>" . htmlspecialchars( $flag ) . "</argument>\n<argument>"
							. htmlspecialchars( $value ) . "</argument>\n";
	}

	/**
	 * Perform the file download action
	 *
	 * @param string $fileType we want to download
	 * @param Title $pwTitle of file
	 */
	public static function downloadFile( $fileType, $pwTitle ) {
		$pathway = Pathway::newFromTitle( $pwTitle );
		if ( !$pathway->isReadable() ) {
			throw new Exception( "You don't have permissions to view this pathway" );
		}

		if ( $fileType === 'mapp' ) {
			self::launchGenMappConverter( $pathway );
		}
		ob_start();
		$oldid = $_REQUEST['oldid'];
		if ( $oldid ) {
			$pathway->setActiveRevision( $oldid );
		}
		// Register file type for caching
		Pathway::registerFileType( $fileType );

		$file = $pathway->getFileLocation( $fileType );
		$fn = $pathway->getFileName( $fileType );
		$mime = MimeTypes::getMimeType( $fileType );
		if ( !$mime ) {
			$mime = "text/plain";
		}

		ob_clean();
		header( "Content-type: $mime" );
		header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		header( "Content-Disposition: attachment; filename=\"$fn\"" );
		// header("Content-Length: " . filesize($file));
		set_time_limit( 0 );
		@readfile( $file );
		exit();
	}
}

wpi::handleRequest( $_GET );
