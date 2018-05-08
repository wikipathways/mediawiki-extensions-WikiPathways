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
use Html;

class ListPathwaysPager extends BasePathwaysPager {
	protected $columnItemCount;
	protected $columnIndex;
	protected $columnSize = 100;

	const COLUMN_COUNT = 3;

	/**
	 * @param BrowsePathways $page object to use
	 */
	public function __construct( BrowsePathways $page ) {
		parent::__construct( $page );

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
	 * No infinite paging, both are the same.
	 *
	 * @return string bit of html
	 */
	public function getTopNavigationBar() {
		return $this->getNavigationBar();
	}

	/**
	 * No infinite paging, both are the same.
	 *
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
