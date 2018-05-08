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
 * @author     Mark A. Hershberger
 * @package    MediaWiki
 * @subpackage SpecialPage
 */
namespace WikiPathways;

use HTMLForm;
use OOUI\FormLayout;
use OOUI\FieldLayout;
use OOUI\DropdownInputWidget;
use OOUI\HorizontalLayout;
use SpecialPage;
use Title;

class BrowsePathways extends SpecialPage {

	private $offset;
	private $name = 'BrowsePathways';
	// Second is default
	static private $views = [ "list", "thumbs" ];

	// Determines, which message describes the input field 'nsfrom' (->SpecialPrefixindex.php)
	public $nsfromMsg = 'browsepathwaysfrom';
	private $species;
	private $tag;

	/**
	 * @param string $empty ignored
	 * @SuppressWarnings(UnusedFormalParameter)
	 */
	public function __construct( $empty = null ) {
		parent::__construct( $this->name );
	}

	/**
	 * @param string $par url stem
	 * @SuppressWarnings(UnusedFormalParameter)
	 */
	public function execute( $par = null ) {
		$this->getOutput()->setPagetitle( wfMessage( "browsepathways" ) );

		// Back compat for old links.
		$this->species = $this->getRequest()->getVal( "browse", 'Homo_sapiens' );
		$this->tag     = $this->getRequest()->getVal( "tag", CurationTag::defaultTag() );
		$this->view    = $this->getRequest()->getVal( "view", self::$views[1] );

		// Also need to pass
		$this->offset = $this->getRequest()->getVal( "offset", null );

		$this->pathwayForm();

		$pager = PathwaysPagerFactory::get( $this );

		// We have different nav bars for inifinite paging
		$this->getOutput()->addHTML(
			$pager->getTopNavigationBar() .
			$pager->getBody() .
			$pager->getBottomNavigationBar()
		);
	}

	/**
	 * @return string
	 */
	public function getSpecies() {
		return $this->species;
	}

	/**
	 * @return string
	 */
	public function getTag() {
		return $this->tag;
	}

	/**
	 * @return string
	 */
	public function getView() {
		return $this->view;
	}

	private function getSpeciesSelectionList() {
		$arr = Pathway::getAvailableSpecies();
		asort( $arr );
		$all = wfMessage( 'browsepathways-all-species' )->plain();
		$arr[] = $all;

		$list = [];
		foreach ( $arr as $label ) {
			$value = Title::newFromText( $label )->getDBKey();
			if ( $label === $all ) {
				$value = "---";
			}
			$list[] = [ 'data' => $value, 'label' => $label ];
		}
		return $list;
	}

	private function getTagSelectionList() {
		$ret = [];
		foreach ( CurationTag::getUserVisibleTagNames() as $display => $tag ) {
			if ( is_array( $tag ) ) {
				$tag = "---";
			}
			$ret[] = [ "label" => $display, "data" => $tag ];
		}
		return $ret;
	}

	private function getViewSelectionList() {
		$ret = [];
		foreach ( self::$views as $view ) {
			$ret[] = [
				"label" => wfMessage( "browsepathways-view-$view" )->plain(),
				"data" => $view
			];
		}
		return $ret;
	}

	/**
	 * HTML for the top form
	 *
	 * @return HTMLForm
	 */
	public function pathwayForm() {
		$title = SpecialPage::getTitleFor( $this->name );

		$this->getOutput()->addModules( 'wpi.browsePathwaysScript' );
		$this->getOutput()->enableOOUI();
		$this->getOutput()->addHTML( new FormLayout( [
			"method" => "GET",
			"action" => $title,
			"id" => "browsePathwayForm",
			"items" => [
				new HorizontalLayout( [
					"label" => "Form layout",
					"items" => [
						new FieldLayout(
							new DropdownInputWidget( [
								"name" => "browse",
								"id" => "browseSelection",
								"options" => $this->getSpeciesSelectionList(),
								"value" => $this->getSpecies(),
							] ),
							[
								"label" => wfMessage( "browsepathways-select-species" )->plain()
							]
						),
						new FieldLayout(
							new DropdownInputWidget( [
								"name" => "tag",
								"id" => "tagSelection",
								"options" => $this->getTagSelectionList(),
								"value" => $this->getTag(),
							] ),
							[
								"label" => wfMessage( "browsepathways-select-collection" )->plain()
							]
						),
						new FieldLayout(
							new DropdownInputWidget( [
								"name" => "view",
								"id" => "viewSelection",
								"options" => $this->getViewSelectionList(),
								"value" => $this->getView(),
							] ),
							[
								"label" => wfMessage( "browsepathways-select-view" )->plain()
							]
						),
					]
				] )
			]
		] ) );
	}
}
