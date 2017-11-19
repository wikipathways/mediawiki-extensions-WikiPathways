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

use SimpleXMLElement;
use Exception;
use MWException;
use OutputPage;
use RequestContext;
use Title;

/**
 * API for reading/writing Curation tags
 **/
class CurationTag {
	private static $TAG_LIST = "Curationtags-definition.xml";
	private static $TAG_LIST_PAGE = "MediaWiki:Curationtags-definition.xml";

	private static $mayEdit;

	public static function onMakeGlobalVariablesScript(
		array &$vars, OutputPage $outputPage
	) {
		global $wgScriptPath;

		$helpLink = Title::newFromText( "CurationTags", NS_HELP )->getFullURL();

		// Add CSS
		$outputPage->addModuleScripts( "wpi.CurationTags", "top" );
		// Add javascript
		$vars["CurationTags.extensionPath"] = $wgScriptPath . "/extensions/WikiPathways/";
		$vars["CurationTags.mayEdit"] = self::$mayEdit;
		$vars["CurationTags.helpLink"] = $helpLink;
	}

	public static function displayCurationTags( $input, $argv, $parser ) {
		$title = $parser->getTitle();
		$mayEdit = $title->userCan( 'edit' ) ? true : false;
		if ( !$parser->getRevisionId() ) {
			$parser->mTitle->getLatestRevId();
		}

		$pageId = $parser->mTitle->getArticleID();
		$elementId = 'curationTagDiv';
		return "<div id='$elementId'></div>"
			. "<script type='text/javascript'>"
			. "CurationTags.insertDiv('$elementId', '$pageId');</script>\n";
	}

	/**
	 * Processes events after a curation tag has changed
	 */
	public static function curationTagChanged( $tag ) {
		global $wgEnotifUseJobQ;

		$hist = MetaTag::getHistoryForPage( $tag->getPageId(), wfTimestamp( TS_MW ) );

		if ( count( $hist ) > 0 ) {
			$taghist = $hist[0];
			$enotif = new TagChangeNotification( $taghist );
			$enotif->notifyOnTagChange();
		}
	}

	/**
	 * Tags with this prefix will be recognized
	 * as curation tags. Other tags will be ignored
	 * by this API.
	 */
	public static $TAG_PREFIX = "Curation:";
	private static $tagDefinition;

	private static function getTagAttr( $tag, $attr ) {
		$r = self::getTagDefinition()->xpath( 'Tag[@name="' . $tag . '"]/@' . $attr );
		$v = $r ? (string)$r[0][$attr] : null;
		return $v !== null && $v !== "" ? $v : null;
	}

	/**
	 * Get the display name for the given tag name
	 */
	public static function getDisplayName( $tagname ) {
		return self::getTagAttr( $tagname, "displayName" );
	}

	/**
	 * Get the drop-down name for the given tag name
	 */
	public static function getDropDown( $tagname ) {
		return self::getTagAttr( $tagname, "dropDown" );
	}

	/**
	 * Get the icon for the tag.
	 */
	public static function getIcon( $tagname ) {
		$a = self::getTagAttr( $tagname, "icon" );
		return self::getTagAttr( $tagname, "icon" );
	}

	/**
	 * Get the description for the given tag name
	 */
	public static function getDescription( $tagname ) {
		return self::getTagAttr( $tagname, "description" );
	}

	/**
	 * Returns true if you the revision should be used.
	 */
	public static function useRevision( $tagname ) {
		return self::getTagAttr( $tagname, "useRevision" ) !== null;
	}

	public static function newEditHighlight( $tagname ) {
		return self::getTagAttr( $tagname, "newEditHighlight" );
	}

	public static function highlightAction( $tagname ) {
		return self::getTagAttr( $tagname, "highlightAction" );
	}

	public static function bureaucratOnly( $tagname ) {
		return self::getTagAttr( $tagname, 'bureaucrat' );
	}

	public static function defaultTag() {
		$r = self::getTagDefinition()->xpath( 'Tag[@default]' );
		if ( count( $r ) === 0 ) {
			throw new MWException( "curationtags-no-tags" );
		}
		if ( count( $r ) > 1 ) {
			throw new MWException( "curationtags-multiple-tags" );
		}
		return (string)$r[0]['name'];
	}

	/**
	 * Return a list of top tags
	 */
	public static function topTags() {
		$r = self::topTagsWithLabels();
		return array_values( $r );
	}

	/**
	 * Return a list of top tags indexed by label.
	 */
	public static function topTagsWithLabels() {
		$r = self::getTagDefinition()->xpath( 'Tag[@topTag]' );
		if ( count( $r ) === 0 ) {
			throw new MWException( "No top tags specified!  Please set [[CurationTagsDefinition]] with at least one top tag." );
		}
		$top = [];
		foreach ( $r as $tag ) {
			$top[(string)$tag['displayName']] = (string)$tag['name'];
		}

		return $top;
	}

	/**
	 * Get the names of all available curation tags.
	 */
	public static function getTagNames() {
		$xpath = 'Tag/@name';
		$dn = self::getTagDefinition()->xpath( $xpath );
		$names = [];
		foreach ( $dn as $e ) { $names[] = $e['name'];
		}
		return $names;
	}

