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

use AjaxResponse;
use Html;
use DOMDocument;

class PageEditor {
	/**
	 * Handle display of editor
	 *
	 * @param string $input of pageditor
	 * @param array $argv attributes
	 * @param Parser $parser object
	 * @return string for display
	 */
	public static function display( $input, $argv, $parser ) {
		global $wgUser;

		// Check user rights
		if ( !$wgUser->isLoggedIn() || wfReadOnly() ) {
			return "<!-- Page Editor here. -->";
		}

		if ( !isset( $argv['id'] ) ) {
			return wfMessage( "wp-pageditor-id-needed" );
		}
		if ( !isset( $argv['type'] ) ) {
			return wfMessage( "wp-pageditor-type-needed" );
		}
		$targetId = $argv['id'];
		$type = $argv['type'];

		$parser->getOutput()->addModules( [ "wpi.PageEditor" ] );
		$title = $parser->getTitle();
		$mayEdit = $title->userCan( 'edit' ) ? true : false;

		// Add javascript
		$pwId = $title->getText();
		$script = Html::element( 'div',
								 [ 'id' => 'pageEditor',
								   'data-target' => $targetId,
								   'data-type' => $type,
								   'data-input' => $input,
								   'data-pw' => $pwId,
								   'data-editable' => $mayEdit ] );

		return $script;
	}

	public static function save( $pwId, $type, $content ) {
		try {
			$pathway = new Pathway( $pwId );

			$doc = new DOMDocument();
			$gpml = $pathway->getGpml();
			$doc->loadXML( $gpml );
			switch ( $type ) {
				case "description":
					// Save description
					$description = false;
					$root = $doc->documentElement;
					foreach ( $root->childNodes as $n ) {
						if ( $n->nodeName == "Comment" &&
							$n->getAttribute( 'Source' ) == COMMENT_WP_DESCRIPTION ) {
							$description = $n;
							break;
						}
					}

					if ( !$description ) {
						$description = $doc->createElement( "Comment" );
						$description->setAttribute( "Source", COMMENT_WP_DESCRIPTION );
						$root->insertBefore( $description, $root->firstChild );
					}
					$description->nodeValue = $content;
					break;
				case "title":
					$doc->documentElement->setAttribute( "Name", $content );
					break;
			}
			$gpml = $doc->saveXML();
			$pathway->updatePathway( $gpml, "Modified " . $type );
		} catch ( Exception $e ) {
			$r = new AjaxResponse( $e );
			$r->setResponseCode( 500 );
			wfHttpError( 500, $e->getMessage() );
			return $r;
		}
		return new AjaxResponse( "" );
	}
}
