<?php
/**
 * Copyright (C) 2017  J. David Gladstone Institutes
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
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
 * @author Mark A. Hershberger
 */
namespace WikiPathways;

class PathwayWishList {
	private $list_table = "wishlist";
	private $subscribe_table = "wishlist_subscribe";

	// List of titles in domain wishlist
	private $wishlist;
	private $byVotes;
	private $byDate;

	function __construct() {
		$this->loadWishlist();
	}

	/**
	 * Get the array containing all page_ids of the whishlist pages
	 */
	function getWishlist( $sortKey = 'date' ) {
		switch ( $sortKey ) {
			case 'votes':
				return $this->sortByVotes();
			case 'date':
				return $this->sortByDate();
			default:
				return $this->wishlist;
		}
	}

	private function sortByDate() {
		if ( !$this->byDate ) {
			$this->byDate = array_values( $this->wishlist );
			usort( $this->byDate, __CLASS__ . "::cmpDate" );
		}
		return $this->byDate;
	}

	private function sortByVotes() {
		if ( !$this->byVotes ) {
			$this->byVotes = array_values( $this->wishlist );
			usort( $this->byVotes, __CLASS__ . "::cmpVotes" );
		}
		return $this->byVotes;
	}

	static function cmpVotes( $a, $b ) {
		return $b->countVotes() - $a->countVotes();
	}

	static function cmpDate( $a, $b ) {
		return $b->getRequestDate() - $a->getRequestDate();
	}

	function addWish( $name, $comments ) {
		$wish = Wish::createNewWish( $name, $comments );
		$this->wishlist[$wish->getRequestDate()] = $wish;
	}

	/**
	 * Loads the wishlist from the database
	 */
	private function loadWishlist() {
		$this->wishlist = [];
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->query(
			"SELECT page_id FROM page WHERE page_namespace = " . NS_WISHLIST
			);

		while ( $row = $dbr->fetchRow( $res ) ) {
			$wish = new Wish( $row[0] );
			if ( $wish->exists() ) {
					$this->wishlist[$wish->getRequestDate()] = $wish;
			}
		}
		$dbr->freeResult( $res );
	}
}
