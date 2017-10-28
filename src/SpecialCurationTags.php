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

use Linker;
use MediaWiki\MediaWikiServices;
use Title;

class SpecialCurationTags extends \SpecialPage {
	function __construct() {
		parent::__construct( "CurationTags" );
	}

	private $tagNames;

	function execute( $par ) {
		global $wgOut, $wgUser, $wgLang, $wgRequest, $wgTitle;
		$url = $wgTitle->getLocalURL();
		$this->setHeaders();

		if ( $tagName = $wgRequest->getVal( 'showPathwaysFor' ) ) {
			$disp = htmlentities( CurationTag::getDisplayName( $tagName ) );
			$wgOut->setPageTitle( wfMsgExt( 'curation-tag-show', [ 'parsemsg' ], $disp ) );
			$wgOut->addScriptFile( "../wikipathways/CurationTags.js" );
			$def = CurationTag::getTagDefinition();
			// Don't you just love how php does things?
			$useRev  = CurationTag::useRevision( $tagName );
			$newEdit = CurationTag::newEditHighlight( $tagName );
			$action  = CurationTag::highlightAction( $tagName );

			$pages = CurationTag::getPagesForTag( $tagName );
			$table = "";
			$nr = 0;
			foreach ( $pages as $pageId ) {
				try {
					$t = Title::newFromId( $pageId );
					if($t->getNamespace() == NS_PATHWAY ) {
						$p = Pathway::newFromTitle( $t );
						if ( $p->isDeleted() ) {
							//Skip deleted pathways
							continue;
						}

						$nr = $nr + 1;

						$data = [];
						$data[] = "<a href='{$p->getFullUrl()}'>{$p->name()}</a>";
						$data[] = $p->species();

						$tag = new MetaTag( $tagName, $pageId );
						$umod = User::newFromId( $tag->getUserMod() );
						$data[] = $wgUser->getSkin()->userLink( $umod->getId(), $umod->getName() );
						$data[] = "<i style='display: none'>{$tag->getTimeMod()}</i>".
							$wgLang->timeanddate( $tag->getTimeMod(), true );

						$ts = Revision::newFromId( $p->getLatestRevision() )->getTimestamp();
						if ( $useRev ) {
							if ( $p->getLatestRevision() == $tag->getPageRevision() ) {
								$data[] = "<font color='green'>yes</font>";
							} else {
								$ts = $p->getFirstRevisionAfterRev( $tag->getPageRevision() )->getTimestamp();
								$data[] = "<font color='red'><i style='display:none'>$ts</i>".
									$wgLang->timeAndDate( $ts ) ."</font>";
							}
						}

						if ( $newEdit ) {
							// Last Edited date
							$data[] = "<i style='display:none'>$ts</i>".
								$wgLang->timeAndDate( $ts );
						}

						$row = tableRowFactory::produce( $action, $data );
						$row->action( $tag, $ts, $newEdit );
						$table .= $row->format();
					}
				} catch ( Exception $e ) {
					wfDebug( "SpecialCurationTags: unable to create pathway object for page "
							 . $pageId );
				}
			}

			$wgOut->addWikiText(
				"The table below shows all $nr pathways that are tagged with curation tag: " .
				"'''$disp'''. "
			);
			$wgOut->addHTML( "<p><a href='$url'>back</a></p>" );
			$wgOut->addHTML( "<table class='prettytable sortable'><tbody>" );
			$wgOut->addHTML( "<tr><th>Pathway name<th>Organism<th>Tagged by<th>Date tagged" );
			if ( $useRev ) {
				$wgOut->addHTML( "<th>Applies to latest revision" );
			}
			if ( $newEdit ) {
				$wgOut->addHTML( "<th>Last Edited" );
			}
			$wgOut->addHTML( $table );
		} else {
			$wgOut->addWikiText( "This page lists all available curation tags. "
								 . "See the [[Help:CurationTags|help page]] for instructions "
								 . "on how to use curation tags." );
			$wgOut->addHTML("<table class='prettytable sortable'><tbody>");
			$wgOut->addHTML("<th>Name<th>Template<th>Description");
			$this->tagNames = CurationTag::getTagNames();
			foreach($this->tagNames as $tagName) {
				$tmp = Title::newFromText("Template:$tagName");
				$disp = htmlentities(CurationTag::getDisplayName($tagName));
				$descr = htmlentities(CurationTag::getDescription($tagName));
				$wgOut->addHTML("<tr><td>$disp<td>");
				$services = MediaWikiServices::getInstance();
				$linkRenderer = $services->getLinkRenderer();
				$wgOut->addHTML( $linkRenderer->makeLink( $tmp, $tagName ) );
				$wgOut->addHTML( "<td>$descr" );
				$urlName = htmlentities( $tagName );
				$url = $wgTitle->getLocalURL("showPathwaysFor=$urlName");
				$wgOut->addHTML("<td><a href='".$url."'>Show pathways</a>");
			}
		}
		$wgOut->addHTML("</tbody></table>");
	}
}

