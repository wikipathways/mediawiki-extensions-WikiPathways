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

use HistoryPager;
use Revision;
use User;
use Linker;
use Xml;

class GpmlHistoryPager extends HistoryPager {
	private $pathway;
	private $nrShow = 5;

	public function __construct( $pathway, $pageHistory ) {
		parent::__construct( $pageHistory );
		$this->pathway = $pathway;
	}

	private static function historyRow($h, $style) {

		if($h) {
			$row = "<TR $style>";
			$row .= "<TD>$h[diff]";
			$row .= "<TD id=\"historyTable_$h[id]_tag\">$h[id]";
			$row .= "<TD>$h[rev]$h[view]";
			$row .= "<TD>$h[date]";
			$row .= "<TD>$h[user]";
			$row .= "<TD>$h[descr]";
			return $row;
		} else {
			return "";
		}
	}


	/**
	 * Create radio buttons for page history
	 *
	 * @param Revision $rev
	 * @param bool $firstInList Is this version the first one?
	 *
	 * @return string HTML output for the radio buttons
	 */
	function diffButtons( $rev, $firstInList ) {
			$id = $rev->getId();
			$radio = [ 'type' => 'radio', 'value' => $id ];
			/** @todo Move title texts to javascript */
			if ( $firstInList ) {
				$first = Xml::element( 'input',
					array_merge( $radio, [
						'style' => 'visibility:hidden',
						'name' => 'oldid',
						'id' => 'mw-oldid-null' ] )
				);
				$checkmark = [ 'checked' => 'checked' ];
			} else {
				# Check visibility of old revisions
				if ( !$rev->userCan( Revision::DELETED_TEXT, $this->getUser() ) ) {
					$radio['disabled'] = 'disabled';
					$checkmark = []; // We will check the next possible one
				} else {
					$checkmark = [];
				}
				$first = Xml::element( 'input',
					array_merge( $radio, $checkmark, [
						'name' => 'oldid',
						'id' => "mw-oldid-$id" ] ) );
				$checkmark = [];
			}
			$second = Xml::element( 'input',
				array_merge( $radio, $checkmark, [
					'name' => 'diff',
					'id' => "mw-diff-$id" ] ) );

			return $first . $second;
	}

	private function gpmlHistoryLine($pathway, $row, $nr, $counter = '', $cur = false, $firstInList = false) {
		global $wpiScript, $wgLang, $wgUser, $wgTitle;

		$rev = new Revision( $row );

		$user = User::newFromId($rev->getUser());
		/* Show bots
		   if($user->isBot()) {
		   //Ignore bots
		   return "";
		   }
		*/

		$rev->setTitle( $pathway->getFileTitle(FILETYPE_GPML) );

		$revUrl = WPI_SCRIPT_URL . '?action=revert&pwTitle=' .
				$pathway->getTitleObject()->getPartialURL() .
				"&oldid={$rev->getId()}";

		$diff = self::diffButtons( $rev, $firstInList, $counter, $nr );

		$revert = "";
		if($wgUser->getID() != 0 && $wgTitle && $wgTitle->userCanEdit()) {
			$revert = $cur ? "" : "(<A href=$revUrl>revert</A>), ";
		}

		$dt = $wgLang->timeanddate( wfTimestamp(TS_MW, $rev->getTimestamp()), true );
		$oldid = $firstInList ? '' : "oldid=" . $rev->getId();
		$view = Linker::link($pathway->getTitleObject(), 'view', ['oldid' => $oldid ] );

		$date = $wgLang->timeanddate( $rev->getTimestamp(), true );
		$user = Linker::userLink( $rev->getUser(), $rev->getUserText() );
		$descr = $rev->getComment();
		return array('diff'=>$diff, 'rev'=>$revert, 'view'=>$view, 'date'=>$date, 'user'=>$user, 'descr'=>$descr, 'id'=>$rev->getId());
	}


	public function formatRow( $row ) {
		$latest = $this->mCounter == 1;
		$firstInList = $this->mCounter == 1;
		$style = ($this->mCounter <= $this->nrShow) ? '' : 'class="toggleMe"';

		$s = self::historyRow( $this->gpmlHistoryLine($this->pathway, $row, $this->getNumRows(), $this->mCounter++, $latest, $firstInList), $style);

		$this->mLastRow = $row;
		return $s;
	}

	public function getStartBody() {
		$this->mLastRow = false;
		$this->mCounter = 1;

		$nr = $this->getNumRows();

		if($nr < 1) {
			$table = '';
		} else {
			$table = '<form action="' . SITE_URL . '/index.php" method="get">';
			$table .= '<input type="hidden" name="title" value="Special:DiffViewer"/>';
			$table .= '<input type="hidden" name="pwTitle" value="' . $this->pathway->getTitleObject()->getFullText() . '"/>';
			$table .= '<input type="submit" value="Compare selected versions"/>';
			$table .= "<TABLE  id='historyTable' class='wikitable'><TR><TH>Compare<TH>Revision<TH>Action<TH>Time<TH>User<TH>Comment<TH id='historyHeaderTag' style='display:none'>";

		}

		if($nr >= $this->nrShow) {
			$expand = "<b>View all...</b>";
			$collapse = "<b>View last " . ($this->nrShow) . "</b>";
			$button = "<table><td width='51%'><div onClick='".
				'doToggle("historyTable", this, "'.$expand.'", "'.$collapse.'")'."' style='cursor:pointer;color:#0000FF'>".
				"$expand<td width='20%'></table>";
			$table = $button . $table;
		}

		return $table;
	}

	public function getEndBody() {
		$end = "</TABLE>";
		$end .= '<input type="submit" value="Compare selected versions"></form>';
		return $end;
	}
}
