<?php
/**
 * This class is responsible for reading values from and writing
 * values to that cache.
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
   Since counting unique genes accross all pathways is an expensive operation,
   these calculations are cached.
 */
class StatisticsCache {
	private static $pathways;

	private static function getAllPathways() {
		if ( !self::$pathways ) {
			self::$pathways = Pathway::getAllPathways();
		}
		return self::$pathways;
	}

	/**
	 * returns the number of unique genes for a certain species.
	 * re-creates the cache if it doesn't exist.
	 */
	public static function howManyUniqueGenes( $species ) {
		global $wgScriptPath;
		$count = 0;

		// initialize variable $data with the contents of the cache
		$data = self::readGeneCache();
		// update cache if this species has never been calculated before
		if ( !array_key_exists( $species, $data ) ) {
				$data[$species] = self::countUniqueGenes( $species );
				self::writeGeneCache( $data );
		}

		return $data[$species];
	}

	/**
	 * returns the number of pathways for a certain species.
	 * given species = 'total', it returns total number of pathways
	 * re-creates the cache if it doesn't exist.
	 */
	public static function howManyPathways( $species ) {
		global $wgScriptPath;
		$count = 0;

		// initialize variable $data with the contents of the cache
		$data = self::readPathwayCache();

		// update cache if this species has never been calculated before
		if ( !array_key_exists( $species, $data ) ) {
				$data = self::countPathways();
				self::writePathwayCache( $data );
		}

		return $data[$species];
	}

	/**
	 * Calculates the number of unique genes in all pathways per species.
	 */
	private static function countUniqueGenes( $species ) {
		global $wgScriptPath;

		$databases = DataSource::getDatasourcesByType( 'gene' );
		$geneList = [];
		$taggedIds = CurationTag::getPagesForTag( 'Curation:Tutorial' );
		$all_pathways = self::getAllPathways();
		foreach ( $all_pathways as $pathway ) {
			if ( !$pathway->isPublic() ) { continue; // Skip private pathways
			}
			$pathwaySpecies = $pathway->species();
			if ( $pathwaySpecies != $species ) { continue;   }			$page_id = $pathway->getPageIdDB();
			if ( in_array( $page_id, $taggedIds ) ) { continue; // skip Tutorial pathways
			}
			try
				{
					$xrefs = $pathway->getUniqueXrefs();

					foreach ( $xrefs as $xref ) {
						$id = $xref->getId();
						$db = $xref->getSystem();
						if ( $id && $id != '' && $id != ' ' ) {
							if ( in_array( $db, $databases ) ) {
								array_push( $geneList, $id );
							}
						}
					}
			}
			catch ( Exception $e ) {
					// we can safely ignore exceptions
					// erroneous pathways simply won't get counted
			}
		}
		$geneList = array_unique( $geneList );
		return count( $geneList );
	}

	/**
	 * Calculates the number of pathways for each species. Unlike countUniqueGenes(),
	 * this methods counts for all species every time. It's basically just as fast with the
	 * current logic below.
	 */
	private static function countPathways() {
		$taggedIds = CurationTag::getPagesForTag( 'Curation:Tutorial' );
		$pathwaysPerSpecies = [];

		// Set all species to 0
		foreach ( Pathway::getAvailableSpecies() as $species ) {
			$pathwaysPerSpecies[$species] = 0;
		}

		$total = 0;
		$pathways = self::getAllPathways();
		foreach ( $pathways as $pathway ) {
			if ( $pathway->isDeleted() ) { continue; // skip deleted pathways
			}
			if ( !$pathway->isPublic() ) { continue; // Skip private pathways
			}
			$page_id = $pathway->getPageIdDB();
			if ( in_array( $page_id, $taggedIds ) ) { continue; // skip Tutorial pathways
			}
			$species = $pathway->getSpecies();
			if ( $species == '' ) { continue; // skip pathways without a species category
			}
			$pathwaysPerSpecies{$species} += 1;
			$total += 1;
		}
		$pathwaysPerSpecies{'total'} = $total;
		return $pathwaysPerSpecies;
	}

	/**
	   re-calculate the gene count for a particular species.
	   This should be called when a pathway has been updated.
	*/
	public static function updateUniqueGenesCache( $species ) {
		try
			{
				$data = self::readGeneCache();
				$data[$species] = self::countUniqueGenes( $species );
				self::writeGeneCache( $data );

				return $data;
		}
		catch ( Exception $e ) {
				// likely having trouble opening files, perhaps due to permissions
				// files should have 664 permissions
		}
	}

