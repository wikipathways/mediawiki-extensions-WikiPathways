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
 * @package MediaWiki
 * @subpackage SpecialPage
 */
namespace WikiPathways;

class BrowsePathways extends \SpecialPage {

	protected $maxPerPage  = 960;
	protected $name        = 'BrowsePathways';
	static private $defaultView = "thumbs";
	static private $views  = [ "list", "thumbs" ];
	static private $sortOptions = [ 'A-Z', 'creation date', 'last edit date', 'most viewed' ];

	# Determines, which message describes the input field 'nsfrom' (->SpecialPrefixindex.php)
	public $nsfromMsg = 'browsepathwaysfrom';
	protected $species;
	protected $tag;
	protected $sortOrder;

	public function __construct( $empty = null ) {
		parent::__construct( $this->name );
	}

	public function execute( $par ) {
		global $wgOut, $wgRequest;

		$wgOut->setPagetitle( wfMessage( "browsepathways" ) );

		$this->species   = $wgRequest->getVal( "browse", 'Homo_sapiens' );
		$this->tag       = $wgRequest->getVal( "tag", CurationTag::defaultTag() );
		$this->view      = $wgRequest->getVal( "view", self::$defaultView );
		$this->sortOrder = $wgRequest->getVal( "sort", 0 );
		$nsForm = $this->pathwayForm();

		$wgOut->addHtml( $nsForm . '<hr />' );

		$pager = PathwaysPagerFactory::get( $this->view, $this->species, $this->tag, $this->sortOrder );
		$wgOut->addHTML(
			$pager->getTopNavigationBar() .
			$pager->getBody() .
			$pager->getBottomNavigationBar()
		);
		return;
	}

	protected function getSortingOptionsList() {
		$arr = self::$sortOptions;

		$sel = "\n<select onchange='this.form.submit()' name='sort' class='namespaceselector'>\n";
		foreach ( $arr as $key => $label ) {
			$sel .= $this->makeSelectionOption( $key, $this->sortOrder, $label );
		}
		$sel .= "</select>\n";
		return $sel;
	}

	protected function getSpeciesSelectionList() {
		$arr = Pathway::getAvailableSpecies();
		asort( $arr );
		$all = wfMsg( 'browsepathways-all-species' );
		$arr[] = $all;
		/* $arr[] = wfMsg('browsepathways-uncategorized-species'); Don't look for uncategorized species */

		$sel = "\n<select onchange='this.form.submit()' name='browse' class='namespaceselector'>\n";
		foreach ( $arr as $label ) {
			$value = Title::newFromText( $label )->getDBKey();
			if ( $label === $all ) {
				$value = "---";
			}
			$sel .= $this->makeSelectionOption( $value, $this->species, $label );
		}
		$sel .= "</select>\n";
		return $sel;
	}

	protected function getTagSelectionList() {
		$sel = "<select onchange='this.form.submit()' name='tag' class='namespaceselector'>\n";
		foreach ( CurationTag::getUserVisibleTagNames() as $display => $tag ) {
			if ( is_array( $tag ) ) {
				$tag = "---";
			}
			$sel .= $this->makeSelectionOption( $tag, $this->tag, $display );
		}
		$sel .= "</select>\n";
		return $sel;
	}

	protected function getViewSelectionList() {
		$sel = "\n<select onchange='this.form.submit()' name='view' class='namespaceselector'>\n";
		foreach ( self::$views as $s ) {
			$sel .= $this->makeSelectionOption( $s, $this->view, wfMsg( "browsepathways-view-" . $s ) );
		}
		$sel .= "</select>\n";
		return $sel;
	}

	protected function makeSelectionOption( $item, $selected, $display = null ) {
		$attr = [ "value" => $item ];
		if ( null === $display ) {
			$display = $item;
		}
		if ( $item == $selected ) {
			$attr['selected'] = 1;
		}

		return "\t" . Xml::element( "option", $attr, $display ) . "\n";
	}

	/**
	 * HTML for the top form
	 * @param string Species to show pathways for
	 * @return string
	 */
	public function pathwayForm() {
		global $wgScript;
		$t = SpecialPage::getTitleFor( $this->name );

		/**
		 * Species Selection
		 */
		$speciesSelect = $this->getSpeciesSelectionList();
		$tagSelect     = $this->getTagSelectionList();
		$viewSelect    = $this->getViewSelectionList();
		$sortSelect    = $this->getSortingOptionsList();
		$submitbutton = '<noscript><input type="submit" value="Go" name="pick" /></noscript>';

		$out = "<form method='get' action='{$wgScript}'>";
		$out .= '<input type="hidden" name="title" value="'.$t->getPrefixedText().'" />';
		$out .= "
<table id='nsselect' class='allpages'>
	<tr>
		<td align='right'>". wfMsg( "browsepathways-select-species" ) ."</td>
		<td align='left'>$speciesSelect</td>
		<td align='right'>". wfMsg( "browsepathways-select-collection" ) ."</td>
		<td align='left'>$tagSelect</td>
		<td align='right'>". wfMsg( "browsepathways-select-view" ) ."</td>
		<td align='left'>$viewSelect</td>
		<td>$submitbutton</td>
	</tr>
</table>
";

		$out .= '</form>';
		return $out;
	}
}
