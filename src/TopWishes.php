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

class TopWishes {
	public static function renderWishlist( $input, $argv, &$parser ) {
		$parser->disableCache();

		$wishlist = new PathwayWishList();

		$type = $argv['type'];
		$tableAttr = $argv['tableattr'];
		switch ( $type ) {
		case 'newest':
			$limit = $argv['limit'];
			return self::createNewest( $wishlist, $limit, $tableAttr );
		case 'topVoted':
		default:
			$limit = $argv['limit'];
			$threshold = $argv['threshold'];
			return createTopVoted( $wishlist, $limit, $threshold, $tableAttr );

		}
	}

	public static function createNewest( $wishlist, $limit = 5, $tableAttr = '' ) {
		global $wgLang;
		$limit = (int)$limit;
		$top = $wishlist->getWishlist( 'date' );
		if ( count( $top ) == 0 ) {
			return "<i>There are currently no wishlist items</i>";
		}
		// List version
		$out = "<ul>";
		$i = 0;
		foreach ( $top as $wish ) {
			if ( $i >= $limit ) {
				break;
			}
			$name = $wish->getTitle()->getText();
			$date = off( $wgLang->date( $wish->getRequestDate() ) );
			$href = SITE_URL . "/index.php/Special:SpecialWishlist";
			$out .= "<li><a href='$href'>$name</a> ($date), ";
			$i++;
		}
		$out = $out . "</ul><p align='right'><a href='$href'>more...</a></p>";

		/* table version
		   $out = "<table $tableAttr><tbody>";
		   $out .= "<th>Pathway<th>User<th>";
		   if(count($top) == 0) {
		   return "<i>There are currently no wishlist items</i>";
		   }
		   $i = 0;
		   foreach($top as $wish) {
		   if($i >= $limit) break;

		   $name = $wish->getTitle()->getText();
		   $user = $wish->getRequestUser();
		   $by = $wgUser->getSkin()->userLink( $user, $user->getName());
		   $date = off($wgLang->date($wish->getRequestDate()));
		   $out .= "<tr><td>$name<td>$by<td>$date";
		   $i++;
		   }
		   $out .= "</tbody></table>";
		*/
		return $out;
	}

	public static function off( $date ){
		$date = strtotime( $date );
		$offset = ( strftime( "%j" ) + strftime( "%Y" ) * 365 ) -
				( strftime( "%j", $date ) + strftime( "%Y", $date ) * 365 );
		if ( $offset > 7 ) {
			$offset = ( strftime( "%V" ) + strftime( "%Y" ) * 52 ) -
					( strftime( "%V", $date ) + strftime( "%Y", $date ) * 52 );
			$end = ( $offset != 0
					 ? ( $offset > 1
						 ? $offset . " weeks ago"
						 : "a week ago" )
					 : "Today" );
		} else {
			$end = ( $offset != 0
					 ? ( $offset > 1
						 ? "$offset days ago"
						 : "Yesterday" )
					 : "Today" );
		}
		return $end;
	}

	public static function createTopVoted( $wishlist, $limit = 5, $threshold = 1, $tableAttr = '' ) {
		$threshold = (int)$threshold;
		$limit = (int)$limit;
		$top = $wishlist->getWishlist( 'votes' );

		// list version
		$out = "<ul>";
		$i = 0;
		foreach ( $top as $wish ) {
			if ( $i >= $limit ) {
				break;
			}
			$votes = $wish->countVotes();
			if ( $votes < $threshold ) {
				break;
			}
			$name = $wish->getTitle()->getText();
			$href = SITE_URL . "/index.php/Special:SpecialWishlist";
			$out .= "<li><a href='$href'>$name</a> ($votes votes), ";
			$i++;
		}
		$out = $out . "</ul><p align='right'><a href='$href'>more...</a></p>";

		/* table version
		   $out = "<table $tableAttr><tbody>";
		   $out .= "<th>Pathway<th>Votes";
		   if(count($top) == 0 || $top[0]->countVotes() < $threshold) {
		   return "<i>There are currently no wishlist items with enough votes</i>";
		   }
		   $i = 0;
		   foreach($top as $wish) {
		   if($i >= $limit) break;
		   $votes = $wish->countVotes();
		   if($votes < $threshold) break;

		   $name = $wish->getTitle()->getText();
		   //$user = $wish->getRequestUser();
		   //$by = $wgUser->getSkin()->userLink( $user, $user->getName());

		   $out .= "<tr><td>$name<td align='center'>$votes";
		   $i++;
		   }
		   $out .= "</tbody></table>";
		*/
		return $out;
	}
}