	/**
	 * Returns a list of tags that the user can select.
	 */
	public static function getUserVisibleTagNames() {
		global $wgUser;
		$groups = array_flip( $wgUser->getGroups() );
		$isBureaucrat = isset( $groups['bureaucrat'] );
		$visible = self::topTagsWithLabels();
		$top = array_flip( $visible ); /* Quick way to check if this is an already-visible top-tag */
		$rest = []; // holds all the tags, not just the visible ones

		foreach ( self::getTagNames() as $tag ) {
			$tag = (string)$tag; /* SimpleXMLElements means lots of problems */
			if ( self::bureaucratOnly( $tag ) ) {
				if ( isset( $top[$tag] ) ) {
					throw new MWException( "Bureaucrat-only tags cannot be top tags! Choose one or the other for '$tag'" );
				}
				if ( $isBureaucrat ) {
					$label = self::getDropDown( $tag );
					if ( empty( $label ) ) {
						$label = self::getDisplayName( $tag );
					}
					$visible[$label] = $tag;
					$rest[] = $tag; /* Also add it to the list of all tags */
				}
			} else {
				$rest[] = $tag;
			}
		}
		$visible[ wfMessage( 'browsepathways-all-tags' )->text() ] = $rest;
		return $visible;
	}

	/**
	 * Get all pages that have the given curation tag.
	 *
	 * @param  $name The tag name
	 * @return An array with page ids
	 */
	public static function getPagesForTag( $tagname ) {
		return MetaTag::getPagesForTag( $tagname );
	}

	/**
	 * Get the SimpleXML representation of the tag definition
	 **/
	public static function getTagDefinition() {
		if ( !self::$tagDefinition ) {
			$ref = wfMessage( self::$TAG_LIST )->plain();
			if ( !$ref ) {
				throw new Exception( "No content for [[".self::$TAG_LIST_PAGE."]].  "
									. "It must be a valid XML document." );
			}
			try {
				libxml_use_internal_errors( true );
				self::$tagDefinition = new SimpleXMLElement( $ref );
			} catch ( Exception $e ) {
				$err = "Error parsing [[".self::$TAG_LIST_PAGE."]].  It must be a valid XML document.\n";
				$line = explode( "\n", trim( $ref ) );
				foreach ( libxml_get_errors() as $error ) {
					if ( strstr( $error->message, "Start tag expected" ) ) {
						$err .= "\n    " . $error->message . "\nPage content:\n    " .
						implode( "\n    ", $line );
					} else {
						$err .= "\n    " . $error->message . "\nStart of page:\n  " .
						substr( trim( $line[0] ), 0, 100 );
					}
				}
				throw new MWException( $err );
			}
		}
		return self::$tagDefinition;
	}

	/**
	 * Create or update the tag, based on the provided tag information
	 */
	public static function saveTag( $pageId, $name, $text, $revision = false ) {
		if ( !self::isCurationTag( $name ) ) {
			self::errorNoCurationTag( $name );
		}

		$tag = new MetaTag( $name, $pageId );
		$tag->setText( $text );
		if ( $revision && $revision != 'false' ) {
			$tag->setPageRevision( $revision );
		}
		$tag->save();
		self::curationTagChanged( $tag );
	}

	/**
	 * Remove the given curation tag for the given page.
	 */
	public static function removeTag( $tagname, $pageId ) {
		if ( !self::isCurationTag( $tagname ) ) {
			self::errorNoCurationTag( $tagname );
		}

		$tag = new MetaTag( $tagname, $pageId );
		$tag->remove();
		self::curationTagChanged( $tag );
	}

	public static function getCurationTags( $pageId ) {
		$tags = MetaTag::getTagsForPage( $pageId );
		$curTags = [];
		foreach ( $tags as $t ) {
			if ( self::isCurationTag( $t->getName() ) ) {
				$curTags[$t->getName()] = $t;
			}
		}
		return $curTags;
	}

	public static function getCurationImagesForTitle( $title ) {
		$pageId = $title->getArticleId();
		$tags = self::getCurationTags( $pageId );

		$icon = [];
		foreach ( $tags as $tag ) {
			if ( $i = self::getIcon( $tag->getName() ) ) {
				$icon[self::getDisplayName( $tag->getName() )] =
				[
				"img" => $i,
				"tag" => $tag->getName()
				];
			}
		}
		return $icon;
	}

	public static function getCurationTagsByName( $tagname ) {
		if ( !self::isCurationTag( $tagname ) ) {
			self::errorNoCurationTag( $tagname );
		}
		return MetaTag::getTags( $tagname );
	}

	/**
	 * Get tag history for the given page
	 */
	public static function getHistory( $pageId, $fromTime = 0 ) {
		$allhist = MetaTag::getHistoryForPage( $pageId, $fromTime );
		$hist = [];
		foreach ( $allhist as $h ) {
			if ( self::isCurationTag( $h->getTagName() ) ) {
				$hist[] = $h;
			}
		}
		return $hist;
	}

	/**
	 * Get the curation tag history for all pages
	 **/
	public static function getAllHistory( $fromTime = 0 ) {
		$allhist = MetaTag::getAllHistory( '', $fromTime );
		$hist = [];
		foreach ( $allhist as $h ) {
			if ( self::isCurationTag( $h->getTagName() ) ) {
				$hist[] = $h;
			}
		}
		return $hist;
	}

	/**
	 * Checks if the tagname is a curation tag
	 **/
	public static function isCurationTag( $tagName ) {
		$expr = "/^" . self::$TAG_PREFIX . "/";
		return preg_match( $expr, $tagName );
	}

	private static function errorNoCurationTag( $tagName ) {
		throw new Exception( "Tag '$tagName' is not a curation tag!" );
	}
}
