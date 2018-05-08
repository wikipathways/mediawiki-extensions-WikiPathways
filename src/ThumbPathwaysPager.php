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

	/**
	 * @param BrowsePathways $page object to use
	 */
	public function __construct( BrowsePathways $page ) {
		parent::__construct( $page );

		$this->mLimit = 10;
	}

	/**
	 * @return string bit of html
	 */
	public function getStartBody() {
		return "<div class='infiniteContainer'>";
	}

	/**
	 * @return string bit of html
	 */
	public function getEndBody() {
		return "</div>";
	}

	/**
	 * @return string bit of html
	 */
	public function getNavigationBar() {
		$linkTexts = [
			'prev' => '',
			'next' => $this->msg( 'nextn' )->numParams( $this->mLimit )->escaped(),
			'first' => '',
			'last' => ''
		];
		$pagingLinks = $this->getPagingLinks( $linkTexts );

		$link = \Linker::linkKnown(
			$this->getTitle(),
			$pagingLinks['next'],
			[ "class" => 'infiniteMoreLink' ]
		);

		return $link;
	}

	/**
	 * @return string bit of html
	 */
	public function getTopNavigationBar() {
		return "";
	}

	/**
	 * @return string bit of html
	 */
	public function getBottomNavigationBar() {
		return $this->getNavigationBar();
	}

	/**
	 * @param array|stdClass $row Database row
	 * @return string
	 */
	public function formatRow( $row ) {
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
