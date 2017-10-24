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
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways;

class Hook {
	// Probably better to put this in parser init hook
	public static function pathwayViewer() {
		global $wgParser;
		$wgParser->setHook( "pathwayBibliography", "WikiPathways\\PathwayBibliography::output" );
		$wgParser->setHook( "Xref", "WikiPathways\\XrefPanel::renderXref" );
		$wgParser->setHook( "pathwayHistory", "WikiPathways\\PathwayHistory::history" );
		$wgParser->setHook(
			"batchDownload", "WikiPathways\\BatchDownloader::createDownloadLinks"
		);
		$wgParser->setHook( "recentChanges", "WikiPathways\\RecentChangesBox::create" );

		$wgParser->setFunctionHook(
			"PathwayViewer", "WikiPathways\\PathwayViewer::enable"
		);
		$wgParser->setFunctionHook(
			"pwImage", "WikiPathways\\PathwayThumb::renderPathwayImage"
		);
		$wgParser->setFunctionHook(
			"editApplet", "WikiPathways\\EditApplet::createApplet"
		);
		$wgParser->setFunctionHook(
			"pathwayOfTheDay", "WikiPathways\\PathwayOfTheDay::get"
		);
		$wgParser->setFunctionHook(
			'siteStats', 'WikiPathways\\StatisticsCache::getSiteStats'
		);
		$wgParser->setFunctionHook(
			'pathwayInfo', 'WikiPathways\\PathwayInfo::getPathwayInfoText'
		);
		$wgParser->setFunctionHook(
			"Statistics", "WikiPathways\\Statistics::loadStatistics"
		);

		XrefPanel::addXrefPanelScripts();

		Pathway::registerFileType( FILETYPE_PDF );
		Pathway::registerFileType( FILETYPE_PWF );
		Pathway::registerFileType( FILETYPE_TXT );
		Pathway::registerFileType( FILETYPE_BIOPAX );

        RecentChangesBox::init;
	}

	public static function pathwayMagic( &$magicWords, $langCode ) {
		$magicWords['PathwayViewer'] = [ 0, 'PathwayViewer' ];
		$magicWords['pwImage'] = [ 0, 'pwImage' ];
		$magicWords['editApplet'] = [ 0, 'editApplet' ];
		$magicWords['pathwayOfTheDay'] = [ 0, 'pathwayOfTheDay' ];
		$magicWords['pathwayInfo'] = [ 0, 'pathwayInfo' ];
		$magicWords['siteStats'] = [ 0, 'siteStats' ];
		$magicWords['Statistics'] = [ 0, 'Statistics' ];
	}

	/* http://developers.pathvisio.org/ticket/1559 */
	public static function stopDisplay( $output, $sk ) {
		global $wgUser;

		$title = $output->getPageTitle();
		if ( 'mediawiki:questycaptcha-qna' === strtolower( $title )
			|| 'mediawiki:questycaptcha-q&a' === strtolower( $title )
		) {
			if ( !$title->userCan( "edit" ) ) {
				$output->clearHTML();

				$wgUser->mBlock = new Block(
					'127.0.0.1', 'WikiSysop', 'WikiSysop', 'none', 'indefinite'
				);
				$wgUser->mBlockedby = 0;

				$output->blockedPage();
				return false;
			}
		}
	}

