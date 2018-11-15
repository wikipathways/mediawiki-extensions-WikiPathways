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
			$this->saveCache( $fileType );
		}
	}

	private function getFileLocation( $type ) {
		return $this->cachePath() . "." . $type;
	}

	public function fetchText() {
		if ( !$this->isCached() ) {
			throw new \MWException("Cache this! " . $this->pathway->getId());
		}
		return parent::fetchText();
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
	 * Save a cached version of a filetype to be converted
	 * from GPML, when the conversion is done by GPMLConverter.
	 */
	private function saveCache( $fileType ) {
		# Convert gpml to fileType
		$gpmlFile = realpath( $this->getFileLocation( FILETYPE_GPML ) );
		$conFile = $this->getFileLocation( $fileType );
		$dir = dirname( $conFile );
		wfDebugLog( "Pathway",  "Saving $gpmlFile to $fileType in $conFile" );
		if ( !is_dir( $dir ) && !wfMkdirParents( $dir ) ) {
			throw new MWException( "Couldn't make directory: $dir" );
		}
		$this->getConverter()->convert(
			$gpmlFile,
			$conFile,
			[ "identifier" => $identifier,
			  "version" => $version,
			  "organism" => $organism ]
		);
		return $conFile;
	}

}
