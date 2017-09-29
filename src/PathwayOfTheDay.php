<?php
/**
 * Pathway of the day generator
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
 *
 * We need:
 *    - a randomized list of all pathways
 *    - remove pathway that is used
 *    - randomize again when we're at the end!
 *    - update list when new pathways are added....randomize every
 *      time (but exclude those we've already had)
 *
 * Concerning MediaWiki:
 *    - create a new SpecialPage: Special:PathwayOfTheDay
 *    - create an extension that implements above in php
 *
 * We need:
 *    - to pick a random pathway everyday (from all articles in
 *      namespace pathway)
 *    - remember this pathway and the day it was picked, store
 *      that in cache
 *    - on a new day, pick a new pathway, replace cache and
 *      update history
 */
namespace WikiPathways;

use Parser;

class PathwayOfTheDay {
	private static $table = 'pathwayOfTheDay';

	// Todays pathway
	protected $todaysPw;

	// Day todaysPw was marked as today's
	protected $today;

	// Id to support multiple pathway of the day caches
	protected $id;

	/**
	 * el constructor
	 *
	 * @param string $id of pathway
	 * @param int $date (optional) time since epoch
	 */
	public function __construct( $id, $date = null ) {
		// TODO: Move to update schema hook
		self::setupDB();
		$this->id = $id;
		if ( $date ) {
			$this->today = $date;
		} else {
			$this->today = date( "l j F Y" );
		}
		$this->todaysPw = $this->fetchTodaysPathway();
	}

	/**
	 * Generator for parser's magic word
	 *
	 * @param Parser $parser the parser
	 * @param string $date this is for
	 * @param string $listpage this is listed force
	 * @param bool $isTag is this a tagged or featured?
	 * @return array
	 */
	public static function get(
		Parser $parser, $date, $listpage = 'FeaturedPathways', $isTag = false
	) {
		$parser->disableCache();
		wfDebug( "GETTING PATHWAY OF THE DAY for date: $date\n" );
		try {
			if ( $isTag ) {
				$potd = new TaggedPathway( $listpage, $date, $listpage );
			} else {
				$potd = new FeaturedPathway( $listpage, $date, $listpage );
			}
			$out = $potd->getWikiOutput();
			wfDebug( "END GETTING PATHWAY OF THE DAY for date: $date\n" );
		} catch ( Exception $e ) {
			$out = "Unable to get pathway of the day: {$e->getMessage()}";
			wfDebug( "Couldn't make pathway of the day: {$e->getMessage()}" );
		}
		$out = $parser->recursiveTagParse( $out );
		return [ $out, 'isHTML' => true, 'noparse' => true, 'nowiki' => true ];
	}

	/**
	 * Return the wikitext for this pathway
	 *
	 * @return string
	 */
	public function getWikiOutput() {
		// Template variable not set, use dummy return values
		if ( $this->today == '{{{date}}}' ) {
			$pw = "TemplatePathway";
			$date = "TemplateDate";
		} else {
			$pw = $this->todaysPathway();
			$name = $pw->name();
			$species = $pw->species();
			$article = $pw->getTitleObject()->getFullText();
			$image = $pw->getImageTitle()->getFullText();
			$date = $this->today;
		}
		return "{{Template:TodaysPathway|pwName=$name|pwSpecies=$species|article=$article"
			. "|image=$image|date=$date}}";
	}

	private function fetchTodaysPathway() {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( self::$table, [ 'pathway' ], [ 'day' => $this->id . $this->today ] );
		$row = $dbr->fetchRow( $res );
		$dbr->freeResult( $res );
		return $row[0];
	}

	/**
	 * Get the pathway for today
	 *
	 * @return Pathway
	 */
	public function todaysPathway() {
		// No pathway in history yet
		if ( !$this->todaysPw ) {
			$this->brandNewDay();
		}
		try {
			$pathway = Pathway::newFromTitle( $this->todaysPw );
			// Check for deletion and fetch other pathway if the
			// current one doesn't exist anymore
			if ( !$pathway->exists() || $pathway->isDeleted() ) {
				$this->brandNewDay();
				$pathway = Pathway::newFromTitle( $this->todaysPw );
			}
		} catch ( Exception $e ) {
			// Fallback to default pathway
			$pathway = Pathway::newFromTitle( "Pathway:Homo sapiens:Apoptosis" );
		}
		return $pathway;
	}

	/**
	 * Create and fill the tables
	 */
	private static function setupDB() {
		$tbl = self::$table;
		$dbw = wfGetDB( DB_MASTER );
		wfDebug( "\tCreating tables\n" );
		$dbw->query(
			"CREATE TABLE IF NOT EXISTS $tbl ( pathway varchar(255), day varchar(50) )", DB_MASTER
		);
		wfDebug( "\tDone!\n" );
	}

	/**
	 * 	A brand new day, fetch new random pathway that we haven't had before
	 */
	private function brandNewDay() {
		wfDebug( "\tA brand new day....refreshing pathway of the day\n" );
		$this->findFreshPathway();
	}

	private function findFreshPathway() {
		wfDebug( "\tSearching for fresh pathway\n" );
		$pw = $this->fetchRandomPathway();
		wfDebug( "\t\tPathway in cache: '$pw'\n" );
		$tried = 0;
		while ( $this->hadBefore( $pw ) ) {
			// Keep on searching until we found one that we haven't had before
			$pw = $this->fetchRandomPathway();
			wfDebug( "\t\tTrying: '$pw'\n" );
			$tried++;
			wfDebug( "\t\t\t$tried attempt\n" );
			if ( $tried > 100 ) {
				wfDebug( "\tTried too often, clearing history\n" );
				// However, if we tried too often, just pick a pathway and reset the pathway list
				// TODO: 'too often' needs to be the number of pathways...
				$this->clearHistory();
			}
		}
		$this->todaysPw = $pw;
		// We found  a new pathway, now update history
		$this->updateHistory();
	}

	private function hadBefore( $pathway ) {
		wfDebug( "\tDid we have $pathway before? " );
		if ( !$pathway ) {
			wfDebug( " we don't have a pathway\n" );
			return true;
		}
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( self::$table, [ 'pathway' ], [ 'pathway' => $pathway ] );
		$row = $dbr->fetchRow( $res );
		$dbr->freeResult( $res );
		$had = $row ? true : false;
		wfDebug( " $had\n" );
		return $had;
	}

	private function clearHistory() {
		$dbw = wfGetDB( DB_MASTER );
		wfDebug( "\tClearing history\n" );
		$dbw->query( "TRUNCATE TABLE " . self::$table, DB_MASTER );
	}

	private function updateHistory() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert( self::$table, [ 'pathway' => $this->todaysPw, 'day' => $this->id . $this->today ] );
	}

	/**
	 * Select a random pathway
	 * @return a page title
	 */
	protected function fetchRandomPathway() {
		wfDebug( "Fetching random pathway...\n" );
		$dbr = wfGetDB( DB_SLAVE );
		// Pick a random pathway from all articles in namespace NS_PATHWAY
		// FIXME: RAND() only works in MySQL?
		$res = $dbr->query(
			"SELECT page_title FROM page WHERE page_namespace = " . NS_PATHWAY .
				" AND page_is_redirect = 0 ORDER BY RAND() LIMIT 1", DB_SLAVE );
		$row = $dbr->fetchRow( $res );
		wfDebug( "Resulting pathway: " . $row[0] . "\n" );
		return $row[0];
	}
}
