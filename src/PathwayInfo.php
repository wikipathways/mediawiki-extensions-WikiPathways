<?php
/**
 * Generates info text for pathway page
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
 * @author Thomas Kelder <thomaskelder@gmail.com>
 * @author Alexander Pico <apico@gladstone.ucsf.edu>
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways;

use Exception;
use Html;
use Parser;
use Linker;
use Title;

class PathwayInfo extends PathwayData {
	private $parser;

	/**
	 * Get the pathway info text.  Handles the {{#pathwayInfo}} parser
	 * function.
	 *
	 * @param Parser $parser guess
	 * @param string $pathway identifier
	 * @param string $type to get
	 * @return string
	 */
	public static function getPathwayInfoText( Parser $parser, $pathway, $type ) {
		global $wgRequest;
		$parser->disableCache();
		$pathway = Title::newFromText( $pathway, NS_PATHWAY );
		if ( $pathway->exists() ) {
			try {
				$pathway = Pathway::newFromTitle( $pathway );
				$oldid = $wgRequest->getval( 'oldid' );
				if ( $oldid ) {
					$pathway->setActiveRevision( $oldid );
				}
				$info = new PathwayInfo( $parser, $pathway );

				if ( !$type ) {
					return wfMessage( "wp-pathway-info-no-type", $type );
				}
				if ( method_exists( $info, $type ) ) {
					return $info->$type();
				} else {
					return wfMessage( "wp-pathway-info-wrong-param", $type );
				}
			} catch ( Exception $e ) {
				return "Error: $e";
			}
		}
		return wfMessage( "wp-no-id-found" )->params( $pathway );
	}

	/**
	 * @param Parser $parser from function
	 * @param string $pathway page we're looking at
	 */
	public function __construct( Parser $parser, $pathway ) {
		parent::__construct( $pathway );
		$this->parser = $parser;
	}

	/**
	 * @return string
	 */
	private function getDataNodesHeader() {
		return Html::openElement( "table", [
			'class' => 'wikitable sortable',
			'id' => 'dnTable'
		] ) . Html::openElement( "tbody" )
			. Html::element( "th", null, wfMessage( "wp-pathwayinfo-name" ) )
			. Html::element( "th", null, wfMessage( "wp-pathwayinfo-type" ) )
			. Html::element( "th", null, wfMessage( "wp-pathwayinfo-dbref" ) )
			. Html::element( "th", null, wfMessage( "wp-pathwayinfo-comment" ) );
		// style="border:1px #AAA solid;margin:1em 1em 0;background:#F9F9F9"
	}

	private function getFooter() {
		return '</tbody></table>';
	}

	private function getUniqueNodes() {
		$all = $this->getElements( 'DataNode' );
		$nodes = [];

		foreach ( $all as $elm ) {
			$key = $elm['TextLabel'];
			$key .= $elm->Xref['ID'];
			$key .= $elm->Xref['Database'];
			$nodes[(string)$key] = $elm;
		}
		return $nodes;
	}

	private function getTextLabel( $elm ) {
		if ( isset( $elm['TextLabel'] ) ) {
			return $elm['TextLabel'];
		}
		return '';
	}

	private function getSource( $elm ) {
		return $this->getTextLabel( $elm->getSource() );
	}

	private function getTarget( $elm ) {
		return $this->getTextLabel( $elm->getTarget() );
	}

	private function getUniqueAnnotations() {
		$all = $this->getAllAnnotatedInteractions();
		$nodes = [];

		foreach ( $all as $elm ) {
			if ( $elm->getEdge()->Xref['ID'] != "" && $elm->getEdge()->Xref['Database'] != "" ) {
				$key = $this->getSource( $elm );
				$key .= $this->getTarget( $elm );
				$key .= $elm->getType();
				$key .= $elm->getEdge()->Xref['ID'];
				$key .= $elm->getEdge()->Xref['Database'];
				$nodes[(string)$key] = $elm;
			}
		}
		return $nodes;
	}

	private function displayItem( $item ) {
		$ret = "";
		if ( count( $item ) > 1 ) {
			$ret .= "<ul>";
			foreach ( $item as $c ) {
				$ret .= "<li>$c";
			}
			$ret .= "</ul>";
		} elseif ( count( $item ) == 1 ) {
			$ret .= $item[0];
		}
		return $ret;
	}

	private function getComment( $datanode ) {
		// Comment Data

		$comment = [];
		foreach ( $datanode->children() as $child ) {
			if ( $child->getName() == 'Comment' ) {
				$comment[] = (string)$child;
			}
		}
		return $this->displayItem( $comment );
		// This did used to do biopaxrefs:
		// http://developers.pathvisio.org/ticket/800#comment:9
	}

	private function getLink( $xid, $xds, $xref ) {
		$link = DataSource::getLinkout( $xid, $xds );
		if ( $link ) {
			$linker = new Linker();
			$link = $linker->makeExternalLink( $link, "$xid ({$xref['Database']})" );
		} elseif ( $xid != '' ) {
			$link = $xid;
			if ( $xref['Database'] ) {
				$link .= ' (' . $xref['Database'] . ')';
			}
		}
		return $link;
	}

	// Add xref info button
	private function getXrefHTML( $xid, $xds, $xref, $element ) {
		$html = $this->getLink( $xid, $xds, $xref );
		if ( $xid && $xds ) {
			$this->parser->getOutput()->addModules( [ "wpi.XrefPanel" ] );
			$html = XrefPanel::getXrefHTML(
				$xid, $xds, $element, $html, $this->getOrganism()
			);
		}
		return $html;
	}

	private function getDataNodesTable( array $nodes, $nrShow ) {
		$table = $this->getDataNodesHeader();

		ksort( $nodes );
		$row = 0;
		foreach ( $nodes as $datanode ) {
			$xref = $datanode->Xref;
			$xds = (string)$xref['Database'];
			$xid = trim( $xref['ID'] );

			$html = $this->getXrefHTML( $xid, $xds, $xref, $datanode['TextLabel'] );

			$table .= Html::rawElement(
				"tr", [ 'class' => ( $row++ < $nrShow ? "" : "toggleMe" ) ],
				Html::rawElement( 'td', null,  $datanode['TextLabel'] )
				. Html::rawElement(
					'td', [ 'class' => 'path-type' ], $datanode['Type']
				) . Html::rawElement(
					'td', [ 'class' => 'path-dbref' ], $html
				) . Html::rawElement(
					'td', [ 'class' => 'path-comment' ], $this->getComment( $datanode )
				)
			);
		}
		$table .= $this->getFooter();
		return $table;
	}

	/**
	 * Creates a table of all datanodes and their info.
	 *
	 * Note that this is only really called as a parameter to the
	 * {{#pathwayInfo}} parser function.  See MediaWiki::wp-gpml-xrefs
	 * defined in WikiPathways::GPML
	 *
	 * Note similarity to interactionAnnotations() below.
	 *
	 * @return array
	 */
	public function datanodes() {
		// Check for uniqueness, based on textlabel and xref
		$nodes = $this->getUniqueNodes();

		// Create collapse button
		$nrShow = 5;
		$button = Pathway::toggleElement( "dnTable", count( $nodes ), $nrShow );

		if ( count( $nodes ) == 0 ) {
			$table = "<cite>No datanodes</cite>";
		} else {
			// Sort and iterate over all elements
			$table = $this->getDataNodesTable( $nodes, $nrShow );
		}
		return [ $button . $table, 'isHTML' => 1, 'noparse' => 1 ];
	}

	/**
	 * Creates a table of all interactions and their info.
	 *
	 * Note that this is only really called as a parameter to the
	 * {{#pathwayInfo}} parser function.  See MediaWiki::wp-gpml-xrefs
	 * defined in WikiPathways::GPML
	 *
	 * Note similiarity to datanodes() above.
	 *
	 * @return array
	 */
	public function interactionAnnotations() {
		$annotations = $this->getUniqueAnnotations();

		// Create collapse button
		$nrShow = 5;
		$button = Pathway::toggleElement( "inTable", count( $annotations ), $nrShow );

		if ( count( $annotations ) == 0 ) {
			$table = "<cite>No annotated interactions</cite>";
		} else {
			// sort and iterate over all elements'
			$table = $this->getAnnotationsTable( $annotations, $nrShow );
		}
		return [ $button . $table, 'isHTML' => 1, 'noparse' => 1 ];
	}

	private function getAnnotationsHeader() {
		return Html::openElement( 'table', [
			"class" => "wikitable sortable",
			"id" => "inTable"
		] ) . Html::openElement( "tbody" )
			. Html::element( "th", null, wfMessage( "wp-annotations-source" ) )
			. Html::element( "th", null, wfMessage( "wp-annotations-target" ) )
			. Html::element( "th", null, wfMessage( "wp-annotations-type" ) )
			. Html::element( "th", null, wfMessage( "wp-annotations-dbref" ) )
			. Html::element( "th", null, wfMessage( "wp-annotations-comment" ) );
	}

	private function getAnnotationsTable( array $nodes, $nrShow ) {
		$table = $this->getAnnotationsHeader();

		// Sort and iterate over all elements
		ksort( $nodes );
		$row = 0;
		foreach ( $nodes as $datanode ) {
			$xref = [];
			if ( isset( $int->Xref ) ) {
				$xref = $int->Xref;
			}
			$xds = "";
			$xid = "";
			if ( isset( $xref['Database'] ) ) {
				$xds = (string)$xref['Database'];
				$xid = trim( $xref['ID'] );
			}
			$int = $datanode->getEdge();

			$html = $this->getXrefHTML( $xid, $xds, $xref, $xid );

			$table .= Html::rawElement(
				"tr", [ 'class' => ( $row++ < $nrShow ? "" : "toggleMe" ) ],
				Html::rawElement(
					'td', [ 'class' => 'path-source' ], $this->getSource( $datanode )
				) . Html::rawElement(
					'td', [ 'class' => 'path-targert', 'align' => 'center' ],
					$this->getTarget( $datanode )
				) . Html::rawElement(
					'td', [ 'class' => 'path-type', 'align' => 'center' ],
					$datanode->getType()
				) . Html::rawElement(
					'td', [ 'class' => 'path-dbref', 'align' => 'center' ], $html
				) . Html::rawElement(
					'td', [ 'class' => 'path-comment' ], $this->getComment( $int )
				)
			);
		}
		$table .= $this->getFooter();
		return $table;
	}
}
