<?php
/**
 * Implementation of Pathway data.
 *
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
 * @file
 * @ingroup Content
 *
 * @author Mark A. Hershberger
 */
namespace WikiPathways;

use AbstractContent;
use Title;
use ParserOptions;

/**
 * Base interface for pathway objects.
 *
 * @ingroup Content
 */
class PathwayContent extends AbstractContent {
	protected $title;
	protected $revId;

	public function __construct( $text ) {
		parent::__construct( CONTENT_MODEL_PATHWAY );
		$this->mText = $text;
	}

	public function getModel() {
		return CONTENT_MODEL_PATHWAY;
	}

	/**
	 * @since 1.21
	 *
	 * @return string A string representing the content in a way useful for
	 *   building a full text search index. If no useful representation exists,
	 *   this method returns an empty string.
	 *
	 * @todo: test that this actually works
	 * @todo: make sure this also works with LuceneSearch / WikiSearch
	 */
	public function getTextForSearchIndex() { return ""; }

	/**
	 * @since 1.21
	 *
	 * @return string|false The wikitext to include when another page includes this
	 * content, or false if the content is not includable in a wikitext page.
	 *
	 * @todo allow native handling, bypassing wikitext representation, like
	 *    for includable special pages.
	 * @todo allow transclusion into other content models than Wikitext!
	 * @todo used in WikiPage and MessageCache to get message text. Not so
	 *    nice. What should we use instead?!
	 */
	public function getWikitextForTransclusion() { return false; }

	/**
	 * Returns a textual representation of the content suitable for use in edit
	 * summaries and log messages.
	 *
	 * @since 1.21
	 *
	 * @param int $maxLength Maximum length of the summary text
	 * @return string The summary text
	 */
	public function getTextForSummary( $maxLength = 250 ) { return "basic log message"; }

	public function serialize( $format = null ) {
		return $this->mText;
	}

	/**
	 * Returns native representation of the data. Interpretation depends on
	 * the data model used, as given by getDataModel().
	 *
	 * @since 1.21
	 *
	 * @return mixed The native representation of the content. Could be a
	 *    string, a nested array structure, an object, a binary blob...
	 *    anything, really.
	 *
	 * @note Caller must be aware of content model!
	 */
	public function getNativeData( ) {
		return $this->mText;
	}

	/**
	 * Returns the content's nominal size in bogo-bytes.
	 *
	 * @return int
	 */
	public function getSize() { return 0; }

	/**
	 * Return a copy of this Content object. The following must be true for the
	 * object returned:
	 *
	 * if $copy = $original->copy()
	 *
	 * - get_class($original) === get_class($copy)
	 * - $original->getModel() === $copy->getModel()
	 * - $original->equals( $copy )
	 *
	 * If and only if the Content object is immutable, the copy() method can and
	 * should return $this. That is, $copy === $original may be true, but only
	 * for immutable content objects.
	 *
	 * @since 1.21
	 *
	 * @return Content. A copy of this object
	 */
	public function copy() { return clone( $this ); }

	/**
	 * Returns true if this content is countable as a "real" wiki page, provided
	 * that it's also in a countable location (e.g. a current revision in the
	 * main namespace).
	 *
	 * @since 1.21
	 *
	 * @param bool $hasLinks If it is known whether this content contains
	 *    links, provide this information here, to avoid redundant parsing to
	 *    find out.
	 * @return boolean
	 */
	public function isCountable( $hasLinks = null ) { return true; }

	/* No redirects on pathways */
	public function getRedirectTarget() { return null; }

	/**
	 * Parse the Content object and generate a ParserOutput from the result.
	 * $result->getText() can be used to obtain the generated HTML. If no HTML
	 * is needed, $generateHtml can be set to false; in that case,
	 * $result->getText() may return null.
	 *
	 * @param $title Title The page title to use as a context for rendering
	 * @param $revId null|int The revision being rendered (optional)
	 * @param $options null|ParserOptions Any parser options
	 * @param $generateHtml Boolean Whether to generate HTML (default: true). If false,
	 *        the result of calling getText() on the ParserOutput object returned by
	 *        this method is undefined.
	 *
	 * @since 1.21
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput(
		Title $title, $revId = null, ParserOptions $options = null, $generateHtml = true
	) {
		wfProfileIn( __METHOD__ );
		global $wgParser, $wgTextModelsToParse;
		if ( !$options ) {
			// NOTE: use canonical options per default to produce
			// cacheable output
			$options = $this->getContentHandler()
					 ->makeParserOptions( 'canonical' );
		}

		$id = Pathway::parseIdentifier($title);
		$pathway = new Pathway( $id );
		if($revId) {
			$pathway->setActiveRevision($revId);
		}

		// title editor
		$showTitle = $pathway->getName();
		$out = "";

		// Start permission warning;
		global $wgLang;
		$url = SITE_URL;
		$msg = wfMessage( 'private_warning' )->text();
		$pp = $pathway->getPermissionManager()->getPermissions();
		if ( $pp ) {
			$expdate = $pp->getExpires();
			$expdate = $wgLang->date($expdate, true);
			$msg = str_replace('$DATE', $expdate, $msg);
			$out .= "<div class='private_warn'>$msg</div>";
		}

		$out .= "{{Template:PathwayPage:Top}}\n" .
			 "== Curation Tags ==\n" .
			 "<CurationTags></CurationTags>\n";

		// descriptionText
		$out .= "== Description ==\n";
		$out .= $pathway->getPathwayData()->getWikiDescription();

		// ontologytags
		global $wpiEnableOtag;
		if($wpiEnableOtag) {
			$out .= "\n== Ontology Tags ==\n" .
				 "<OntologyTags></OntologyTags>\n";
		}

		global $wgUser;
		$out .= "== Bibliography ==\n" .
			 "<pathwayBibliography></pathwayBibliography>\n";
		## FIXME display {{Template:Help:LiteratureReferences}} if user
		## is logged in here -- should use JS

		$out .= "{{Template:PathwayPage:Bottom}}\n";
		$po = $wgParser->parse( $out, $title, $options, true, true,
								$revId );
		$po->setDisplayTitle( $showTitle );
		## FIXME use js to allow editing that was done using pageEditor on
		## #pageTitle

		wfProfileOut( __METHOD__ );
		return $po;
	}
}
