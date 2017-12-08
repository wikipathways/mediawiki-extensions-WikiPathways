<?php
/*
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

class PathwayIndex {
	// Field names
	public static $f_source = 'source';
	public static $f_name = 'name';
	public static $f_organism = 'organism';
	public static $f_textlabel = 'textlabel';
	public static $f_category = 'category';
	public static $f_description = 'description';
	public static $f_id = 'id';
	public static $f_id_database = 'id.database';
	public static $f_x_id = 'x.id';
	public static $f_x_id_database = 'x.id.database';
	public static $f_graphId = 'graphId';
	public static $f_left = 'left';
	public static $f_right = 'right';
	public static $f_mediator = 'mediator';
	public static $f_literature_author = 'literature.author';
	public static $f_literature_title = 'literature.title';
	public static $f_literature_pubmed = 'literature.pubmed';
	public static $f_ontology = 'ontology';
	public static $f_ontology_id = 'ontologyId';
	public static $f_source_id = 'sourceId';

	/**
	 * Get a list of pathways by a datanode xref.
	 * @param XRef|array $xrefs The XRef object or an array of XRef objects. If
	 * the XRef->getSystem() field is empty, the search will not
	 * restrict to any database.
	 * @return An array with the results as PathwayDocument objects
	 **/
	public static function searchByXref( $xrefs ) {
		if ( !is_array( $xrefs ) ) {
			$xrefs = [ $xrefs ];
		}

		$ids = [];
		$codes = [];

		foreach ( $xrefs as $xref ) {
			$ids[] = $xref->getId();
			if ( $xref->getSystem() ) {
				$codes[] = $xref->getSystem();
			}
		}
		return IndexClient::queryXrefs( $ids, $codes );
	}

	/**
	 * Searches on all text fields:
	 * name, organism, textlabel, category, description
	 * @param string $query The query (e.g. 'apoptosis')
	 * @param string $organism Optional, specify organism name to limit search by organism.
	 * Leave empty to search on all organisms.
	 * @return An array with the results as PathwayDocument objects
	 **/
	public static function searchByText( $query, $organism = false ) {
		$query = self::queryToAllFields(
			$query,
			[
				self::$f_name,
				self::$f_textlabel,
				self::$f_category,
				self::$f_description,
				self::$f_ontology,
				self::$f_ontology_id,
				self::$f_source_id
			]
		);
		if ( $organism ) {
			$query = "($query) AND " . self::$f_organism . ":\"$organism\"";
		}
		return IndexClient::query( $query );
	}

	/**
	 * Searches Pathway title
	 * @param string $query The query (e.g. 'apoptosis')
	 * @param string $organism Optional, specify organism name to limit search by organism.
	 * Leave empty to search on all organisms.
	 * @return array with the results as PathwayDocument objects
	 **/
	public static function searchByTitle( $query, $organism = false ) {
		$query = self::queryToAllFields(
			$query,
			[
				self::$f_name,
			]
		);

		if ( $organism ) {
			$query = "($query) AND " . self::$f_organism . ":\"$organism\"";
		}
		return IndexClient::query( $query );
	}

	/**
	 * Searches on literature fields:
	 * literature.pubmed, literature.author, literature.title
	 * @param string $query The query (can be pubmed id, author or title keyword).
	 * @return array with the results as SearchHit objects
	 **/
	public static function searchByLiterature( $query ) {
		$query = self::queryToAllFields(
			$query,
			[
				self::$f_literature_author,
				self::$f_literature_title,
				self::$f_literature_pubmed,
			]
		);
		return IndexClient::query( $query );
	}

	public static function searchInteractions( $query ) {
		$query = self::queryToAllFields(
			$query,
			[
				self::$f_right,
				self::$f_left,
				self::$f_mediator
			]
		);
		return IndexClient::query( $query );
	}

	public static function listPathwayXrefs( $pathway, $code, $local='TRUE' ) {
		return IndexClient::xrefs( $pathway, $code, $local );
	}

	static function pathwayFromSource( $source ) {
		return Pathway::newFromTitle( $source );
	}

	private static function queryToAllFields( $queryStr, $fields ) {
		$q = '';
		foreach ( $fields as $f ) {
			$q .= "$f:($queryStr) ";
		}
		return $q;
	}
}
