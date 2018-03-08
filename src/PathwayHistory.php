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
 */
namespace WikiPathways;

use MWException;
use Article;
use HistoryPager;
use Revision;
use User;
use Linker;
use Html;

class PathwayHistory extends HistoryPager {

	public static function history( $input, $argv, $parser ) {
		$parser->disableCache();
		try {
			$pathway = Pathway::newFromTitle( $parser->mTitle );
			return self::getHistory( $pathway, $parser );
		} catch ( MWException $e ) {
			return "Error: $e";
		}
	}

	private static function getHistory( $pathway, $parser ) {
		$gpmlTitle = $pathway->getTitleObject();
		$gpmlArticle = new Article( $gpmlTitle );
		$pager = new self( $pathway, new HistoryPager( $gpmlArticle ) );

		return $pager->getBody();
	}

	private $pathway;
	private $nrShow = 5;

	/**
	 * @param Pathway $pathway id
	 * @param array $pageHistory history array
	 */
	public function __construct( $pathway, $pageHistory ) {
		parent::__construct( $pageHistory );
		$this->pathway = $pathway;
	}

	private static function historyRow( $rowData, $style ) {
		$row = "";
		if ( $rowData ) {
			$row = Html::rawElement(
				"tr", $style,
				Html::rawElement( 'td', null, $rowData['id'] )
				. Html::rawElement( 'td', null, $rowData['view'] . $rowData['revert'] )
				. Html::rawElement( 'td', null, $rowData['date'] )
				. Html::rawElement( 'td', null, $rowData['user'] )
				. Html::element( 'td', null, $rowData['descr'] ) );
		}
		return $row;
	}

	private function gpmlHistoryLine(
		$row,
		$cur = false,
		$firstInList = false
	) {
		global $wgLang, $wgUser;

		$rev = new Revision( $row );

		$user = User::newFromId( $rev->getUser() );
		/* Show bots
		   if($user->isBot()) {
		   //Ignore bots
		   return "";
		   }
		*/

		$rev->setTitle( $this->pathway->getFileTitle( FILETYPE_GPML ) );

		$revUrl = WPI_SCRIPT_URL . '?action=revert&pwTitle=' .
				$this->pathway->getTitleObject()->getPartialURL() .
				"&oldid={$rev->getId()}";

		$revert = "";
		if ( $wgUser->getID() != 0 && $this->pathway->getTitleObject()->userCan( "edit" ) ) {
			$revert = $cur ? "" : "(<A href=$revUrl>revert</A>), ";
		}

		$oldid = $firstInList ? false : $rev->getId();
		$view = Linker::link(
			$this->pathway->getTitleObject(),
			wfMessage( 'wp-history-pager-view' ),
			[],
			$oldid ? [ 'oldid' => $oldid ] : []
		);

		$date = $wgLang->timeanddate( $rev->getTimestamp(), true );
		$user = Linker::userLink( $rev->getUser(), $rev->getUserText() );
		$descr = $rev->getComment();
		return [
			'rev' => $revUrl,
			'view' => $view,
			'revert' => $revert,
			'date' => $date,
			'user' => $user,
			'descr' => $descr,
			'id' => $rev->getId()
		];
	}

	public function formatRow( $row ) {
		$latest = $this->mCounter == 1;
		$firstInList = $this->mCounter == 1;
		$style = ( $this->mCounter <= $this->nrShow ) ? [] : [ 'class' => 'toggleMe' ];
		$this->mCounter++;

		$row = self::historyRow( $this->gpmlHistoryLine(
			$row, $latest, $firstInList
		), $style );

		$this->mLastRow = $row;
		return $row;
	}

	public function getStartBody() {
		$this->mLastRow = false;
		$this->mCounter = 1;

		$numRows = $this->getNumRows();

		$table = '';
		if ( $numRows >= 1 ) {
			if ( $numRows >= $this->nrShow ) {
				$table = Html::rawElement(
					'div', null, Html::element( 'b', [
						'class' => 'toggleLink',
						'data-target' => 'historyTable',
						'data-expand' => 'View all...',
						'data-collapse' => "View last {$this->nrShow}"
					], 'View all...' ) );
			}

			$table .= Html::openElement(
				'table', [
					'id' => 'historyTable',
					'class' => 'wikitable'
				] ) . Html::rawElement(
					'tr', null,
					Html::element( 'th', null, wfMessage( 'wp-history-pager-revision' ) )
					. Html::element( 'th', null, wfMessage( 'wp-history-pager-action' ) )
					. Html::element( 'th', null, wfMessage( 'wp-history-pager-time' ) )
					. Html::element( 'th', null, wfMessage( 'wp-history-pager-user' ) )
					. Html::element( 'th', null, wfMessage( 'wp-history-pager-comment' ) )
					. Html::element( 'th', [
						'id' => 'historyHeaderTag',
						'style' => 'display:none'
					] ) );
		}

		return $table;
	}

	public function getEndBody() {
		return Html::closeElement( "table" );
	}
}
