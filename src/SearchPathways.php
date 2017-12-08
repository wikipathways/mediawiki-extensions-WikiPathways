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
 */
namespace WikiPathways;

class SearchPathways extends \SpecialPage {
	private $this_url;

	public function __construct( $empty = null ) {
		parent::__construct( "SearchPathways" );
	}

	public function execute( $par ) {
		global $wfSearchPagePath;

		$out = $this->getOutput();
		$req = $this->getRequest();
		$this->setHeaders();
		$this->this_url = SITE_URL . '/index.php';
		$out->setPagetitle( wfMessage( "searchpathways" ) );

		$query   = $req->getVal( 'query' );
		$species = $req->getVal( 'species' );
		$ids     = $req->getVal( 'ids' );
		$codes   = $req->getVal( 'codes' );
		$type    = $req->getVal( 'type' );

		// SET DEFAULTS
		if ( !$type || $type == '' ) {
			$type = 'query';
		}
		if ( ( !$query || $query == '' ) && $type == 'query' ) {
			$query = 'glucose';
		}
		if ( $species == 'ALL SPECIES' ) {
			$species = '';
		}

		$out->addModules( [ "wpi.searchPathways" ] );
		// Submit button pressed
		if ( $req->getVal( 'doSearch' ) == '1' ) {
			global $wgScriptPath;

			$this->showForm( $query, $species, $ids, $codes, $type );
			$out->addHTML(
				"<DIV id='searchResults'>"
				. "<IMG src='$wgScriptPath/extensions/WikiPathways/images/progress.gif'/> "
				. "Loading...</DIV>"
			);
			$out->addHTML( "<DIV id='more'></DIV>" );
			$out->addHTML( "<DIV id='error'></DIV>" );
		} else {
			$this->showForm( $query, $species, $ids, $codes, $type );
		}
	}

	public function showForm( $query, $species = '', $ids = '', $codes = '', $type ) {

		$out = $this->getOutput();
		# For now, hide the form when id search is done (no gui for that yet)
		$hide = "";
		$xrefInfo = "";
		if ( $type != 'query' ) {
			$hide = "style='display:none'";
			$xrefs = SearchPathwaysAjax::parToXref( $ids, $codes );
			$xrefInfo = "Pathways by idenifier: ";
			$xstr = [];
			foreach ( $xrefs as $x ) {
				$xstr[] = "{$x->getId()} ({$x->getSystem()})";
			}
			$xrefInfo .= implode( ", ", $xstr );
			$xrefInfo = "<P>$xrefInfo</P>";
		}

		$form_method = "get";
		$form_extra = "";
		$search_form = "$xrefInfo<FORM $hide id='searchForm' action='$this->this_url' method='get'>
				<table cellspacing='7'><tr valign='middle'><td>"
		// <input type='radio' name='type' value='query' CHECKED>Keywords
		// <input type='radio' name='type' value='xref'>Identifiers
		// <tr><td>
		."Search for: <input type='text' name='query' value='$query' size='25'>
				</td><td><select name='species'>";
		$allSpecies = Pathway::getAvailableSpecies();
		$search_form .= "<option value='ALL SPECIES'"
					 . ( $species == '' ? ' SELECTED' : '' ). ">ALL SPECIES";
		foreach ( $allSpecies as $sp ) {
			$search_form .= "<option value='$sp'" . ( $sp == $species ? ' SELECTED' : '' ) . ">$sp";
		}
		$search_form .= '</select>';
		$search_form .= "<input type='hidden' name='title' value='Special:SearchPathways'>
				<input type='hidden' name='doSearch' value='1'>
				</td><td><input type='submit' value='Search'></td></tr>
				<tr valign='top'><td colspan='3'><font size='-3'><i>&nbsp;&nbsp;&nbsp;
Tip: use AND, OR, *, ?, parentheses or quotes</i></font></td></tr>
				</table>";

		$search_form .= "<input type='hidden' name='ids' value='$ids'/>";
		$search_form .= "<input type='hidden' name='codes' value='$codes'/>";
		$search_form .= "<input type='hidden' name='type' value='$type'/>";

		$search_form .= "</FORM><BR>";

		$out->addHTML( "
						<DIV id='search' >
			$search_form
			</DIV>
						" );
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
