<?php
/**
 * Cache metadata for a pathway.
 *
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
 *
 * @author
 * @author Mark A. Hershberger <mah@nichework.com>
 */

/**
 * The metadata cache is responsible for caching
 * metadata for a pathway (e.g. name, organism). This
 * information is also stored in GPML, but caching it
 * prevents that the GPML has to be loaded and parsed
 * each time the information is queried.
 */
namespace WikiPathways;

use Revision;

class MetaDataCache {
	private static $TAG_PREFIX = "cache-";
	public static $FIELD_NAME = "name";
	public static $FIELD_ORGANISM = "organism";
	public static $FIELD_DELETED = "deleted";
	public static $FIELD_XREFS = "xrefs";

	public static $XREF_SEP = ',';

	private $fields;
	private $pathway;
	private $page_id;
	private $tags;
	private $revtime; // Timestamp of latest revision of pathway

	/**
	 * Create a MetaDataCache object for the given
	 * pathway. The constructor will check if the
	 * cache is still up-to-date and updates it if
	 * necessary.
	 */
	public function __construct( $pathway ) {
		$this->pathway = $pathway;
		$this->page_id = $pathway->getTitleObject()->getArticleId();
		$this->tags = [];
		$this->fields = [
			self::$FIELD_NAME,
			self::$FIELD_ORGANISM,
			self::$FIELD_XREFS,
			self::$FIELD_DELETED,
		];
	}

	private static function createTagName( $field ) {
		return self::$TAG_PREFIX . $field;
	}

	private function load( $f ) {
		$tag = new MetaTag( self::createTagName( $f ), $this->page_id );
		$this->tags[$f] = $tag;
		return $tag;
	}

	/**
	 * Checks for the cache to be valid and updates it if
	 * necessary. The cache will only be updated if
	 * the pathway was changed after the last cache update.
	 */
	public function updateCache( $field = '' ) {
		if ( !$this->isValid( $field ) && $this->pathway->isReadable() ) {
			if ( $this->pathway->isDeleted( false ) ) {
				// leave the old cached values the same
				// but update to set modified timestamp
				$this->doUpdate( self::$FIELD_NAME, $this->getValue( self::$FIELD_NAME, false ) );
				$this->doUpdate( self::$FIELD_ORGANISM, $this->getValue( self::$FIELD_ORGANISM, false ) );
				$this->doUpdate( self::$FIELD_XREFS, $this->getValue( self::$FIELD_XREFS, false ) );
				// Set deleted to true
				$this->doUpdate( self::$FIELD_DELETED, $this->pathway->getLatestRevision() );
			} else {
				$title = $this->pathway->getPathwayData()->getName();
				$org = $this->pathway->getPathwayData()->getOrganism();
				$xrefs = $this->pathway->getPathwayData()->getUniqueXrefs();
				$xrefs = implode( self::$XREF_SEP, array_keys( $xrefs ) );

				$this->doUpdate( self::$FIELD_NAME, $title );
				$this->doUpdate( self::$FIELD_ORGANISM, $org );
				$this->doUpdate( self::$FIELD_XREFS, $xrefs );
				$this->doUpdate( self::$FIELD_DELETED, '' );
			}
		}
	}

	private function doDelete( $field ) {
		$tag = $this->tags[$field];
		if ( $tag ) {
			$tag->setPermissions( [] );
			$tag->setUseHistory( false );
			$tag->remove();
		}
	}

	private function doUpdate( $field, $value ) {
		$tag = isset( $this->tags[$field] ) ? $this->tags[$field] : null;

		if ( !$tag ) {
			$tag = new MetaTag( self::createTagName( $field ), $this->page_id );
			$this->tags[$field] = $tag;
		}
		$tag->setPermissions( [] );
		$tag->setUseHistory( false );
		$tag->setText( $value );
		$tag->save();
	}

	private function isValid( $field ) {
		$tag = $this->tags[$field];
		if ( !$tag ) { return false;
		}

		if ( !$this->revtime ) { // Load the latest revision
			$prev = Revision::newFromId(
				$this->pathway->getTitleObject()->getLatestRevID()
			);
			if ( $prev ) {
				$this->revtime = $prev->getTimestamp();
			}
		}

		$tmod = $tag->getTimeMod();
		if ( $this->revtime > $tmod ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the cached value for the given field
	 * @param field A cache field (use one of the $FIELD_ constants)
	 */
	public function getValue( $field, $update = true ) {
		$tag = isset( $this->tags[$field] ) ? $this->tags[$field] : $this->load( $field );
		if ( $update ) { $this->updateCache( $field );
		}
		return $tag;
	}

	/**
	 * Get all pages that have the given value for a cache field
	 * @param $field The cache field
	 * @param $value The value to search for
	 **/
	public static function getPagesByCache( $field, $value ) {
		return MetaTag::getPagesForTag( self::createTagname( $field ), $value );
	}
}
