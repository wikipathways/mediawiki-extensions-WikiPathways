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
 * @author Thomas Kelder <thomaskelder@gmail.com>
 * @author Alexander Pico <apico@gladstone.ucsf.edu>
 *
 * Code modernized and brought into 1.2x MediaWiki by
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways;

use LocalFile;
use RepoGroup;
use Title;

class ImageLink {
	/**
	 * Modified from pathwayThumb.php
	 * Insert arbitrary images as thumbnail links to any SPECIAL,
	 * PATHWAY, HELP or MAIN page, or external link.
	 *
	 * Usage: Special page example:
	 *          {{#imgLink:Wishlist_thumb_200.jpg|200|center
	 *            |Wish list page|special|SpecialWishList|Wish list}}
	 *        Pathway page example:
	 *          {{#imgLink:Sandbox_thumb_200.jpg|200|center|Sandbox page|pathway|WP274|Sandbox}}
	 *        Main page example:
	 *          {{#imgLink:Download_all_thumb_200.jpg|200|center|Download page|
	 *            |Download_Pathways|Download pathways}}
	 * 	      External link example:
	 *          {{#imgLink:WikiPathwaysSearch2.png|200|center|
	 *            |Help|{{FULLPAGENAME}}/WikiPathwaysSearch|Search}}
	 *
	 * @param Parser &$parser object
	 * @param string $img image filename
	 * @param int $width display width
	 * @param string $align horizonal alignment
	 * @param string $caption caption
	 * @param string $namespace (special, pathway, main (default), or external)
	 * @param string $pagetitle (stable id for pathways, e.g., WP274)
	 * @param string $tooltip tooltip.
	 * @return array
	 */
	public static function renderImageLink(
		&$parser,
		$img,
		$width = 200,
		$align = '',
		$caption = '',
		$namespace = '',
		$pagetitle = '',
		$tooltip = ''
	) {
		$parser->disableCache();
		try {
			// This can be quite dangerous (injection),
			$caption = html_entity_decode( $caption );
			// we would rather parse wikitext, let me know if
			// you know a way to do that (TK)

			$output = self::makeImageLinkObj(
				$img, $caption, $namespace, $pagetitle, $tooltip, $align, $width
			);

		} catch ( Exception $e ) {
			return "invalid image link: $e";
		}
		return [ $output, 'isHTML' => 1, 'noparse' => 1 ];
	}

	/** MODIFIED FROM Linker.php
	 * Make HTML for a thumbnail including image, border and caption
	 *
	 * @param Object $img image filename
	 * @param string $label to use
	 * @param string $namespace (special, pathway, main (default), or external)
	 * @param string $pagetitle (stable id for pathways, e.g., WP274)
	 * @param string $alt text
	 * @param string $align horizonal alignment
	 * @param int $boxwidth display width
	 * @return string
	 */
	public static function makeImageLinkObj(
		$img,
		$label = '',
		$namespace = '',
		$pagetitle = '',
		$alt,
		$align = 'right',
		$boxwidth = 180
	) {
		global $wgContLang;
		$boxheight = false;
		$framed = false;

		$img = new LocalFile(
			Title::makeTitleSafe( NS_FILE, $img ),
			RepoGroup::singleton()->getLocalRepo()
		);
		$href = '';

		switch ( $namespace ) {
		case 'special':
			$title = Title::newFromText( $pagetitle, NS_SPECIAL );
			if ( $title ) {
				$href = $title->getFullUrl();
			}
			break;
		case 'pathway':
			$title = Title::newFromText( $pagetitle, NS_PATHWAY );
			if ( $title ) {
				$href = $title->getFullUrl();
			}
			break;
		case 'help':
			$title = Title::newFromText( $pagetitle, NS_HELP );
			if ( $title ) {
				$href = $title->getFullUrl();
			}
			break;
		case 'external':
			$href = $pagetitle;
			break;
		default:
			$title = Title::newFromText( $pagetitle, NS_MAIN );
			if ( $title ) {
				$href = $title->getFullUrl();
			}
		}

		$thumbUrl = '';
		$error = '';

		$width = $height = 0;
		if ( $img->exists() ) {
			$width  = $img->getWidth();
			$height = $img->getHeight();
		}
		if ( 0 == $width || 0 == $height ) {
			$width = $height = 180;
		}
		if ( $boxwidth == 0 ) {
			$boxwidth = 180;
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
			$thumb = $img->transform(
				[ "width" => $boxwidth, "height" => $boxheight ]
			);
			if ( $thumb ) {
				$thumbUrl = $thumb->getUrl();
				$boxwidth = $thumb->getWidth();
				$boxheight = $thumb->getHeight();
			} else {
				$error = $img->getLastError();
			}
		}
		$oboxwidth = $boxwidth + 2;

		$textalign = $wgContLang->isRTL() ? ' style="text-align:right"' : '';

		$html = "<div class='thumb t{$align}'>"
			  . "<div class='thumbinner' style='width:{$oboxwidth}px;'>";
		if ( $thumbUrl == '' ) {
			// Couldn't generate thumbnail? Scale the image client-side.
			$thumbUrl = $img->getViewURL();
			if ( $boxheight == -1 ) {
				// Approximate...
				$boxheight = intval( $height * $boxwidth / $width );
			}
		}

		if ( $error ) {
			$html .= htmlspecialchars( $error );
		} elseif ( !$img->exists() ) {
			$html .= "Image does not exist";
		} elseif ( $href === "" ) {
			$html .= "Title error";
		} else {
			$html .= "<a href='$href' class='internal' title='$alt'>"
				  . "<img src='$thumbUrl' alt='$alt' "
				  . "width='$boxwidth' height='$boxheight' "
				  . "longdesc='$href' class='thumbimage' /></a>";
		}
		$html .= "  <div class='thumbcaption' $textalign>$label</div></div></div>";
		return str_replace( "\n", ' ', $html );
	}
}
