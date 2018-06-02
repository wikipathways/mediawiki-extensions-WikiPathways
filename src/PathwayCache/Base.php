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
namespace WikiPathways\PathwayCache;

use FileCacheBase;
use FSFileBackend;
use LocalRepo;
use UnregisteredLocalFile;
use WikiPathways\Pathway;
use WikiPathways\GPML\Converter;

abstract class Base extends FileCacheBase {
	protected $pathway;
	protected $converter;
	private $content;
	protected $mimeType = "unknown/unknown";

	public function __construct( Pathway $pathway, Converter $converter, $type ) {
		$this->pathway = $pathway;
		$this->converter = $converter;
		$this->mType = "wikipathways";
		$this->mKey = $pathway->getId() . '_' . $pathway->getActiveRevision();
		$this->mExt = strtolower( $type );
	}

	protected function cacheDirectory() {
		global $wgUploadDirectory;
		return $wgUploadDirectory;
	}

	public function getURL() {
		global $wgUploadPath;
		return $wgUploadPath . '/' . $this->typeSubdirectory() . $this->hashSubdirectory()
							 . $this->mKey . "." . $this->mExt;
	}

	public function getPath() {
		return $this->cachePath();
	}

	abstract public function doRender();
	public function render() {
		wfDebugLog( __METHOD__,  "called\n" );

		if ( isset( $this->content ) ) {
			wfDebugLog( __METHOD__,  "Returning content from memory\n" );
			return $this->content;
		}

		// Don't call isCached b/c we'll iniloop
		if ( file_exists( $this->cachePath() ) ) {
			$this->content = file_get_contents( $this->cachePath() );
			return $this->content;
		}
		$this->content = $this->doRender();
		if ( strlen( $this->content ) > 0 ) {
			return $this->content;
		}

		return false;
	}

	public function isCached() {
		if ( !parent::isCached() ) {
			return $this->saveText( $this->render() ) !== false;
		}
		return true;
	}

	private static $repo;
	private function setupRepo() {
		global $wgUploadPath;
		global $wgUploadDirectory;

		self::$repo = new LocalRepo( [
			"name" => "pathways",
			"url" => $wgUploadPath . "/wikipathways",
			"backend" => new FSFileBackend( [
				"name" => "pathways",
				"domainId" => "wikipathways",
				'basePath' => $wgUploadDirectory . "/wikipathways"
			] ),
		] );
	}

	/**
	 * @return string basename of file
	 */
	public function getName() {
		return basename( $this->cachePath() );
	}

	private function getRepo() {
		if ( !self::$repo ) {
			$this->setupRepo();
		}
		return self::$repo;
	}

	/**
	 * Get the MW image
	 *
	 * @return UnregisteredLocalFile
	 */
	public function getImgObject() {
		return new UnregisteredLocalFile(
			false, $this->getRepo(), $this->cachePath(), $this->mimeType
		);
	}
}
