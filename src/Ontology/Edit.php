<?php
/*
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
 * @author Thomas Kelder <thomaskelder@gmail.com>
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways\Ontology;

use AjaxResponse;
use WikiPathways\Pathway;

class Edit {
	/**
	 * @param string $tagId id of tag to remove
	 * @param string $pwTitle Pathway identifier
	 * @return string
	 * @fixme should detect failures to remove
	 */
	public static function removeOntologyTag( $tagId, $pwTitle ) {
		$dbw = wfGetDB( DB_MASTER );
		$comment = "Ontology Term : '$tagId' removed !";
		$pathway = Pathway::newFromTitle( $pwTitle );
		$gpml = $pathway->getGpml();
		$xml = simplexml_load_string( $gpml );

		$entry = $xml->Biopax[0];
		$namespaces = $entry->getNameSpaces( true );
		$bpNS = $entry->children( $namespaces['bp'] );

		$total = count( $bpNS->openControlledVocabulary );
		for ( $i = 0; $i < $total; $i++ ) {
			if ( $bpNS->openControlledVocabulary[$i]->ID == $tagId ) {
				unset( $bpNS->openControlledVocabulary[$i] );
			}
			$i++;
		}
		$dbw->delete( 'ontology', [ 'pw_id' => $pwTitle,'term_id' => $tagId ], __METHOD__ );
		$gpml = $xml->asXML();
		$pathway->updatePathway( $gpml, $comment );
		return "SUCCESS";
	}

	/**
	 * @param string $tagId id of tag to add
	 * @param string $tag tag name of tag
	 * @param string $pwTitle Pathway identifier
	 * @return string
	 */
	public static function addOntologyTag( $tagId, $tag, $pwTitle ) {
		$comment = "Ontology Term : '$tag' added !";
		$pathway = Pathway::newFromTitle( $pwTitle );
		$ontology = self::getOntologyName( $tagId );
		$path = self::getOntologyTagPath( $tagId );
		$gpml = $pathway->getGpml();
		$xml = simplexml_load_string( $gpml );

		if ( !isset( $xml->Biopax[0] ) ) {
			$xml->addChild( "Biopax" );
		}

		$bioNS = "http://www.biopax.org/release/biopax-level3.owl#";
		$gpmlVersion = $xml->getNamespaces( false );
		$gpmlVersion = $gpmlVersion[''];
		if ( preg_match( "@http://genmapp.org/GPML/([0-9]{4})@", $gpmlVersion, $res ) ) {
			if ( $res[1] < 2010 ) {
				$bioNS = "http://www.biopax.org/release/biopax-level2.owl#";
			}
		}
		$node = $xml->Biopax->addChild( "bp:openControlledVocabulary", "", $bioNS );

		$node->addChild( "TERM", $tag );
		$node->addChild( "ID", $tagId );
		$node->addChild( "Ontology", $ontology );

		$gpml = $xml->asXML();

		try {
			$pathway->updatePathway( $gpml, $comment );
			$dbw = wfGetDB( DB_MASTER );
			$dbw->begin();
			$dbw->insert( 'ontology', [
				'term_id' => $tagId,
				'term'    => $tag,
				'ontology' => $ontology,
				'pw_id'   => $pwTitle,
				'term_path'  => $path ],
						  __METHOD__,
						  'IGNORE' );
			$dbw->commit();
			return "SUCCESS";
		}
		catch ( Exception $e ) {
			return "ERROR";
		}
	}

	/**
	 * @param string $pwId
	 * @return string (json format)
	 */
	public static function getOntologyTags( $pwId ) {
		$resultArray = [];
		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'ontology', [ 'term_id', 'term', 'ontology' ], [ 'pw_id' => $pwId ], __METHOD__,
			[ "ORDER BY `ontology`" ]
		);
		foreach ( $res as $row ) {
			$resultArray['Resultset'][] = $row;
		}
		$resp = new AjaxResponse( json_encode( $resultArray ) );
		$resp->setContentType( "application/json" );
		return $resp;
	}

	public static function getOntologyTagPath( $tagId ) {
		$ontologyId = self::getOntologyVersion( $tagId );
		$url = self::getBioPortalURL(
			'path', [ "ontologyId" => $ontologyId, "termId" => $tagId ]
		);
		$xml = simplexml_load_string( OntologyCache::fetchCache( "path", $url ) );

		if ( $xml->data->list->classBean->relations->entry ) {
			foreach ( $xml->data->list->classBean->relations->entry as $entry ) {
				if ( $entry->string == "Path" ) {
					$path = $entry->string[1];
				}
			}
		}
		return $path;
	}

	public static function getOntologyName( $ontId ) {
		global $wgOntologiesArray;
		foreach ( $wgOntologiesArray as $wgOntology ) {
			if ( substr( $ontId, 0, 2 ) == substr( $wgOntology[1], 0, 2 ) ) {
				$ontologyName = $wgOntology[0];
				break;
			}
		}
		return $ontologyName;
	}

	public static function getOntologyVersion( $ontId ) {
		global $wgOntologiesArray;
		foreach ( $wgOntologiesArray as $wgOntology ) {
			if ( substr( $ontId, 0, 2 ) == substr( $wgOntology[1], 0, 2 ) ) {
				$ontologyId = $wgOntology[2];
				break;
			}
		}
		return $ontologyId;
	}

	public static function getBioPortalURL( $functionName, $data ) {
		global $wgOntologiesBioPortalEmail, $wgOntologiesBioPortalSearchHits;
		switch ( $functionName ) {
		case "path":
			$url = "http://rest.bioontology.org/bioportal/virtual/rootpath/ontologyId/termId?"
				 . "email=$wgOntologiesBioPortalEmail";
			break;
		case "search":
			$url = "http://rest.bioontology.org/bioportal/search/searchTerm/?"
				 . "ontologyids=ontologyId&maxnumhits=$wgOntologiesBioPortalSearchHits&"
				 . "email=$wgOntologiesBioPortalEmail";
			break;
		case "tree":
			$url = "http://rest.bioontology.org/bioportal/virtual/ontology/ontologyId/conceptId?"
				 . "email=$wgOntologiesBioPortalEmail";
			break;
		}
		foreach ( $data as $key => $value ) {
			$url = str_replace( $key, $value, $url );
		}
		return $url;
	}

	public static function getBioPortalSearchResults( $searchTerm ) {
		global $wgOntologiesArray;
		$count = 0;

		foreach ( $wgOntologiesArray as $ontology ) {
			$ontologyIdArray[] = $ontology[2];
		}

		$ontologyId = implode( ",", $ontologyIdArray );

		$url = self::getBioPortalURL(
			"search", [ "ontologyId" => $ontologyId, "searchTerm" => $searchTerm ]
		);
		$xml = simplexml_load_string( OntologyCache::fetchCache( "search", $url ) );

		if ( isset( $xml->data->page->contents->searchResultList->searchBean ) ) {
			$resultArray = [];
			foreach ( $xml->data->page->contents->searchResultList->searchBean as $searchResult ) {
				$resultArray[$count]['label'] = str_replace(
					'"', '', (string)$searchResult->contents
				);
				$resultArray[$count]['id'] = (string)$searchResult->conceptIdShort;
				$resultArray[$count]['ontology'] = (string)$searchResult->ontologyDisplayLabel;
				$count++;
			}
		}
		if ( $count == 0 ) {
			$resultArray[$count]['label'] = "No results !";
			$resultArray[$count]['id'] = "No results !";
		}
		sort( $resultArray );
		$resultArr["ResultSet"]["Result"] = $resultArray;
		$resultJSON = json_encode( $resultArr );

		return $resultJSON;
	}

	public static function getBioPortalTreeResults( $termId ) {
		$ontologyId = self::getOntologyVersion( $termId );
		$url = self::getBioPortalURL(
			"tree", [ "ontologyId" => $ontologyId, "conceptId" => $termId ]
		);
		$xml = simplexml_load_string( OntologyCache::fetchCache( "tree", $url ) );
		foreach ( $xml->data->classBean->relations->entry as $entry ) {
			if ( $entry->string == "SubClass" ) {
				foreach ( $entry->list->classBean as $subConcepts ) {
					$tempVar = $subConcepts->label . " - " . $subConcepts->id;
					if ( $subConcepts->relations->entry->int == "0" ) {
						$tempVar .= "||";
					}
					$resultArray[] = $tempVar;
				}
			}
		}

		sort( $resultArray );
		$resultArr["ResultSet"]["Result"] = $resultArray;
		$resultJSON = json_encode( $resultArr );
		return $resultJSON;
	}
}
class_alias( "WikiPathways\\Ontology\\Edit", "OntologyFunctions" );
