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
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways;

use ResultWrapper;
use Title;

class ListPathwaysPager extends BasePathwaysPager {
	protected $columnItemCount;
	protected $columnIndex;
	protected $columnSize = 100;

	const COLUMN_COUNT = 3;

	/**
	 * @param string $species to limit to
	 * @param string $tag to match
	 * @param string $sortOrder to display in
	 */
	public function __construct( $species, $tag, $sortOrder ) {
		parent::__construct( $species, $tag, $sortOrder );

		// We know we have 300, so we'll put 100 in each column
		$this->mLimitsShown = [ $this->columnSize * self::COLUMN_COUNT ];
		$this->mDefaultLimit = $this->columnSize * self::COLUMN_COUNT;
		$this->mLimit = $this->columnSize * self::COLUMN_COUNT;
		$this->columnItemCount = 0;
		$this->columnIndex = 0;
	}

	/**
	 * @param ResultWrapper $result from query
	 */
	protected function preprocessResults( $result ) {
		$rows = $result->numRows( $result );

		if ( $rows < $this->mLimit ) {
			$this->columnSize = (int)( $rows / self::COLUMN_COUNT );
		}
	}

	/**
	 * @return string bit of html
	 */
	protected function getStartBody() {
		return "<ul id='browseListBody'>";
	}

	/**
	 * @return string bit of html
	 */
	protected function getEndBody() {
		return "</ul></li> <!-- end of column --></ul> <!-- getEndBody -->";
	}

	/**
	 * @return string bit of html
	 */
	public function getNavigationBar() {
		global $wgLang;

		$link = "";
		$queries = $this->getPagingQueries();
		$opts = [ 'parsemag', 'escapenoentities' ];

		if ( isset( $queries['prev'] ) && $queries['prev'] ) {
			$link .= $this->getSkin()->makeKnownLinkObj(
				$this->getTitle(),
				wfMessage( 'prevn', $opts, $wgLang->formatNum( $this->mLimit ) )->text(),
				wfArrayToCGI( $queries['prev'], $this->getDefaultQuery() ), '', '',
				"style='float: left;'"
			);
		}

		if ( isset( $queries['next'] ) && $queries['next'] ) {
			$link .= $this->getSkin()->makeKnownLinkObj(
				$this->getTitle(),
				wfMessage( 'nextn', $opts, $wgLang->formatNum( $this->mLimit ) )->text(),
				wfArrayToCGI( $queries['next'], $this->getDefaultQuery() ), '', '',
				"style='float: right;'"
			);
		}

		return $link;
	}

	/**
	 * @return string bit of html
	 */
	public function getTopNavigationBar() {
		$bar = $this->getNavigationBar();

		return "<div class='listNavBar top'>$bar</div>";
	}

	/**
	 * @return string bit of html
	 */
	public function getBottomNavigationBar() {
		$bar = $this->getNavigationBar();

		return "<div class='listNavBar bottom'>$bar</div>";
	}

	/**
	 * @param array|stdClass $row Database row
	 * @return string
	 */
	public function formatRow( $row ) {
		$title = Title::newFromDBkey( $this->nsName .":". $row->page_title );
		$pathway = Pathway::newFromTitle( $title );

		if ( $this->columnItemCount === $this->columnSize ) {
			$row = '</ul></li> <!-- end of column -->';
			$this->columnItemCount = 0;
			$this->columnIndex++;
		} else {
			$row = "";
		}

		if ( $this->columnItemCount === 0 ) {
			$row .= '<li><ul> <!-- start of column -->';
		}
		$this->columnItemCount++;

		$endRow = "</li>";
		$row .= "<li>";
		if ( $this->hasRecentEdit( $title ) ) {
			$row .= "<b>";
			$endRow = "</b></li>";
		}

		$row .= '<a href="' . $title->getFullURL() . '">' . $pathway->getName();

		if ( $this->species === '---' ) {
			$row .= " (". $pathway->getSpeciesAbbr() . ")";
		}

		return "$row</a>" . $this->formatTags( $title ) . $endRow;
	}
}
