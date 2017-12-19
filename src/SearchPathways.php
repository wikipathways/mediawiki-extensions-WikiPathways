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
 * @author Alexander Pico <apico@gladstone.ucsf.edu>
 * @author Thomas Kelder <thomaskelder@gmail.com>
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways;

use HTMLForm;
use HTML;

class SearchPathways extends \SpecialPage {
	private $this_url;

	/**
	 * @param string $empty not used
	 */
	public function __construct( $empty = null ) {
		parent::__construct( "SearchPathways" );
	}

	/**
	 * @param string $par other url bits
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$req = $this->getRequest();

		$this->setHeaders();
		$this->this_url = SITE_URL . '/index.php';
		$out->setPagetitle( wfMessage( "searchpathways" ) );

		$query   = $req->getVal( 'query', 'glucose' );
		$species = $req->getVal( 'species' );
		$ids     = $req->getVal( 'ids' );
		$codes   = $req->getVal( 'codes' );
		$type    = $req->getVal( 'type', 'query' );

		// SET DEFAULTS
		if ( $species == 'ALL SPECIES' ) {
			$species = '';
		}

		$out->addModules( [ "wpi.searchPathways" ] );
		// Submit button pressed
		if ( $req->getVal( 'doSearch' ) == '1' ) {
			global $wgScriptPath;

			$this->showForm( $query, $species, $ids, $codes, $type );
			$out->addHTML(
				Html::rawElement(
					"div", [ "id" => "searchResults" ], Html::rawElement(
						"img",
						[ "src" => "$wgScriptPath/extensions/WikiPathways/images/progress.gif" ]
					) . "Loading..."
				) .
				Html::rawElement(
					"div", [ "id" => "more" ] ),
				Html::rawElement(
					"div", [ "id" => "error" ] )
			);
		} else {
			$this->showForm( $query, $species, $ids, $codes, $type );
		}
	}

	/**
	 * Show the form
	 *
	 * @param string $query what is being searched for
	 * @param string $species to which species it is restricted
	 * @param string $ids used by parToXref
	 * @param string $codes used by parToXref
	 * @param string $type if not "query" use parToXref
	 */
	public function showForm( $query, $species = '', $ids = '', $codes = '', $type ) {
		$out = $this->getOutput();
		# For now, hide the form when id search is done (no gui for that yet)
		$xrefInfo = "";
		$form = "";

		if ( $type !== 'query' ) {
			$xrefs = SearchPathwaysAjax::parToXref( $ids, $codes );
			$xrefInfo = "Pathways by idenifier: ";
			$xstr = [];
			foreach ( $xrefs as $x ) {
				$xstr[] = "{$x->getId()} ({$x->getSystem()})";
			}
			$xrefInfo .= implode( ", ", $xstr );
			$xrefInfo = "<P>$xrefInfo</P>";
		}

		$speciesSelect['ALL SPECIES'] = 'ALL SPECIES';
		foreach ( Pathway::getAvailableSpecies() as $sp ) {
			$speciesSelect[$sp] = $sp;
		}

		$formDescriptor = [
			"search" => [
				"label-message" => "wp-searchpathway-search",
				"class" => "HTMLTextField",
				"name" => "query",
				"default" => $query,
				"help" => wfMessage( "wp-searchpathway-search-help" )->plain(),
				"size" => 25
			],
			"species" => [
				"type" => "select",
				"name" => "species",
				"options" => $speciesSelect
			]
		];
		$searchForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$searchForm->setId( "searchForm" )
			->addHiddenField( "ids", $ids )
			->addHiddenField( "codes", $codes )
			->addHiddenField( "type", $type )
			->addHiddenField( "doSearch", 1 )
			->setSubmitText( wfMessage( "wp-searchpathway-dosearch" )->plain() )
			->setMethod( "get" )
			->prepareForm();

		$searchForm->displayForm( false );
		return $xrefInfo.$form;
	}

