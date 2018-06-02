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

use Exception;
use MWException;
use RequestContext;
use Title;
use WikiPathways\PathwayCache\Factory;

$IP = dirname( dirname( __DIR__ ) ) . "/mediawiki";
putenv( "MW_INSTALL_PATH=$IP" );

define( 'MW_NO_OUTPUT_COMPRESSION', 1 );
require "$IP/includes/WebStart.php";

/**
 * Toy class to hold these otherwise global functions
 */
class wpi {

	private static $req;

	private static function getAction() {
		$action = self::$req->getVal( 'action' );
		if ( !$action ) {
			throw new Exception( "No action given!" );
		}
		return $action;
	}

	private static function getPWTitle() {
		$pwTitle = self::$req->getVal( 'pwTitle' );
		if ( !$pwTitle ) {
			throw new Exception( "No pwTitle given!" );
		}
		$pwTitle = Title::newFromText( $pwTitle, NS_PATHWAY );
		if ( !$pwTitle ) {
			throw new Exception( "Invalid title given!" );
		}
		return $pwTitle;
	}

	private static function getOldID() {
		$oldid = self::$req->getVal( 'oldid' );
		if ( !$oldid && $action !== "downloadFile" &&
			 $action !== "delete" ) {
			throw new Exception( "No oldId given!" );
		}
		return $oldid;
	}

	private static function getType() {
		$type = self::$req->getVal( 'type' );
		if ( !$type ) {
			throw new Exception( "No type given!" );
		}
		return $type;
	}

	/**
	 * Handle the request
	 */
	public static function handleRequest() {
		self::$req = RequestContext::getMain()->getRequest();
		$action = self::getAction();
		$pwTitle = self::getPWTitle();
		$oldId = self::getOldId();

		switch ( $action ) {
		case 'launchCytoscape':
			self::launchCytoscape( self::createPathwayObject( $pwTitle, $oldId ) );
			break;
		case 'launchGenMappConverter':
			self::launchGenMappConverter( self::createPathwayObject( $pwTitle, $oldId ) );
			break;
		case 'downloadFile':
			$type = self::getType();
			self::downloadFile( $type, $pwTitle );
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
	 * @param int $oldId the version
	 * @return Pathway
	 */
	public static function createPathwayObject( Title $pwTitle, $oldId ) {
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
		self::sendWebstart( $webstart, "genmapp.jnlp" );
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
		self::sendWebstart( $webstart, "cytoscape.jnlp" );
	}

	/**
	 * Send some JNLP bits and quit
	 *
	 * @param string $webstart to bootstrap
	 * @param string $filename of jnlp
	 */
	public static function sendWebstart(
		$webstart, $filename = "wikipathways.jnlp"
	) {
		ob_start();
		ob_clean();
		// return webstart file directly
		header( "Content-type: application/x-java-jnlp-file" );
		header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		header( "Content-Disposition: attachment; filename=\"{$filename}\"" );
		echo $webstart;
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
	public static function downloadFile( $fileType, Title $pwTitle ) {
		$pathway = Pathway::newFromTitle( $pwTitle );
		if ( !$pathway->isReadable() ) {
			throw new Exception( "You don't have permissions to view this pathway" );
		}

		if ( $fileType === 'mapp' ) {
			self::launchGenMappConverter( $pathway );
		}
		ob_start();
		$oldid = self::$req->getVal( 'oldid' );
		if ( $oldid ) {
			$pathway->setActiveRevision( $oldid );
		}
		$file = Factory::getCache( $fileType, $pathway );
		$mime = MimeTypes::getMimeType( $fileType );
		if ( !$mime ) {
			$mime = "text/plain";
		}

		if ( $file->isCached() ) {
			ob_clean();
			header( "Content-type: $mime" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( sprintf( 'Content-Disposition: attachment; filename="%s"', $file->getName() ) );
			// header("Content-Length: " . filesize($file));
			set_time_limit( 0 );

			echo $file->fetchText();
		} else {
			throw new MWException( "Couldn't generate file" );
		}
	}
}

wpi::handleRequest();
