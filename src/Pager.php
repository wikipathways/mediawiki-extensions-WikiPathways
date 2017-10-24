<?php

class ListPathwaysPager extends BasePathwaysPager {
	protected $columnItemCount;
	protected $columnIndex;
	protected $columnSize = 100;
	const columnCount = 3;

	function __construct( $species, $tag, $sortOrder ) {
		parent::__construct( $species, $tag, $sortOrder );

		// We know we have 300, so we'll put 100 in each column
		$this->mLimitsShown = [ $this->columnSize * self::columnCount ];
		$this->mDefaultLimit = $this->columnSize * self::columnCount;
		$this->mLimit = $this->columnSize * self::columnCount;
		$this->columnItemCount = 0;
		$this->columnIndex = 0;
	}

	function preprocessResults( $result ) {
		$rows = $result->db->numRows( $result );

		if ( $rows < $this->mLimit ) {
			$this->columnSize = (int)( $rows / self::columnCount );
		}
	}

	function getStartBody() {
		return "<ul id='browseListBody'>";
	}

	function getEndBody() {
		return "</ul></li> <!-- end of column --></ul> <!-- getEndBody -->";
	}

	function getNavigationBar() {
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

	function getTopNavigationBar() {
		$bar = $this->getNavigationBar();

		return "<div class='listNavBar top'>$bar</div>";
	}

	function getBottomNavigationBar() {
		$bar = $this->getNavigationBar();

		return "<div class='listNavBar bottom'>$bar</div>";
	}

	function formatRow( $row ) {
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
