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

use AjaxResponse;
use DOMDocument;
use Parser;
use ParserOptions;
use User;

/**
 * Ajax API for reading/writing curation tags
 **/
class CurationTagsAjax {
	/**
	 * Get the tag names for the given page.
	 *
	 * @return an XML snipped containing a list of tag names of the form:
	 * <TagNames><Name>tag1</Name><Name>tag2</Name>...<Name>tagn</Name></TagNames>
	 */
	public static function getTagNames( $pageId ) {
		$tags = CurationTag::getCurationTags( $pageId );
		$doc = new DOMDocument();
		$root = $doc->createElement( "TagNames" );
		$doc->appendChild( $root );

		foreach ( $tags as $t ) {
			$e = $doc->createElement( "Name" );
			$e->appendChild( $doc->createTextNode( $t->getName() ) );
			$root->appendChild( $e );
		}

		$resp = new AjaxResponse( trim( $doc->saveXML() ) );
		$resp->setContentType( "text/xml" );
		return $resp;
	}

	/**
	 * Remove the given tag
	 *
	 * @return an XML snipped containing the tagname of the removed tag:
	 * <Name>tagname</Name>
	 */
	public static function removeTag( $name, $pageId ) {
		CurationTag::removeTag( $name, $pageId );

		$doc = new DOMDocument();
		$root = $doc->createElement( "Name" );
		$root->appendChild( $doc->createTextNode( $name ) );
		$doc->appendChild( $root );

		$resp = new AjaxResponse( trim( $doc->saveXML() ) );
		$resp->setContentType( "text/xml" );
		return $resp;
	}

	/**
	 * Create or update the tag, based on the provided tag information
	 *
	 * @return an XML snipped containing the tagname of the created tag:
	 * <Name>tagname</Name>
	 */
	public static function saveTag( $name, $pageId, $text, $revision = false ) {
		CurationTag::saveTag( $pageId, $name, $text, $revision );

		$doc = new DOMDocument();
		$root = $doc->createElement( "Name" );
		$root->appendChild( $doc->createTextNode( $name ) );
		$doc->appendChild( $root );

		$resp = new AjaxResponse( trim( $doc->saveXML() ) );
		$resp->setContentType( "text/xml" );
		return $resp;
	}

	/**
	 * Get the tag history for the given page.
	 *
	 * @param  $pageId The page id
	 * @param  $fromTime An optional cutoff, if provided, only
	 * history entries after this time will be returned.
	 * @return An xml encoded response containing the history:
	 * <History fromTime='timestamp'>
	 *         <HistoryRow tagName = 'tagname' ...(other history attributes)/>
	 *        ...
	 * </History>
	 */
	public static function getTagHistory( $pageId, $fromTime = '0' ) {
		global $wgLang, $wgUser;

		$hist = CurationTag::getHistory( $pageId, $fromTime );

		$doc = new DOMDocument();
		$root = $doc->createElement( "History" );
		$doc->appendChild( $root );

		foreach ( $hist as $h ) {
			$elm = $doc->createElement( "HistoryRow" );
			$elm->setAttribute( 'tag_name', $h->getTagName() );
			$elm->setAttribute( 'page_id', $h->getPageId() );
			$elm->setAttribute( 'action', $h->getAction() );
			$elm->setAttribute( 'user', $h->getUser() );
			$elm->setAttribute( 'time', $h->getTime() );

			$timeText = $wgLang->timeanddate( $h->getTime() );
			$elm->setAttribute( 'timeText', $timeText );

			$uid = $h->getUser();
			$nm = $uid;
			$u = User::newFromId( $uid );
			if ( $u ) {
				$nm = $u->getName();
			}
			$userText = $wgUser->getSkin()->userLink( $uid, $nm );
			$elm->setAttribute( 'userText', $userText );

			$root->appendChild( $elm );
		}

		$resp = new AjaxResponse( trim( $doc->saveXML() ) );
		$resp->setContentType( "text/xml" );
		return $resp;
	}

