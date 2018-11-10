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

use Block;
use Content;
use ParserOptions;
use ParserOutput;
use RawMessage;
use Title;
use User;
use UserBlockedError;

class Hook {

	static $c = 0;
	public static function onRegistration() {
		global $wgAjaxExportList;

		// Register AJAX functions
		$wgAjaxExportList[] = "WikiPathways\\CurationTagsAjax::getTagNames";
		$wgAjaxExportList[] = "WikiPathways\\CurationTagsAjax::getTagData";
		$wgAjaxExportList[] = "WikiPathways\\CurationTagsAjax::saveTag";
		$wgAjaxExportList[] = "WikiPathways\\CurationTagsAjax::removeTag";
		$wgAjaxExportList[] = "WikiPathways\\CurationTagsAjax::getAvailableTags";
		$wgAjaxExportList[] = "WikiPathways\\CurationTagsAjax::getTagHistory";
		$wgAjaxExportList[] = "WikiPathways\\CurationTagsAjax::getTags";
		$wgAjaxExportList[] = "WikiPathways\\PageEditor::save";
		$wgAjaxExportList[] = "WikiPathways\\SearchPathwaysAjax::doSearch";
		$wgAjaxExportList[] = "WikiPathways\\SearchPathwaysAjax::getResults";
		// $wgAjaxExportList[] = "jsGetResults";
		// $wgAjaxExportList[] = "jsSearchPathways";
	}

