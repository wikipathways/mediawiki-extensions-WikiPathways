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

use AjaxResponse;
use DOMDocument;
use WikiPathways\XRef;

class SearchPathwaysAjax {
	public static function parToXref( $ids, $codes ) {
		$ids = explode( ',', $ids );
		$codes = explode( ',', $codes );
		if ( count( $xrefs ) > count( $codes ) ) {
			$singleCode = $codes[0];
		}
		for ( $i = 0; $i < count( $ids ); $i += 1 ) {
			if ( $singleCode ) {
				$c = $singleCode;
			} else {
				$c = $codes[$i];
			}
			$x = new XRef( $ids[$i], $c );
			$xrefs[] = $x;
		}
		return( $xrefs );
	}

	public static function doSearch( $query, $species, $ids, $codes, $type ) {
		if ( !$type || $type == '' ) {
			$type = 'query';
		}
		if ( ( !$query || $query == '' ) && $type == 'query' ) {
			$query = 'glucose';
		}
		if ( $species == 'ALL SPECIES' ) {
			$species = '';
		}
		if ( $type == 'query' ) {
			$results = PathwayIndex::searchByText( $query, $species );
		} elseif ( $type == 'xref' ) {
			$xrefs = self::parToXref( $ids, $codes );
			$results = PathwayIndex::searchByXref( $xrefs, true );
		}
		$doc = new DOMDocument();
		$root = $doc->createElement( "results" );
		$doc->appendChild( $root );

		// Keep track of added pathways (result may contain duplicates)
		$addedResults = [];
		foreach ( $results as $r ) {
			$pwy = $r->getFieldValue( PathwayIndex::$f_source );
			if ( !in_array( $pwy, $addedResults ) ) {
				$rn = $doc->createElement( "pathway" );
				$rn->appendChild( $doc->createTextNode( $pwy ) );
				$root->appendChild( $rn );
				$addedResults[] = $pwy;
			}
		}
		$resp = new AjaxResponse( $doc->saveXML() );
		$resp->setContentType( "text/xml" );
		return( $resp );
	}

	public static function getResults( $pathwayTitles, $searchId ) {
		$html = "";
		foreach ( explode( ",", $pathwayTitles ) as $t ) {
			$pathway = Pathway::newFromTitle( $t );
			$name = $pathway->name();
			$species = $pathway->getSpecies();
			$href = $pathway->getFullUrl();
			$caption = "<a href=\"$href\">$name ($species)</a>";
			// This can be quite dangerous (injection)
			$caption = html_entity_decode( $caption );
			$output = SearchPathways::makeThumbNail( $pathway, $caption, $href, '', 'none', 'thumb', 200 );
			preg_match( '/height="(\d+)"/', $output, $matches );
			$height = $matches[1];
			if ( $height > 160 ) {
				$output = preg_replace( '/height="(\d+)"/', 'height="160px"', $output );
			}
			$output = "<div class='thumbholder'>$output</div>";
			$html .= "\n" . $output;
		}

		$doc = new DOMDocument();
		$root = $doc->createElement( "results" );
		$doc->appendChild( $root );

		$ni = $doc->createElement( "searchid" );
		$ni->appendChild( $doc->createTextNode( $searchId ) );
		$root->appendChild( $ni );

		$nh = $doc->createElement( "htmlcontent" );
		$nh->appendChild( $doc->createTextNode( $html ) );
		$root->appendChild( $nh );

		$resp = new AjaxResponse( trim( $doc->saveXML() ) );
		$resp->setContentType( "text/xml" );
		return( $resp );
	}
}
