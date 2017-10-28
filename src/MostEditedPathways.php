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
 * @author Mark A. Hershberger
 */
namespace WikiPathways;

class MostEditedPathways extends \QueryPage {
	private $namespace;
	private $taggedIds;

	public function __construct() {
		parent::__construct( "MostEditedPathwaysPage" );
		$this->namespace = NS_PATHWAY;
	}

	function getName() {
		return "MostEditedPathwaysPage";
	}

	function isExpensive() {
		# page_counter is not indexed
		return true;
	}

	function isSyndicated() {
		return false;
	}

	function getSQL() {
		$dbr = wfGetDB( DB_SLAVE );
		list( $revision, $page ) = $dbr->tableNamesN( 'revision', 'page' );
		return
			"SELECT
				'Mostrevisions' as type,
				page_namespace as namespace,
				page_id as id,
				page_title as title,
				COUNT(*) as value
			FROM $revision
			JOIN $page ON page_id = rev_page
			WHERE page_namespace = " . $this->namespace . "
			AND page_is_redirect = 0
			GROUP BY 1,2,3
			HAVING COUNT(*) > 1";
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgContLang;
		$this->taggedIds = CurationTag::getPagesForTag( 'Curation:Tutorial' );
		if ( in_array( $result->id, $this->taggedIds ) ) {
			return null;
		}
		$pathway = Pathway::newFromTitle( $result->title );
		if ( !$pathway->isReadable() ) { return null; // Skip private pathways
		}
		$title = Title::makeTitle( $result->namespace, $pathway->getSpecies().":".$pathway->getName() );
		$id = Title::makeTitle( $result->namespace, $result->title );
		$text = $wgContLang->convert( "$result->value revisions" );
		$plink = $skin->makeKnownLinkObj( $id, htmlspecialchars( $wgContLang->convert( $title->getBaseText() ) ) );

		/* Not link to history for now, later on link to our own pathway history
		   $nl = wfMsgExt( 'nrevisions', array( 'parsemag', 'escape'),
		   $wgLang->formatNum( $result->value ) );
		   $nlink = $skin->makeKnownLinkObj( $nt, $nl, 'action=history' );
		*/

		return wfSpecialList( $plink, $text );
	}
}
