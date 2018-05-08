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
use ResourceLoader;
use OutputPage;
use Title;

/**
 * API for reading/writing Curation tags
 **/
class CurationTag {
	private static $tagList = "Curationtags-definition.xml";
	private static $tagListPage = "MediaWiki:Curationtags-definition.xml";

	private static $mayEdit;

	public static function onMakeGlobalVariablesScript(
		array &$vars, OutputPage $outputPage
	) {
		global $wgScriptPath;

		$helpLink = Title::newFromText( "CurationTags", NS_HELP )->getFullURL();

		// Add CSS
		$outputPage->addModules( [ "wpi.CurationTags" ] );
		// Add javascript
		$vars["CurationTags.extensionPath"] = $wgScriptPath . "/extensions/WikiPathways/";
		$vars["CurationTags.mayEdit"] = self::$mayEdit;
		$vars["CurationTags.helpLink"] = $helpLink;
	}

	public static function displayCurationTags( $input, $argv, $parser ) {
		if ( !$parser->getRevisionId() ) {
			$parser->mTitle->getLatestRevId();
		}

		$parser->getOutput()->addModules( [ "wpi.CurationTags" ] );
		$pageId = $parser->mTitle->getArticleID();
		$elementId = 'curationTagDiv';
		$parser->getOutput()->addHeadItem(
			ResourceLoader::makeInlineScript(
				"CurationTags.insertDiv('$elementId', '$pageId');"
			)
		);

		return "<div id='$elementId'></div>";
	}

	/**
	 * Processes events after a curation tag has changed
	 */
	public static function curationTagChanged( $tag ) {
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
	public static $tagPrefix = "Curation:";
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
	 *
	 * @param string $tagname to check
	 * @return bool
	 */
	public static function useRevision( $tagname ) {
		return self::getTagAttr( $tagname, "useRevision" ) !== null;
	}

	/**
	 * @param string $tagname to check
	 * @return string
	 */
	public static function newEditHighlight( $tagname ) {
		return self::getTagAttr( $tagname, "newEditHighlight" );
	}

	/**
	 * @param string $tagname to check
	 * @return string
	 */
	public static function highlightAction( $tagname ) {
		return self::getTagAttr( $tagname, "highlightAction" );
	}

	/**
	 * @param string $tagname to check
	 * @return string
	 */
	public static function bureaucratOnly( $tagname ) {
		return self::getTagAttr( $tagname, 'bureaucrat' );
	}

	/**
	 * @return string
	 */
	public static function defaultTag() {
		$list = self::getTagDefinition()->xpath( 'Tag[@default]' );
		if ( count( $list ) === 0 ) {
			throw new MWException( "curationtags-no-tags" );
		}
		if ( count( $list ) > 1 ) {
			throw new MWException( "curationtags-multiple-tags" );
		}
		return (string)$list[0]['name'];
	}

	/**
	 * Return a list of top tags
	 * @return array
	 */
	public static function topTags() {
		$list = self::topTagsWithLabels();
		return array_values( $list );
	}

	/**
	 * Return a list of top tags indexed by label.
	 * @return array
	 */
	public static function topTagsWithLabels() {
		$list = self::getTagDefinition()->xpath( 'Tag[@topTag]' );
		if ( count( $list ) === 0 ) {
			throw new MWException( "No top tags specified!  Please set [[CurationTagsDefinition]] with at least one top tag." );
		}
		$top = [];
		foreach ( $list as $tag ) {
			$top[(string)$tag['displayName']] = (string)$tag['name'];
		}

		return $top;
	}

	/**
	 * Get the names of all available curation tags.
	 *
	 * @return array of names
	 */
	public static function getTagNames() {
		$xpath = 'Tag/@name';
		$def = self::getTagDefinition()->xpath( $xpath );
		$names = [];
		foreach ( $def as $e ) {
			$names[] = $e['name'];
		}
		return $names;
	}

	/**
	 * Returns a list of tags that the user can select.
	 *
	 * @return array
	 */
	public static function getUserVisibleTagNames() {
		global $wgUser;
		$groups = array_flip( $wgUser->getGroups() );
		$isBureaucrat = isset( $groups['bureaucrat'] );
		$visible = self::topTagsWithLabels();
		/* Quick way to check if this is an already-visible top-tag */
		$top = array_flip( $visible );
		/* holds all the tags, not just the visible ones */
		$rest = [];

		foreach ( self::getTagNames() as $tag ) {
			/* SimpleXMLElements means lots of problems */
			$tag = (string)$tag;
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
					/* Also add it to the list of all tags */
					$rest[] = $tag;
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
	 * @param string $tagname The tag name
	 * @return An array with page ids
	 */
	public static function getPagesForTag( $tagname ) {
		return MetaTag::getPagesForTag( $tagname );
	}

	/**
	 * Get the SimpleXML representation of the tag definition
	 */
	public static function getTagDefinition() {
		if ( !self::$tagDefinition ) {
			$ref = wfMessage( self::$tagList )->plain();
			if ( !$ref ) {
				throw new Exception( "No content for [[".self::$tagListPage."]].  "
									. "It must be a valid XML document." );
			}
			try {
				libxml_use_internal_errors( true );
				self::$tagDefinition = new SimpleXMLElement( $ref );
			} catch ( Exception $e ) {
				$err = "Error parsing [[".self::$tagListPage."]].  It must be a valid XML document.\n";
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

	/**
	 * @param Title $title of pathway
	 * @return array of icons
	 */
	public static function getCurationImagesForTitle( Title $title ) {
		$tags = self::getCurationTags( $title->getArticleId() );

		$icon = [];
		foreach ( $tags as $tag ) {
			$img = self::getIcon( $tag->getName() );
			if ( $img ) {
				$icon[self::getDisplayName( $tag->getName() )] = [
					"img" => $img, "tag" => $tag->getName()
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
	 */
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
	 */
	public static function isCurationTag( $tagName ) {
		$expr = "/^" . self::$tagPrefix . "/";
		return preg_match( $expr, $tagName );
	}

	private static function errorNoCurationTag( $tagName ) {
		throw new Exception( "Tag '$tagName' is not a curation tag!" );
	}
}
