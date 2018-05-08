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

class SinglePathwaysPager extends BasePathwaysPager {
	/**
	 * @param BrowsePathways $page object to use
	 */
	public function __construct( BrowsePathways $page ) {
		parent::__construct( $page );

		$this->mLimitsShown = [ 5 ];
		$this->mDefaultLimit = 5;
		$this->mLimit = 5;
	}

	function getStartBody() {
		return "<div id='singleMode'>";
	}

	function getEndBody() {
		return "</div><div id='singleModeSlider' style='clear: both'></div>";
	}

	function getNavigationBar() {
		/* Nothing */
	}

	function formatRow( $row ) {
		$title = Title::newFromDBkey( $this->nsName .":". $row->page_title );
		$pathway = Pathway::newFromTitle( $title );

		return $this->getThumb( $pathway, $this->formatTags( $title ), 100, false );
	}
}