	/* http://www.pathvisio.org/ticket/1539 */
	public static function externalLink( &$url, &$text, &$link, &$attribs = null ) {
		global $wgExternalLinkTarget, $wgNoFollowLinks, $wgNoFollowNsExceptions;
		wfProfileIn( __METHOD__ );
		wfDebug( __METHOD__.": Looking at the link: $url\n" );

		$linkTarget = "_blank";
		if ( isset( $wgExternalLinkTarget ) && $wgExternalLinkTarget != "" ) {
			$linkTarget = $wgExternalLinkTarget;
		}

		/**AP20070417 -- moved from Linker.php by mah 20130327
		* Added support for opening external links as new page
		* Usage: [http://www.genmapp.org|_new Link]
		*/
		if ( substr( $url, -5 ) == "|_new" ) {
			$url = substr( $url, 0, strlen( $url ) - 5 );
			$linkTarget = "new";
		} elseif ( substr( $url, -7 ) == "%7c_new" ) {
			$url = substr( $url, 0, strlen( $url ) - 7 );
			$linkTarget = "new";
		}

		// Hook changed to include attribs in 1.15
		if ( $attribs !== null ) {
			$attribs["target"] = $linkTarget;
			/* nothing else should be needed, so we can leave the rest */
			return;
		}

		/* ugh ... had to copy this bit from makeExternalLink */
		$l = new Linker;
		$style = $l->getExternalLinkAttributes( $url, $text, 'external ' );
		if ( $wgNoFollowLinks
			&& !( isset( $ns )
			&& in_array( $ns, $wgNoFollowNsExceptions ) )
		) {
			$style .= ' rel="nofollow"';
		}

		$link = '<a href="'.$url.'" target="'.$linkTarget.'"'.$style.'>'
		. $text.'</a>';
		wfProfileOut( __METHOD__ );

		return false;
	}

	public static function updateTags(
		&$article, &$user, $text, $summary, $minoredit, $watchthis, $sectionanchor,
		&$flags, $revision, &$status = null, $baseRevId = null
	) {
		$title = $article->getTitle();
		if ( $title->getNamespace() !== NS_PATHWAY ) {
			return;
		}

		if ( !$title->userCan( "autocurate" ) ) {
			wfDebug( __METHOD__ . ": User can't autocurate\n" );
			return;
		}

		wfDebug( __METHOD__ . ": Autocurating tags for {$title->getText()}\n" );
		$db = wfGetDB( DB_MASTER );
		$tags = MetaTag::getTagsForPage( $title->getArticleID() );
		foreach ( $tags as $tag ) {
			$oldRev = $tag->getPageRevision();
			if ( $oldRev ) {
				wfDebug(
					__METHOD__
					. ": Setting {$tag->getName()} to {$revision->getId()}\n"
				);
				$tag->setPageRevision( $revision->getId() );
				$tag->save();
			} else {
				wfDebug(
					__METHOD__
					. ": No revision information for {$tag->getName()}\n"
				);
			}
		}
	}

	/**
	 * Handles javascript dependencies for WikiPathways extensions
	 */
	public static function addJavascript( &$out, $parseroutput ) {
		global $wgJsMimeType, $wpiJavascriptSnippets, $wpiJavascriptSources,
		 $jsJQuery, $wgRequest;

		// Array containing javascript source files to add
		if ( !isset( $wpiJavascriptSources ) ) {
			$wpiJavascriptSources = XrefPanel::getJsDependencies();
		}
		$wpiJavascriptSources = array_unique( $wpiJavascriptSources );

		// Array containing javascript snippets to add
		if ( !isset( $wpiJavascriptSnippets ) ) {
			$wpiJavascriptSnippets = XrefPanel::getJsSnippets();
		}
		$wpiJavascriptSnippets = array_unique( $wpiJavascriptSnippets );

		foreach ( $wpiJavascriptSnippets as $snippet ) {
			$out->addScript(
				"<script type=\"{$wgJsMimeType}\">"
				. $snippet . "</script>\n"
			);
		}
		foreach ( $wpiJavascriptSources as $src ) {
			$out->addScript(
				'<script src="' . $src . '" type="' . $wgJsMimeType
				. '"></script>'
			);
		}

		// Add firebug lite console if requested in GET
		$bug = "http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js";
		if ( $wgRequest->getval( 'firebug' ) ) {
			$out->addScript(
				'<script src="' . $bug . '" type="' .
				$wgJsMimeType . '></script>'
			);
		}
		return true;
	}
}