	/**
	   re-calculate the pathway count for all species.
	   This should be called when a pathway has been created or deleted.
	*/
	public static function updatePathwaysCache() {
		try
			{
				$data = self::countPathways();
				self::writePathwayCache( $data );

				return $data;
		}
		catch ( Exception $e ) {
				// likely having trouble opening files, perhaps due to permissions
				// files should have 664 permissions
		}
	}

	private static function writeGeneCache( $data ) {
		global $wgScriptPath;
		// write all data in $data back to the file again
		$filename = WPI_CACHE_PATH . '/UniqueGeneCounts.data';
		$file = fopen( $filename, 'w+' );
		foreach ( $data as $key => $c ) {
				fwrite( $file, "$key\t$c\n" );
		}
		fclose( $file );
		chmod( $filename, 0666 );
	}

	/**
	   read the contents of the gene cache
	   and return this as a set of $species => $count pairs
	*/
	private static function readGeneCache() {
		global $wgScriptPath;

		// read contents of the cache into variable $data
		$data = [];

		$filename = WPI_CACHE_PATH . '/UniqueGeneCounts.data';
		$file = @fopen( $filename, 'r' );
		if ( $file ) {
			while ( !feof( $file ) ) {
				if ( $line = trim( fgets( $file ) ) ) {
					$explodedLine = explode( "\t", $line );
					$data[$explodedLine[0]] = $explodedLine[1];
				}
			}
		} else {
			wfDebug( "UniqueGeneCounts.data isn't in " . WPI_CACHE_PATH );
		}
		return $data;
	}

	private static function writePathwayCache( $data ) {
		global $wgScriptPath;

		// write all data in $data back to the file again
		$filename = WPI_CACHE_PATH . '/PathwayCounts.data';

		$file = @fopen( $filename, 'w+' );
		if ( $file === false ) {
			wfDebug( "Couldn't open pathway cache file for writing: $filename\n" );
			throw new MWException( "Couldn't open PathwayCounts file for writing" );
		}
		foreach ( $data as $key => $c ) {
			fwrite( $file, "$key\t$c\n" );
		}
		fclose( $file );
		chmod( $filename, 0666 );
	}

	/**
	   read the contents of the pathway cache
	   and return this as a set of $species => $count pairs
	*/
	private static function readPathwayCache() {
		global $wgScriptPath;

		// read contents of the cache into variable $data
		$data = [];

		$filename = WPI_CACHE_PATH . '/PathwayCounts.data';
		$file = @fopen( $filename, 'r' );
		if ( $file ) {
				while ( !feof( $file ) ) {
						if ( $line = trim( fgets( $file ) ) ) {
							$explodedLine = explode( "\t", $line );
							$data[$explodedLine[0]] = $explodedLine[1];
						}
				}
		}
		return $data;
	}

public static function getSiteStats( &$parser, $tableAttr ) {
	$nrPathways = self::howManyPathways( 'total' );
	$output = "* There are '''{$nrPathways}''' pathways";

	if ( ! is_dir( WPI_CACHE_PATH ) && ! wfMkdirParents( WPI_CACHE_PATH ) ) {
		wfDebug( "Can't create: " . WPI_CACHE_PATH );
		throw new Exception( "Can't create WPI_CACHE_PATH!" );
	}

	$table = <<<EOD

* Number of '''pathways''' ''(and unique genes)'' per species:
{| align="center" $tableAttr
EOD;
	foreach ( Pathway::getAvailableSpecies() as $species ) {
		$nr = self::howManyPathways( $species );
		$genes = self::howManyUniqueGenes( $species );
		if ( $nr > 0 ) {  // skip listing species with 0 pathways
			$table .= <<<EOD

|-align="left"
|$species:
|'''$nr'''
|''($genes)''
EOD;
		}
	}
	$table .= "\n|}";
	$output .= $table;
	// $output .= "\n* There are '''{{NUMBEROFUSERS}}''' registered users";
	$output .= "\n* [[WikiPathways:Statistics|Additional statistics... ]]";

	$output = $parser->recursiveTagParse( $output );
	return [ $output, 'isHTML' => true, 'noparse' => true, 'nowiki' => true ];
}

public static function getSpecies() {
	return Pathway::getAvailableSpecies();
}

}
