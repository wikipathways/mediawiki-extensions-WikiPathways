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

use Title;

class ThumbPathwaysPager extends BasePathwaysPager {

	function __construct( $species, $tag, $sortOrder ) {
		parent::__construct( $species, $tag, $sortOrder );

		$this->mLimit = 10;
	}

	function getStartBody() {
		return "<div class='infinite-container'>";
	}

	function getEndBody() {
		return "</div>";
	}

	function getNavigationBar() {
		global $wgLang;

		/* Link to nowhere by default */
		$link = "<a class='infinite-more-link' href='data:'></a>";

		$queries = $this->getPagingQueries();
		if ( isset( $queries['next'] ) && $queries['next'] ) {
			$link = \Linker::linkKnown(
				$this->getTitle(),
				wfMessage( 'nextn' )->params( $wgLang->formatNum( $this->mLimit ) )->text(),
				[ "class" => 'infinite-more-link' ],
				$queries['next']
			);
		}

		return $link;
;
	}

	function getTopNavigationBar() {
		return "";
	}

	function getBottomNavigationBar() {
		return $this->getNavigationBar();
	}

	/* From getDownloadURL in PathwayPage */
	function formatRow( $row ) {
		$title = Title::newFromDBkey( $this->nsName .":". $row->page_title );
		$pathway = Pathway::newFromTitle( $title );

		$endRow = "";
		$row = "";
		if ( $this->hasRecentEdit( $title ) ) {
			$row = "<b>";
			$endRow = "</b>";
		}

		return $row.$this->getThumb( $pathway, $this->formatTags( $title ) ).$endRow;
	}
}