class tableRow {
	protected $data;
	protected $action = false;

	public function __construct( $d ) {
		$this->data = $d;
	}

	public function action( $tag, $ts, $delta ) {
	}

	public function format( $id = null ) {
		if( $id ) {
			return "<tr id='$id'><td>".implode( "<td>", $this->data );
		} else {
			return "<tr><td>".implode( "<td>", $this->data );
		}
	}
}

// Primative
class tableRowFactory {
	static public function produce( $type, $data ) {
		if($type == "underConstruction") {
			return new underConstructionRow( $data );
		}
		elseif($type == "delete") {
			return new deleteRow( $data );
		}
		else {
			return new tableRow( $data );
		}
	}
}


class underConstructionRow extends tableRow {
	public function action( $tag, $ts, $delta ) {
		global $wgLang;
		$date = date_create( $ts );
		$prev = date_create( "now" );
		$prev->modify( "-30 days" );

		// http://developers.pathvisio.org/ticket/1534#comment:21
		$dateFormated = $date->format("YmdHis");
		if( $dateFormated < $tag->getTimeMod()
			|| $dateFormated < $prev->format("YmdHis") ) {
			$this->action = true;
		} else {
			$this->action = false;
		}
		$a = $tag->getTimeMod();
		$b = $prev->format("YmdHis");
	}

	public function format( $id = null ) {
		// Row is red if the last edit date (5th column) is not after the tag date (4th column)
		// or if the last edit is older than 30 days
		$style = "";
		if( $this->action ) {
			$style = " class='notUnderConstruction'";
		}
		return "<tr$style><td>".implode( "<td>", $this->data )."\n";
	}
}

class deleteRow extends tableRow {
	public function action( $tag, $ts, $delta ) {
		global $wgLang;
		$prev = date_create( "now" );
		$prev->modify( "-$delta" );
		$date = date_create( $ts );

		if( $date->format("YmdHis") < $tag->getTimeMod() && $prev->format("YmdHis") > $tag->getTimeMod() ) {
			/* In the future, we'll set this to the ID of the tag or page, but for now ... */
			$this->action = $tag->getPageId();
		} else {
			$this->action = false;
		}
	}

	private function deleteButton( $row ) {
		global $wgUser, $wgStylePath;
		$pageId = $this->action;

		if( $wgUser->isLoggedIn() ) {
			return "<A title='". wfmsg( "wpict-delete" ) . "' ".
				"href='javascript:CurationTags.removeTagFromPathway(\"Curation:ProposedDeletion\", $pageId, \"$row\" )'>" .
				"<IMG class='center-button' src='$wgStylePath/wikipathways/cancel.png'/></A>";
		} else {
			return "";
		}
	}

	public function format( $id = null ) {
		// show a delete button
		$row = "";
		if( $this->action ) {
			$row = "row".$this->action;
		}
		return parent::format( $row )."<td>".( $this->action !== false ?
			$this->deleteButton( $row ) : wfMsg( "wpict-too-new" ) );
	}
}
