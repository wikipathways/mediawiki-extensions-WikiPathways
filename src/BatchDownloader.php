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
 *
 * @author
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways;

use Html;

class BatchDownloader {
	private $species;
	private $fileType;
	private $listPage;
	private $tag;
	private $excludeTags;
	private $displayStats;

	function __construct(
		$species, $fileType, $listPage = '', $includeTag = '', $excludeTags = null,
		$displayStats = false
	) {
		$this->species = $species;
		$this->fileType = $fileType;
		$this->listPage = $listPage;
		$this->tag = $includeTag;
		if ( $excludeTags && count( $excludeTags ) > 0 ) {
			$this->excludeTags = $excludeTags;
		}
		$this->stats = $displayStats;
	}

	static function createDownloadLinks( $input, $argv, $parser ) {
		$fileType     = isset( $argv['filetype'] ) ? $argv['filetype'] : "";
		$listPage     = isset( $argv['listpage'] ) ? $argv['listpage'] : "";

		$tag          = isset( $argv['tag'] ) ? $argv['tag'] : "";
		$excludeTags  = isset( $argv['excludetags'] ) ? $argv['excludetags'] : "";
		$displayStats = isset( $argv['stats'] ) ? $argv['stats'] : "";

		$listParam = "";
		if ( $listPage ) {
			$listParam = '&listPage=' . $listPage;
			$listedPathways = Pathway::parsePathwayListPage( $listPage );
			foreach ( $listedPathways as $pw ) {
				if ( isset( $countPerSpecies[$pw->getSpecies()] ) ) {
					$countPerSpecies[$pw->getSpecies()] += 1;
				} else {
					$countPerSpecies[$pw->getSpecies()] = 1;
				}
			}
		}

		$tagParam = "";
		if ( $tag ) {
			$tagParam = "&tag=$tag";
			$taggedPageIds = CurationTag::getPagesForTag( "$tag" );
			foreach ( $taggedPageIds as $pageId ) {
				$species = Pathway::newFromTitle( Title::newFromId( $pageId ) )->getSpecies();
				$countPerSpecies["$species"] = isset( $countPerSpecies["$species"] )
											 ? $countPerSpecies["$species"] + 1
											 : 1;
			}
		}

		$excludeParam = "";
		if ( $excludeTags ) {
			$excludeParam = "&tag_excl=$excludeTags";
		}
		$html = "";
		foreach ( Pathway::getAvailableSpecies() as $species ) {
			$nrPathways = isset( $countPerSpecies[$species] ) ? $countPerSpecies[$species] : 0;
			$stats = "";
			if ( $displayStats ) {
				$stats = "\t\t($nrPathways)";
			} elseif ( !$listPage && !$tag ) {
				// list all if not filtering and counting
				$nrPathways = 1;
			}
			if ( $nrPathways > 0 ) {
				// skip listing species with 0 pathways
				$html .=
					  Html::element( 'li', [],
									 Html::element( 'a', [
										 'href' => WPI_URL
										 . "/batchDownload.php?species=$species"
										 . "&fileType=$fileType"
										 . "$listParam$tagParam$excludeParam",
										 'target' => '_new'
									 ],  $species . $stats )
					  );
			}
		}
		$html = tag( 'ul', $html );
		return $html;
	}

	private function createZipName() {
		$list = $this->listPage ? "_{$this->listPage}" : '';
		$t = $this->tag ? "_{$this->tag}" : '';
		$et = '';
		if ( $this->excludeTags ) {
			$str = implode( '.', $this->excludeTags );
			$et = "_$str";
		}
		$fileName = "wikipathways_" . $this->species .
			$list . $t . $et . "_{$this->fileType}.zip";
		$fileName = str_replace( ' ', '_', $fileName );
		// Filter out illegal chars
		$fileName = preg_replace( "/[\/\?\<\>\\\:\*\|\[\]]/", '-', $fileName );

		return WPI_CACHE_DIR . "/" . $fileName;
	}

	private function getCached() {
		$zipFile = $this->createZipName();
		if ( file_exists( $zipFile ) ) {
			$tsZip = filemtime( $zipFile );

			// Check if file is still valid (based on the latest pathway edit)
			$latest = wfTimestamp( TS_UNIX, MwUtils::getLatestTimestamp( NS_PATHWAY ) );

			// If the download is based on curation tags, also check the last modification
			// on the used tags
			if ( $this->tag || $this->excludeTags ) {
				$checkTags = [];
				if ( $this->tag ) { $checkTags[] = $this->tag;
				}
				if ( $this->excludeTags ) {
					foreach ( $this->excludeTags as $t ) { $checkTags[] = $t;
					}
				}
				$hist = CurationTag::getAllHistory( wfTimestamp( TS_MW, $tsZip ) );
				foreach ( $hist as $h ) {
					if ( in_array( $h->getTagName(), $checkTags ) ) {
						$action = $h->getAction();
						if ( $action == MetaTag::$ACTION_CREATE || $action == MetaTag::$ACTION_REMOVE ) {
							$latestTag = wfTimestamp( TS_UNIX, $h->getTime() );
							break;
						}
					}
				}
			}
			if ( $latestTag > $latest ) { $latest = $latestTag;
			}

			if ( $latest > $tsZip ) {
				return null;
			} else {
				return $zipFile;
			}
		} else {
			return null;
		}
	}