	/**
	 * Get all curation tags (and their contents) at once.
	 */
	public static function getTags( $pageId, $pageRev = 0 ) {
		$tags = CurationTag::getCurationTags( $pageId );
		$doc = new DOMDocument();
		$root = $doc->createElement( "Tags" );
		$doc->appendChild( $root );

		foreach ( $tags as $t ) {
			$elm = self::getTagXml( $doc, $t, $pageRev );
			$root->appendChild( $elm );
		}

		$resp = new AjaxResponse( trim( $doc->saveXML() ) );
		$resp->setContentType( "text/xml" );
		return $resp;
	}

	/**
	 * Get the data for this tag.
	 *
	 * @return An xml encoded response, in the form:
	 * <Tag name='tagname' ...(other tag attributes)>
	 *         <Html>the html code</html>
	 *         <Text>the tag text</text>
	 *     </Tag>
	 */
	public static function getTagData( $name, $pageId, $pageRev = 0 ) {
		$tag = new MetaTag( $name, $pageId );

		$doc = new DOMDocument();
		$elm = self::getTagXML( $doc, $tag, $pageRev );
		$doc->appendChild( $elm );

		$resp = new AjaxResponse( $doc->saveXML() );
		$resp->setContentType( "text/xml" );
		return $resp;
	}

	public static function getTagXML( $doc, $tag, $pageRev = 0 ) {
		// Create a template call and use the parser to
		// convert this to HTML
		$userAdd = User::newFromId( $tag->getUserAdd() );
		$userMod = User::newFromId( $tag->getUserMod() );

		$name = $tag->getName();
		$pageId = $tag->getPageId();

		$tmp = $name;
		$tmp .= "|tag_name={$tag->getName()}";
		$tmp .= "|tag_text={$tag->getText()}";
		$tmp .= "|user_add={$tag->getUserAdd()}";
		$tmp .= "|user_add_name={$userAdd->getName()}";
		$tmp .= "|user_mod_name={$userMod->getName()}";
		$tmp .= "|user_add_realname={$userAdd->getRealName()}";
		$tmp .= "|user_mod_realname={$userMod->getRealName()}";
		$tmp .= "|user_mod={$tag->getUserMod()}";
		$tmp .= "|time_add={$tag->getTimeAdd()}";
		$tmp .= "|time_mod={$tag->getTimeMod()}";
		$tmp .= "|page_revision={$pageRev}";

		if ( $tag->getPageRevision() ) {
			$tmp .= "|tag_revision={$tag->getPageRevision()}";
		}

		$tmp = "{{Template:" . $tmp . "}}";

		$parser = new Parser();
		$title = Title::newFromID( $pageId );
		$out = $parser->parse( $tmp, $title, new ParserOptions() );
		$html = $out->getText();

		$elm = $doc->createElement( "Tag" );
		$elm->setAttribute( 'name', $tag->getName() );
		$elm->setAttribute( 'page_id', $tag->getPageId() );
		$elm->setAttribute( 'user_add', $tag->getUserAdd() );
		$elm->setAttribute( 'time_add', $tag->getTimeAdd() );
		$elm->setAttribute( 'user_mod', $tag->getUserMod() );
		$elm->setAttribute( 'time_mod', $tag->getTimeMod() );
		if ( $tag->getPageRevision() ) {
			$elm->setAttribute( 'revision', $tag->getPageRevision() );
		}
		$elm_text = $doc->createElement( "Text" );
		$elm_text->appendChild( $doc->createTextNode( $tag->getText() ) );
		$elm->appendChild( $elm_text );

		$elm_html = $doc->createElement( "Html" );
		$elm_html->appendChild( $doc->createTextNode( $html ) );
		$elm->appendChild( $elm_html );

		return $elm;
	}

	/**
	 * Get the available curation tags.
	 *
	 * @return An xml document containing the list of tags on the
	 * CurationTagsDefinition wiki page
	 */
	public static function getAvailableTags() {
		$td = CurationTag::getTagDefinition();
		$resp = new AjaxResponse( $td->asXML() );
		$resp->setContentType( "text/xml" );
		return $resp;
	}
}
