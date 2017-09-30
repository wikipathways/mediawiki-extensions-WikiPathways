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

use Exception;
use Linker;

class PathwayBibliography {
	public static function output($input, $argv, $parser) {
		$parser->disableCache();
		try {
			$pathway = Pathway::newFromTitle($parser->mTitle);
			return self::getHTML($pathway, $parser);
		} catch(Exception $e) {
			return "Error: $e";
		}
	}

	private static function getHTML($pathway, $parser) {
		global $wgUser;

		$data = $pathway->getPathwayData();
		$gpml = $pathway->getGpml();

		$i = 0;
		$nrShow = 5;

		if(!$data) return "";

		//Format literature references
		$pubXRefs = $data->getPublicationXRefs();
		$out = "";
		foreach(array_keys($pubXRefs) as $id) {
			$doShow = $i++ < $nrShow ? "" : "class='toggleMe'";

			$xref = $pubXRefs[$id];

			$authors = $title = $source = $year = '';

			//Format the citation ourselves
			//Authors, title, source, year
			foreach($xref->AUTHORS as $a) {
				$authors .= "$a, ";
			}

			if($authors) $authors = substr($authors, 0, -2) . "; ";
			if($xref->TITLE) $title = "''" . $xref->TITLE . "''; ";
			if($xref->SOURCE) $source = $xref->SOURCE;
			if($xref->YEAR) $year = ", " . $xref->YEAR;
			$out .= "<LI $doShow>$authors" . $title . "$source$year";

			if((string)$xref->ID && (strtolower($xref->DB) == 'pubmed')) {
				$l = new Linker();
				$out .= ' '. $l->makeExternalLink( 'http://www.ncbi.nlm.nih.gov/pubmed/' . $xref->ID, "PubMed" );
			}
		}

		$id = 'biblist';
		$hasRefs = (boolean)$out;
		if($hasRefs) {
			$out = "<OL id='$id'>$out</OL>";
			$nrNodes = count($pubXRefs);
			if($nrNodes > $nrShow) {
				$expand = "<b>View all...</b>";
				$collapse = "<b>View last " . ($nrShow) . "</b>";
				$button = "<table><td width='51%'><div onClick='".
					'doToggle("'.$id.'", this, "'.$expand.'", "'.$collapse.'")'."' style='cursor:pointer;color:#0000FF'>".
					"$expand</div><td width='45%'></table>";
				$out = $button . $out;
			}
		} else {
			$out = "<I>No bibliography</i>\n";
		}
		//Handle hook template, may be used to add custom info after bibliography section
		$hookTmp = "{{#ifexist: Template:PathwayPage:BibliographyBottom | {{Template:PathwayPage:BibliographyBottom|hasRefs=$hasRefs}} | }}";
		$hookTmp = $parser->recursiveTagParse( $hookTmp );
		return $out . $hookTmp;
	}
}
