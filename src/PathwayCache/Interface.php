<?php

/**
 * Copyright (C) 2018  J. David Gladstone Institutes
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
 *
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways;

use FileCacheBase;
use WikiPathways\GPML\Converter;

class PathwayCache extends FileCacheBase {
	private $pvjson;
	private $pathway;
	private $converter;

	public function __construct() {
		parent::__construct()
		global $wpiFileCache;
		$this->mType = $wpiFileCache;
	}

	/**
	 * @param
	 */
	public function getConverter() {
		if ( !$this->converter ) {
			$this->converter = new Converter( $this->pathway->getId() );
		}
		return $this->converter;
	}

	/**
	 * @param Pathway $pathway to get cache object for
	 * @return PathwayCache
	 */
	public static function newFromPathway( Pathway $pathway ) {
		$cache = new self();
		$cache->pathway = $pathway;
		return $cache;
	}

	/**
	 * Checks whether the cached files are up-to-data and updates them
	 * if neccesary
	 * @param string $fileType The file type to check the cache for (one of
	 * FILETYPE_* constants) or null to check all files
	 */
	public function updateCache( $fileType = null ) {
		wfDebugLog( "Pathway",  "updateCache called for filetype $fileType\n" );
		// Make sure to update GPML cache first
		if ( $fileType !== FILETYPE_GPML ) {
			$this->updateCache( FILETYPE_GPML );
		}

		if ( $fileType === null ) {
			// Update all
			foreach ( self::$fileTypes as $type ) {
				$this->updateCache( $type );
			}
			return;
		}
		if ( $this->isOutOfDate( $fileType ) ) {
			wfDebugLog( "Pathway",  "\t->Updating cached file for $fileType\n" );
			switch ( $fileType ) {
				case FILETYPE_GPML:
					$this->saveGpmlCache();
					break;
				case FILETYPE_JSON:
					$this->savePvjsonCache();
					break;
				case FILETYPE_IMG:
					$this->saveSvgCache();
					break;
				case FILETYPE_PDF:
					$this->savePdfCache();
					break;
				case FILETYPE_PWF:
					$this->savePwfCache();
					break;
				case FILETYPE_BIOPAX:
					$this->saveOwlCache();
					break;
			}
		}
	}

	private function getFileLocation( $type ) {
		return $this->cachePath() . "." . $type;
	}

	/**
	 * Get the JSON for this pathway, as a string (the active revision will be
	 * used, see Pathway::getActiveRevision)
	 * Gets the JSON representation of the GPML code,
	 * formatted to match the structure of SVG,
	 * as a string.
	 * TODO: we aren't caching this
	 */
	public function getPvjson() {
		wfDebugLog( "Pathway",  "getPvjson() called\n" );

		if ( isset( $this->pvjson ) ) {
			wfDebugLog( "Pathway",  "Returning pvjson from memory\n" );
			return $this->pvjson;
		}

		$file = $this->getFileLocation( FILETYPE_JSON );
		if ( $file && file_exists( $file ) ) {
			wfDebugLog( "Pathway",  "Returning pvjson from cache $file\n" );
			return file_get_contents( $file );
		}

		$gpmlPath = $this->getFileLocation( FILETYPE_GPML );
		$identifier = $this->pathway->getId();
		$version = $this->pathway->getActiveRevision();
		$organism = $this->pathway->getSpecies();

		if ( !$gpmlPath ) {
			error_log( "No file for GPML!" );
			return null;
		}

		$this->pvjson = $this->getConverter()->gpml2pvjson(
			$this->fetchText(),
			[ "identifier" => $identifier,
			  "version" => $version,
			  "organism" => $organism ]
		);
		if ( $this->pvjson ) {
			wfDebugLog( "Pathway",  "Converted gpml to pvjson\n" );
			$this->savePvjsonCache();
			return $this->pvjson;
		}
		$err = error_get_last();
		error_log( "Trouble converting $gpmlPath: {$err['message']}" );
		return null;
	}

	/**
	 * Get the PDF for this pathway, as a string
	 */
	public function getPdf() {
		wfDebugLog( "Pathway",  "getPdf() called\n" );

		if ( isset( $this->pdf ) ) {
			wfDebugLog( "Pathway",  "Returning pdf from memory\n" );
			return $this->pdf;
		}

		$file = $this->getFileLocation( FILETYPE_PDF );
		if ( $file && file_exists( $file ) ) {
			wfDebugLog( "Pathway",  "Returning pdf from cache $file\n" );
			return file_get_contents( $file );
		}

		$gpmlPath = $this->getFileLocation( FILETYPE_GPML );
		$identifier = $this->pathway->getId();
		$version = $this->pathway->getActiveRevision();
		$organism = $this->pathway->getSpecies();

		if ( !$gpmlPath ) {
			error_log( "No file for GPML!" );
			return null;
		}

		$this->pdf = $this->getConverter()->getgpml2pdf(
			$this->fetchText(),
			[]
		);
		if ( $this->pdf ) {
			wfDebugLog( "Pathway",  "Converted gpml to pdf\n" );
			$this->savePdfCache();
			return $this->pdf;
		}
		$err = error_get_last();
		error_log( "Trouble converting $gpmlPath: {$err['message']}" );
		return null;
	}

	/**
	 * Get the PWF for this pathway, as a string
	 */
	public function getPwf() {
		wfDebugLog( "Pathway",  "getPwf() called\n" );

		if ( isset( $this->pwf ) ) {
			wfDebugLog( "Pathway",  "Returning pwf from memory\n" );
			return $this->pwf;
		}

		$file = $this->getFileLocation( FILETYPE_PWF );
		if ( $file && file_exists( $file ) ) {
			wfDebugLog( "Pathway",  "Returning pwf from cache $file\n" );
			return file_get_contents( $file );
		}

		$gpmlPath = $this->getFileLocation( FILETYPE_GPML );
		$identifier = $this->pathway->getId();
		$version = $this->pathway->getActiveRevision();
		$organism = $this->pathway->getSpecies();

		if ( !$gpmlPath ) {
			error_log( "No file for GPML!" );
			return null;
		}

		$this->pwf = $this->getConverter()->getgpml2pwf(
			$this->fetchText(),
			[]
		);
		if ( $this->pwf ) {
			wfDebugLog( "Pathway",  "Converted gpml to pwf\n" );
			$this->savePwfCache();
			return $this->pwf;
		}
		$err = error_get_last();
		error_log( "Trouble converting $gpmlPath: {$err['message']}" );
		return null;
	}

	/**
	 * Get the OWL for this pathway, as a string
	 */
	public function getOwl() {
		wfDebugLog( "Pathway",  "getOwl() called\n" );

		if ( isset( $this->owl ) ) {
			wfDebugLog( "Pathway",  "Returning owl from memory\n" );
			return $this->owl;
		}

		$file = $this->getFileLocation( FILETYPE_BIOPAX );
		if ( $file && file_exists( $file ) ) {
			wfDebugLog( "Pathway",  "Returning owl from cache $file\n" );
			return file_get_contents( $file );
		}

		$gpmlPath = $this->getFileLocation( FILETYPE_GPML );
		$identifier = $this->pathway->getId();
		$version = $this->pathway->getActiveRevision();
		$organism = $this->pathway->getSpecies();

		if ( !$gpmlPath ) {
			error_log( "No file for GPML!" );
			return null;
		}

		$this->owl = $this->getConverter()->getgpml2owl(
			$this->fetchText(),
			[]
		);
		if ( $this->owl ) {
			wfDebugLog( "Pathway",  "Converted gpml to owl\n" );
			$this->saveOwlCache();
			return $this->owl;
		}
		$err = error_get_last();
		error_log( "Trouble converting $gpmlPath: {$err['message']}" );
		return null;
	}

	public function fetchText() {
		if ( !$this->isCached() ) {
			throw new \MWException("Cache this! " . $this->pathway->getId());
		}
		return parent::fetchText();
	}

	private function savePvjsonCache() {
		wfDebugLog( "Pathway",  "savePvjsonCache() called\n" );
		// This function is always called when GPML is converted to pvjson; which is not the case for SVG.
		$pvjson = $this->pvjson;

		$file = $this->getFileLocation( FILETYPE_JSON );
		if ( !$pvjson ) {
			$pvjson = $this->getPvjson();
		}

		if ( !$pvjson ) {
			wfDebugLog(
				"Pathway", "Invalid pvjson, so cannot savePvjsonCache."
			);
			return;
		}

		wfDebugLog(
			"Pathway",  "savePvjsonCache: Need to write pvjson to $file\n"
		);
		self::writeFile( $file, $pvjson );

		if ( !file_exists( $file ) ) {
			throw new Exception( "Unable to save pvjson" );
		}
		wfDebugLog( "Pathway",  "PVJSON CACHE SAVED: $file\n" );
	}

	private function savePdfCache() {
		wfDebugLog( "Pathway",  "savePdfCache() called\n" );
		$gpmlPath = $this->getFileLocation( FILETYPE_GPML );
		$file = $this->getFileLocation( FILETYPE_BIOPAX );
		if ( !$gpmlPath || !file_exists( $gpmlPath ) ) {
			throw new MWException( "savePdfCache() failed: GPML unavailable." );
		}
		$pdf = $this->getPdf();
		if ( !$pdf ) {
			wfDebugLog( "Pathway",  "Unable to convert to pdf, so cannot savePdfCache." );
			return;
		}
		self::writeFile( $file, $pdf );
		if ( !file_exists( $file ) ) {
			throw new Exception( "Unable to save pdf" );
		}
		wfDebugLog( "Pathway",  "PDF CACHE SAVED: $file\n" );
	}

	private function savePwfCache() {
		wfDebugLog( "Pathway",  "savePwfCache() called\n" );
		$gpmlPath = $this->getFileLocation( FILETYPE_GPML );
		$file = $this->getFileLocation( FILETYPE_BIOPAX );
		if ( !$gpmlPath || !file_exists( $gpmlPath ) ) {
			throw new MWException( "savePwfCache() failed: GPML unavailable." );
		}
		$pwf = $this->getPwf();
		if ( !$pwf ) {
			wfDebugLog( "Pathway",  "Unable to convert to pwf, so cannot savePwfCache." );
			return;
		}
		self::writeFile( $file, $pwf );
		if ( !file_exists( $file ) ) {
			throw new Exception( "Unable to save pwf" );
		}
		wfDebugLog( "Pathway",  "PWF CACHE SAVED: $file\n" );
	}

	private function saveOwlCache() {
		wfDebugLog( "Pathway",  "saveOwlCache() called\n" );
		$gpmlPath = $this->getFileLocation( FILETYPE_GPML );
		$file = $this->getFileLocation( FILETYPE_BIOPAX );
		if ( !$gpmlPath || !file_exists( $gpmlPath ) ) {
			throw new MWException( "saveOwlCache() failed: GPML unavailable." );
		}
		$owl = $this->getOwl();
		if ( !$owl ) {
			wfDebugLog( "Pathway",  "Unable to convert to owl, so cannot saveOwlCache." );
			return;
		}
		self::writeFile( $file, $owl );
		if ( !file_exists( $file ) ) {
			throw new Exception( "Unable to save owl" );
		}
		wfDebugLog( "Pathway",  "OWL CACHE SAVED: $file\n" );
	}

	private function saveSvgCache() {
		wfDebugLog( "Pathway",  "saveSvgCache() called\n" );
		$gpmlPath = $this->getFileLocation( FILETYPE_GPML );
		$file = $this->getFileLocation( FILETYPE_IMG );
		if ( !$gpmlPath || !file_exists( $gpmlPath ) ) {
			throw new MWException( "saveSvgCache() failed: GPML unavailable." );
		}
		$svg = $this->getSvg();
		if ( !$svg ) {
			wfDebugLog( "Pathway",  "Unable to convert to svg, so cannot saveSvgCache." );
			return;
		}
		self::writeFile( $file, $svg );
		if ( !file_exists( $file ) ) {
			throw new Exception( "Unable to save svg" );
		}
		wfDebugLog( "Pathway",  "SVG CACHE SAVED: $file\n" );
	}

	private function savePngCache() {
		// NOTE: Inkscape has an open issue for not supporting
		// the CSS property dominant-baseline.
		// https://bugs.launchpad.net/inkscape/+bug/811862
		wfDebugLog( "Pathway",  "savePngCache() called\n" );
		global $wgSVGConverters, $wgSVGConverter, $wgSVGConverterPath;

		$input = $this->getFileLocation( FILETYPE_IMG );
		$output = $this->getFileLocation( FILETYPE_PNG );

		$width = 1000;
		$retval = 0;
		if ( isset( $wgSVGConverters[$wgSVGConverter] ) ) {
			// TODO: calculate proper height for rsvg
			$cmd = str_replace(
				[ '$path/', '$width', '$input', '$output' ],
				[
					$wgSVGConverterPath
					? wfEscapeShellArg( "$wgSVGConverterPath/" )
					: "",
					intval( $width ),
					wfEscapeShellArg( $input ),
					wfEscapeShellArg( $output )
				],
				$wgSVGConverters[$wgSVGConverter] ) . " 2>&1";
			$err = wfShellExec( $cmd, $retval );
			if ( $retval != 0 || !file_exists( $output ) ) {
				throw new Exception(
					"Unable to convert to png: $err\nCommand: $cmd"
				);

			}
		} else {
			throw new Exception(
				"Unable to convert to png, no SVG rasterizer found"
			);
		}
		wfDebugLog( "Pathway",  "PNG CACHE SAVED: $output\n" );
	}

	// Check if the cached version of the GPML data derived file is out of date
	private function isOutOfDate( $fileType ) {
		wfDebugLog( "Pathway",  "isOutOfDate for $fileType\n" );

		$gpmlTitle = $this->getTitleObject();
		$gpmlRev = Revision::newFromTitle( $gpmlTitle );
		if ( $gpmlRev ) {
			$gpmlDate = $gpmlRev->getTimestamp();
		} else {
			$gpmlDate = -1;
		}

		$file = $this->getFileObj( $fileType, false );

		if ( $file->exists() ) {
			$fmt = wfTimestamp( TS_MW, filemtime( $file ) );
			wfDebugLog( "Pathway",  "\tFile exists, cache: $fmt, gpml: $gpmlDate\n" );
			return $fmt < $gpmlDate;
		} elseif ( $fileType === FILETYPE_GPML ) {
			$output = $this->getFileLocation( FILETYPE_GPML );
			$rev = Revision::newFromTitle(
				$this->getTitleObject(), false, Revision::READ_LATEST
			);
			if ( !is_object( $rev ) ) {
				return true;
			}

			self::ensureDir( $output );
			file_put_contents( $output, $rev->getContent()->getNativeData() );
			return false;
		} else {
			// No cached version yet, so definitely out of date
			wfDebugLog( "Pathway",  "\tFile doesn't exist\n" );
			return true;
		}
	}

	/**
	 * Clear all cached files
	 * @param string $fileType The file type to remove the cache for (
	 * one of FILETYPE_* constants ) or null to remove all files
	 */
	public function clearCache( $fileType = null ) {
		if ( !$fileType ) {
			// Update all
			$this->clearCache( FILETYPE_PNG );
			$this->clearCache( FILETYPE_GPML );
			$this->clearCache( FILETYPE_IMG );
		} else {
			$file = $this->getFileObj( $fileType );
			if ( $file->exists() ) {
				// Delete the cached file
				unlink( $file );
			}
		}
	}

	/**
	 * Get the SVG for the given JSON
	 * @return string
	 */
	public function getSvg() {
		wfDebugLog( "Pathway",  "getSvg() called\n" );

		if ( isset( $this->svg ) ) {
			wfDebugLog( "Pathway",  "Returning svg from memory\n" );
			return $this->svg;
		}

		$file = $this->getFileLocation( FILETYPE_IMG );
		if ( $file && file_exists( $file ) ) {
			wfDebugLog( "Pathway",  "Returning svg from cache $file\n" );
			return file_get_contents( $file );
		}

		wfDebugLog( "Pathway",  "need to get pvjson in order to get svg\n" );
		$pvjson = $this->getPvjson();
		wfDebugLog( "Pathway",  "got pvjson in process of getting svg\n" );
		$svg = $this->getConverter()->getpvjson2svg( $pvjson, [ "static" => false ] );
		wfDebugLog( "Pathway",  "got svg\n" );
		$this->svg = $svg;
		return $svg;
	}
}