	public function download() {
		if ( !Pathway::isValidFileType( $this->fileType ) ) {
			throw new Exception( "Invalid file type: {$this->fileType}" );
		}

		// Try to find a cached download file and validate
		$zipFile = $this->getCached();
		if ( $zipFile ) {
			wfDebug( __METHOD__ . ": using cached file $zipFile\n" );
			$this->doDownload( $zipFile );
		} else {
			wfDebug( __METHOD__ . ": no cached file, creating new batch download file\n" );
			$this->doDownload( $this->createZipFile( $this->listPathways() ) );
		}
	}

	private function createZipFile( $pathways ) {
		if ( is_null( $pathways ) || count( $pathways ) == 0 ) {
			throw new Exception( "'''Unable process download:''' No pathways matching your criteria" );
		}

		$zipFile = $this->createZipName();
		// Delete old file if exists
		if ( file_exists( $zipFile ) ) { unlink( $zipFile );
		}

		// Create symlinks to the cached gpml files,
		// with a custom file name (containing the pathway title)
		$files = "";
		$tmpLinks = [];
		$tmpDir = WPI_TMP_PATH . "/" . wfTimestamp( TS_UNIX );
		if ( !file_exists( $tmpDir ) ) {
			if ( !mkdir( $tmpDir ) ) {
				$e = error_get_last();
				throw new Exception( "Couldn't create directory. ". $e['message'] . " for $tmpDir." );
			}
		} elseif ( !is_dir( $tmpDir ) ) {
			throw new Exception( "$tmpDir exists but isn't a directory" );
		}
		foreach ( $pathways as $pw ) {
			$link = $tmpDir . "/" . $pw->getFilePrefix() . "_" . $pw->getIdentifier() . "_"
				. $pw->getActiveRevision() . "." . $this->fileType;
			$cache = $pw->getFileLocation( $this->fileType );
			link( $cache, $link );
			$tmpLinks[] = $link;
			$files .= '"' . $link . '" ';
		}
		$cmd = "zip -j \"$zipFile\" $files 2>&1";
		$output = wfShellExec( $cmd, $status );

		// Remove the tmp files
		foreach ( $tmpLinks as $l ) {
			if ( file_exists( $tmpDir ) ) {
				unlink( $l );
			}
		}
		if ( file_exists( $tmpDir ) && is_dir( $tmpDir ) ) {
			rmdir( $tmpDir );
		}

		if ( $status != 0 ) {
			throw new Exception( "'''Unable process download:''' $output" );
		}
		return $zipFile;
	}

	function listPathways() {
		if ( $this->listPage ) {
			$allpws = Pathway::parsePathwayListPage( $this->listPage );
			$pathways = [];
			// Apply additional filter by species
			foreach ( $allpws as $p ) {
				if ( $p->getSpecies() == $this->species ) {
					$pathways[$p->getIdentifier()] = $p;
				}
			}
		} else {
			$pathways = Pathway::getAllPathways();
			$filtered = [];
			foreach ( $pathways as $p ) {
				// Filter by species
				if ( $p->getSpecies() == $this->species ) {
					$filtered[$p->getIdentifier()] = $p;
				}
			}
			$pathways = $filtered;
		}

		// Include only pathways with a given tag
		if ( $this->tag ) {
			$filtered = [];
			$pages = MetaTag::getPagesForTag( $this->tag );
			foreach ( $pathways as $p ) {
				$id = $p->getTitleObject()->getArticleId();
				if ( in_array( $id, $pages ) ) {
					$tag = new MetaTag( $this->tag, $id );
					$rev = $tag->getPageRevision();
					if ( $rev ) {
						$p->setActiveRevision( $rev, false );
					}
					$filtered[$p->getIdentifier()] = $p;
				}
			}
			$pathways = $filtered;
		}
		// Filter out certain tags
		$filtered = [];
		if ( $this->excludeTags ) {
			$pages = [];
			foreach ( $this->excludeTags as $t ) {
				$pages = array_merge( $pages, MetaTag::getPagesForTag( $t ) );
			}
			foreach ( $pathways as $p ) {
				$id = $p->getTitleObject()->getArticleId();
				if ( !in_array( $id, $pages ) ) {
					$filtered[$p->getIdentifier()] = $p;
				}
			}
			$pathways = $filtered;
		}
		// Filter for private pathways
		$filtered = [];
		foreach ( $pathways as $p ) {
			if ( $p->isPublic() ) {
				// Filter out all private pathways
				$filtered[$p->getIdentifier()] = $p;
			}
		}
		return $filtered;
	}

	function getPathwaysByList( $listPage ) {
		$pathways = Pathway::parsePathwayListPage( $listPage );
		return $pathways;
	}

	function doDownload( $file ) {
		// redirect to the cached file
		$url = WPI_CACHE_PATH . '/' . basename( $file );
		ob_start();
		ob_clean();
		header( "Location: $url" );
		exit();
	}
}
