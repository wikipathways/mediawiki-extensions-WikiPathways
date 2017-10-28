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
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 *
 * @author
 * @author Mark A. Hershberger
 */
namespace WikiPathways;

use QueryPage;

class PopularPathways extends QueryPage {
	function __construct() {
		parent::__construct( "PopularPathways" );
	}

	function getName() {
		return "PopularPathwaysPage";
	}

	function isExpensive() {
		# page_counter is not indexed
		return true;
	}

	function isSyndicated() {
		return false;
	}

	function getSQL() {
		$dbr =& wfGetDB( DB_SLAVE );
		$page = $dbr->tableName( 'page' );

		return
			"SELECT 'Popularpages' as type,
					page_namespace as namespace,
					page_title as title,
				page_id as id,
					page_counter as value
			FROM $page
			WHERE page_namespace=".NS_PATHWAY."
			AND page_is_redirect=0";
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgContLang;
		$taggedIds = CurationTag::getPagesForTag( 'Curation:Tutorial' );
		if ( in_array( $result->id, $taggedIds ) ) {
			return null;
		}
		$pathway = Pathway::newFromTitle( $result->title );
		// Skip private pathways
		if ( !$pathway->isReadable() ) {
			return null;
		}
		$title = Title::makeTitle(
			$result->namespace,
			$pathway->getSpecies().":".$pathway->getName()
		);
		$id = Title::makeTitle( $result->namespace, $result->title );
		$link = $skin->makeKnownLinkObj(
			$id, htmlspecialchars( $wgContLang->convert(
				$title->getBaseText()
			) ) );
		$nv = wfMsgExt( 'nviews', [ 'parsemag', 'escape' ],
			$wgLang->formatNum( $result->value )
		);
		return wfSpecialList( $link, $nv );
	}
}