	/**
	 * Set up hooks for parsing.
	 */
	public static function pathwayViewer() {
		global $wgParser;
		$wgParser->setHook( "wishlist", "WikiPathways\\TopWishes::renderWishlist" );
		$wgParser->setHook(
			"pathwayBibliography", "WikiPathways\\PathwayBibliography::output"
		);
		$wgParser->setHook( "Xref", "WikiPathways\\XrefPanel::renderXref" );
		$wgParser->setHook( "pathwayHistory", "WikiPathways\\PathwayHistory::history" );
		$wgParser->setHook(
			"batchDownload", "WikiPathways\\BatchDownloader::createDownloadLinks"
		);
		$wgParser->setHook( "recentChanges", "WikiPathways\\RecentChangesBox::create" );
		$wgParser->setHook( "pageEditor", "WikiPathways\\PageEditor::display" );

		$wgParser->setHook(
			"CurationTags", "WikiPathways\\CurationTag::displayCurationTags"
		);

		$wgParser->setFunctionHook(
			"PathwayViewer", "WikiPathways\\PathwayViewer::enable"
		);
		$wgParser->setFunctionHook(
			"searchPathwaysBox", "WikiPathways\\SearchPathways::renderSearchPathwaysBox"
		);
		$wgParser->setFunctionHook(
			"pwImage", "WikiPathways\\PathwayThumb::renderPathwayImage"
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
		$wgParser->setFunctionHook(
			"imgLink", "WikiPathways\\ImageLink::renderImageLink"
		);
	}

	/**
	 * Set up all the magic words
	 *
	 * @param array &$magicWords to modify
	 * @param string $langCode that we're modifying
	 */
	public static function pathwayMagic( &$magicWords, $langCode ) {
		$magicWords['PathwayViewer'] = [ 0, 'PathwayViewer' ];
		$magicWords['pwImage'] = [ 0, 'pwImage' ];
		$magicWords['pathwayOfTheDay'] = [ 0, 'pathwayOfTheDay' ];
		$magicWords['pathwayInfo'] = [ 0, 'pathwayInfo' ];
		$magicWords['siteStats'] = [ 0, 'siteStats' ];
		$magicWords['Statistics'] = [ 0, 'Statistics' ];
		$magicWords['searchPathwaysBox'] = [ 0, 'searchPathwaysBox' ];
		$magicWords['imgLink'] = [ 0, 'imgLink' ];
	}

	/* http://developers.pathvisio.org/ticket/1559 */
	public static function stopDisplay( $output, $sk ) {
		$title = $output->getPageTitle();
		if ( 'mediawiki:questycaptcha-qna' === strtolower( $title )
			|| 'mediawiki:questycaptcha-q&a' === strtolower( $title )
		) {
			if ( !Title::newFromText( "MediaWiki:Questycaptcha-qna" )->userCan( "edit" ) ) {
				$output->clearHTML();
				throw new UserBlockedError( new Block(
					$output->getUser()->getName(), 1, 1, 'secrets', 'indefinite'
				) );

				return false;
			}
		}
	}

	public static function onPageDisplay() {
		global $wgCaptchaQuestions;
		self::onQuestyCaptchaCreateQnA( $wgCaptchaQuestions );
	}

	/* http://www.pathvisio.org/ticket/1539 */
	public static function externalLink( &$url, &$text, &$link, &$attribs = null ) {
		global $wgExternalLinkTarget, $wgNoFollowLinks, $wgNoFollowNsExceptions;
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
	 * Special user permissions once a pathway is deleted.
	 * TODO: Disable this hook for running script to transfer to stable ids
	 *
	 * @param Title $title to check
	 * @param User $user for permissions
	 * @param
	 * @return bool
	 */
	public static function checkDeleted( Title $title, $user, $action, &$result ) {
		if ( $action == 'edit' && $title->getNamespace() == NS_PATHWAY ) {
			$pathway = Pathway::newFromTitle( $title );
			if ( $pathway->isDeleted() ) {
				if ( MwUtils::isOnlyAuthor( $user, $title->getArticleId() ) ) {
					// Users that are sole author of a pathway can always revert deletion
					$result = true;
					return false;
				} else {
					// Only users with 'delete' permission can revert deletion
					// So disable edit for all other users
					$result = $title->getUserPermissionsErrors( 'delete', $user ) == [];
					return false;
				}
			}
		}
		$result = null;
		return true;
	}

	/*
	 * Special delete permissions for pathways if user is sole author
	 */
	function checkSoleAuthor( $title, $user, $action, &$result ) {
		// Users are allowed to delete their own pathway
		if ( $action == 'delete' && $title->getNamespace() == NS_PATHWAY ) {
			if ( MWUtils::isOnlyAuthor( $user, $title->getArticleId() ) && $title->userCan( 'edit' ) ) {
				$result = true;
				return false;
			}
		}
		$result = null;
		return true;
	}

	/**
	 */
	public static function onContentGetParserOutput(
		Content $content,
		Title $title,
		$revId,
		ParserOptions $options,
		$generateHtml,
		ParserOutput &$po
	) {
		return !Pathway::$InternalUpdate;
	}

	/**
	 * 
	 */
	public static function abortOnBadDomain(
		User $user,
		$message
	) {
		return true;
	}

	public static function onSpecialPageBeforeExecute( $specialPage, $subPage = null ) {
		if ( $specialPage->getName() === "CreateAccount" && $subPage === null ) {
			global $wgCaptchaQuestions;

			self::onQuestyCaptchaCreateQnA( $wgCaptchaQuestions );
		}

	}

	public static function onQuestyCaptchaCreateQnA( &$captchaQuestions ) {
		$all = wfMessage( 'wp-questy-qna' )->text();
		$qna = preg_split( "/\n=== Q&A ===\n/", $all, 2 );
		$count = 0;

		$captchaQuestions = [];
		if ( !isset( $qna[1] ) ) {
			throw new ErrorPageError( "questycaptcha-setup", 'wp-questy-no-qna' );
		}
		foreach ( preg_split( "/\n/", $qna[1] ) as $l ) {
			if ( strtolower( substr( $l, 0, 2 ) ) == "q:" ) {
				$msg = new RawMessage( trim( substr( $l, 2 ) ) );
				$captchaQuestions[$count]["question"] = $msg->toString();
			}

			if ( strtolower( substr( $l, 0, 2 ) ) == "a:" ) {
				$captchaQuestions[$count]["answer"] = trim( substr( $l, 2 ) );
			}
			if ( isset( $captchaQuestions[$count]["answer"] ) &&
				 isset( $captchaQuestions[$count]["question"] ) ) {
				// global $wgParser;
				// $captchaQuestions[$count]["question"]
				// 	= $wgParser->recursiveTagParse( $captchaQuestions[$count]["question"] );
				$count++;
			}
		}
		if ( $count < 1 ) {
			throw new ErrorPageError( "questycaptcha-setup", 'wp-questy-no-qna-list' );
		}
		foreach ( $captchaQuestions as $index => $qna ) {
			$captchaQuestions[$qna["question"]] = $qna["answer"];
			unset( $captchaQuestions[$index] );
		}
	}
}
