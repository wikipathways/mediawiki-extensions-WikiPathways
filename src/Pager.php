<?php

class PathwaysPagerFactory {
	static function get( $type, $species, $tag, $sortOrder ) {
		switch( $type ) {
			case 'list':
				return new ListPathwaysPager( $species, $tag, $sortOrder );
				break;
			case 'single':
				return new SinglePathwaysPager( $species, $tag, $sortOrder );
				break;
			default:
				return new ThumbPathwaysPager( $species, $tag, $sortOrder );
		}
	}
}

class ListPathwaysPager extends BasePathwaysPager {
	protected $columnItemCount;
	protected $columnIndex;
	protected $columnSize = 100;
	const columnCount = 3;

	function __construct( $species, $tag, $sortOrder ) {
		parent::__construct( $species, $tag, $sortOrder );

		# We know we have 300, so we'll put 100 in each column
		$this->mLimitsShown = array( $this->columnSize * self::columnCount );
		$this->mDefaultLimit = $this->columnSize * self::columnCount;
		$this->mLimit = $this->columnSize * self::columnCount;
		$this->columnItemCount = 0;
		$this->columnIndex = 0;
	}

	function preprocessResults( $result ) {
		$rows = $result->db->numRows( $result );

		if( $rows < $this->mLimit ) {
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
		$opts = array( 'parsemag', 'escapenoentities' );

		if( isset( $queries['prev'] ) && $queries['prev'] ) {
			$link .= $this->getSkin()->makeKnownLinkObj( $this->getTitle(),
				wfMsgExt( 'prevn', $opts, $wgLang->formatNum( $this->mLimit ) ),
				wfArrayToCGI( $queries['prev'], $this->getDefaultQuery() ), '', '',
				"style='float: left;'" );
		}

		if( isset( $queries['next'] ) && $queries['next'] ) {
			$link .= $this->getSkin()->makeKnownLinkObj( $this->getTitle(),
				wfMsgExt( 'nextn', $opts, $wgLang->formatNum( $this->mLimit ) ),
				wfArrayToCGI( $queries['next'], $this->getDefaultQuery() ), '', '',
				"style='float: right;'" );
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

		if( $this->columnItemCount === $this->columnSize ) {
			$row = '</ul></li> <!-- end of column -->';
			$this->columnItemCount = 0;
			$this->columnIndex++;
		} else {
			$row = "";
		}

		if( $this->columnItemCount === 0 ) {
			$row .= '<li><ul> <!-- start of column -->';
		}
		$this->columnItemCount++;

		$endRow = "</li>";
		$row .= "<li>";
		if( $this->hasRecentEdit( $title ) ) {
			$row .= "<b>";
			$endRow = "</b></li>";
		}

		$row .= '<a href="' . $title->getFullURL() . '">' . $pathway->getName();

		if( $this->species === '---' ) {
			$row .= " (". $pathway->getSpeciesAbbr() . ")";
		}

		return  "$row</a>" . $this->formatTags( $title ) . $endRow;
	}
}

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
		$opts = array( 'parsemag', 'escapenoentities' );

		if( isset( $queries['next'] ) && $queries['next'] ) {
			$link = $this->getSkin()->makeKnownLinkObj( $this->getTitle(),
				wfMsgExt( 'nextn', $opts, $wgLang->formatNum( $this->mLimit ) ),
				wfArrayToCGI( $queries['next'], $this->getDefaultQuery() ), '', '',
				"class='infinite-more-link'" );
		}

		return $link;;
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
		if( $this->hasRecentEdit( $title ) ) {
			$row = "<b>";
			$endRow = "</b>";
		}

		return $row.$this->getThumb( $pathway, $this->formatTags( $title ) ).$endRow;
	}
}

class SinglePathwaysPager extends BasePathwaysPager {
	function __construct( $species, $tag, $sortOrder ) {
		parent::__construct( $species, $tag, $sortOrder );

		$this->mLimitsShown = array( 5 );
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