	public static function makeThumbNail(
		$pathway, $label = '', $href = '', $alt, $align = 'right', $id = 'thumb',
		$boxwidth = 300, $boxheight=false, $framed=false
	) {
		global $wgContLang;

		try {
			$pathway->getImage();
			$img = new Image( $pathway->getFileTitle( FILETYPE_IMG ) );
			$img->loadFromFile();
			$imgURL = $img->getURL();
		} catch ( Exception $e ) {
			$blank = "<div id=\"{$id}\" class=\"thumb t{$align}\">"
				   . "<div class=\"thumbinner\" style=\"width:200px;\">";
			$blank .= "Image does not exist";
			$blank .= '  <div class="thumbcaption" style="text-align:right">'
				   . $label."</div></div></div>";
			return str_replace( "\n", ' ', $blank );
		}

		$thumbUrl = '';
		$error = '';

		$width = $height = 0;
		if ( $img->exists() ) {
			$width  = $img->getWidth();
			$height = $img->getHeight();
		}
		if ( 0 == $width || 0 == $height ) {
			$width = $height = 220;
		}
		if ( $boxwidth == 0 ) {
			$boxwidth = 230;
		}
		if ( $framed ) {
			// Use image dimensions, don't scale
			$boxwidth  = $width;
			$boxheight = $height;
			$thumbUrl  = $img->getViewURL();
		} else {
			if ( $boxheight === false ) {
				$boxheight = -1;
			}
			$thumb = $img->getThumbnail( $boxwidth, $boxheight );
			if ( $thumb ) {
				$thumbUrl = $thumb->getUrl();
				$boxwidth = $thumb->width;
				$boxheight = $thumb->height;
			} else {
				$error = $img->getLastError();
			}
		}
		$oboxwidth = $boxwidth + 2;

		$more = htmlspecialchars( wfMessage( 'thumbnail-more' ) );
		$magnifyalign = $wgContLang->isRTL() ? 'left' : 'right';
		$textalign = $wgContLang->isRTL() ? ' style="text-align:right"' : '';

		$s = "<div id=\"{$id}\" class=\"thumb t{$align}\">"
		   . "<div class=\"thumbinner\" style=\"width:{$oboxwidth}px;\">";
		if ( $thumbUrl == '' ) {
			// Couldn't generate thumbnail? Scale the image client-side.
			$thumbUrl = $img->getViewURL();
			if ( $boxheight == -1 ) {
				// Approximate...
				$boxheight = intval( $height * $boxwidth / $width );
			}
		}
		if ( $error ) {
			$s .= htmlspecialchars( $error );
		} elseif ( !$img->exists() ) {
			$s .= "Image does not exist";
		} else {
			$s .= '<a href="'.$href.'" class="internal" title="'.$alt.'">'
			   . '<img src="'.$thumbUrl.'" alt="'.$alt.'" '
			   . 'width="'.$boxwidth.'" height="'.$boxheight.'" '
			   . 'longdesc="'.$href.'" class="thumbimage" /></a>';
		}
		$s .= '  <div class="thumbcaption"'.$textalign.'>'.$label."</div></div></div>";
		return str_replace( "\n", ' ', $s );
	}

	# The callback public function for converting the input text to HTML output
	public static function renderSearchPathwaysBox( &$parser ) {
		global $siteURL;

		$parser->disableCache();
		$output = <<<SEARCH
<form id="searchbox_cref" action="$siteURL/index.php">
<table width="190" frame="void" border="0">
<tr>
<td align="center" bgcolor="#eeeeee" border="0">
<input name="query" type="text" size="20%" />
<input type='hidden' name='title' value='Special:SearchPathways'>
<input type='hidden' name='doSearch' value='1'>
<tr><td valign="top" align="center" border="0"><input type="submit" name="sa" value="Search" />
</tr>
</table></form>
SEARCH;

		return [ $output, 'isHTML' => 1, 'noparse' => 1 ];
	}
}
