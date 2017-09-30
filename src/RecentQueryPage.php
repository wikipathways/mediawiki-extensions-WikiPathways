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

class RecentQueryPage extends \QueryPage
{
	public $requestedSort = '';
	private $namespace;

	public function __construct( $namespace )
	{
		$this->namespace = $namespace;
	}

	public function getName()
	{
		return "RecentPathwayChanges";
	}

	public function isExpensive()
	{
		// page_counter is not indexed
		return true;
	}

	public function isSyndicated()
	{
		return false;
	}

	/**
	 * Show a drop down list to select a field for sorting.
	 */
	function getPageHeader()
	{
		global $wgRequest;
		$requestedSort = $wgRequest->getVal('sort');

		$self = $this->getTitle();

		// Form tag
		$out = wfOpenElement('form', [ 'method' => 'post', 'action' => $self->getLocalUrl() ]);

		// Drop-down list
		$out .= wfElement('label', [ 'for' => 'sort' ], 'Sort by:') . ' ';
		$out .= wfOpenElement('select', [ 'name' => 'sort' ]);
		$fields = [ 'Date','Title','User' ];
		foreach ( $fields as $field ) {
			$attribs = [ 'value' => $field ];
			if ($field == $requestedSort ) {
				$attribs['selected'] = 'selected';
			}
			$out .= wfElement('option', $attribs, $field);
		}
		$out .= wfCloseElement('select') . ' ';

		// Submit button and form bottom
		$out .= wfElement('input', [ 'type' => 'submit', 'value' => wfMessage( ('allpagessubmit' )->plain()) ]);
		$out .= wfCloseElement('form');

		return $out;
	}

	public function getSQL()
	{
		$dbr = wfGetDB(DB_SLAVE);
		list( $recentchanges, $watchlist ) = $dbr->tableNamesN('recentchanges', 'watchlist');

		$forceclause = $dbr->useIndexClause("rc_timestamp");

		$sql = "SELECT *,
				'RecentPathwayChanges' as type,
				rc_namespace as namespace,
				rc_title as title,
				UNIX_TIMESTAMP(rc_timestamp) as unix_time,
				rc_timestamp as value
			FROM $recentchanges $forceclause
			WHERE rc_namespace = " . $this->namespace .
		" AND rc_bot = 0
			AND rc_minor = 0 ";

		return $sql;
	}

	public function getOrder()
	{
		global $wgRequest;
		$requestedSort = $wgRequest->getVal('sort');

		if ($requestedSort == 'Title' ) {
			return 'ORDER BY rc_title, rc_timestamp DESC';
		} elseif ($requestedSort == 'User' ) {
			return 'ORDER BY rc_user_text, rc_timestamp DESC';
		} else {
			return 'ORDER BY rc_timestamp DESC';
		}
	}

	public function formatResult( $skin, $result )
	{
		global $wgContLang;

		$userPage = Title::makeTitle(NS_USER, $result->rc_user_text);
		$name = $skin->makeLinkObj($userPage, htmlspecialchars($userPage->getText()));
		$date = date('d F Y', $result->unix_time);
		$comment = ( $result->rc_comment ? $result->rc_comment : "no comment" );
		$titleName = $result->title;
		try {
			$pathway = Pathway::newFromTitle($result->title);
			if (!$pathway->isReadable() ) {
				// Skip private pathways
				return null;
			}
			$titleName = $pathway->getSpecies().":".$pathway->getName();
		} catch ( Exception $e ) {
		}
		$title = Title::makeTitle($result->namespace, $titleName);
		$id = Title::makeTitle($result->namespace, $result->title);

		$this->message['hist'] = wfMessage( 'hist', [ 'escape' ] )->text();
		$histLink = $skin->makeKnownLinkObj(
			$id, $this->message['hist'],
			wfArrayToCGI(
				[
				'curid' => $result->rc_cur_id,
				'action' => 'history'
				]
			)
		);

		$this->message['diff'] = wfMessage( 'diff', [ 'escape' ] )->text();
		if ($result->rc_type > 0 ) {
			// not an edit of an existing page
			$diffLink = $this->message['diff'];
		} else {
			$diffLink = "<a href='" . SITE_URL
			 . "/index.php?title=Special:DiffAppletPage&old="
			 . $result->rc_last_oldid . "&new={$result->rc_this_oldid}"
			 . "&pwTitle={$id->getFullText()}'>diff</a>";
		}

		$text = $wgContLang->convert($result->rc_comment);
		$plink = $skin->makeKnownLinkObj(
			$id, htmlspecialchars($wgContLang->convert($title->getBaseText()))
		);

		/* Not link to history for now, later on link to our own pathway history
		$nl = wfMessage( 'nrevisions', array( 'parsemag', 'escape' )->text(),
		$wgLang->formatNum( $result->value ) );
		$nlink = $skin->makeKnownLinkObj( $nt, $nl, 'action=history' );
		*/

		return wfSpecialList(
			"(".$diffLink.") . . ".$plink. ": <b>".$date."</b> by <b>".$name."</b>",
			"<i>".$text."</i>"
		);
	}
}
