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

use Parser;

class OntologyTags {

	# Maximum number of search results returned while searching BioPortal
	const BIO_PORTAL_SEARCH_HITS = 12;

	# Time after which data in the cache is refreshed (in Seconds)
	const EXPIRY_TIME = 3600 * 24 * 7;

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
	 * @param Parser $parser object
	 * @param string &$text to change
	 * @SuppressWarnings(UnusedFormalParameter)
	 */
	public function onHeader( Parser $parser, &$text ) {
		$text = preg_replace_callback(
			'/<!-- ENCODED_CONTENT ([0-9a-zA-Z\\+]+=*) -->/',
			function ( $matches ) {
				return base64_decode( $matches[1] );
			}, $text );
	}

	/**
	 * Use this instead of $wgOntologiesJSON or $wgOntologiesArray
	 *
	 * @return array of arrays of ontology information
	 */
	public static function getOntologies() {
		return array_filter(
			json_decode( wfMessage( "wp-ontology-tags.json" )->plain() ),
			function ( $value ) { return is_array( $value ); }
		);
	}

	/**
	 * Use this instead of $wgOntologiesBioPortalEmail
	 *
	 * Email address for the User Identification parameter to be used
	 * while making REST calls to BioPortal.
	 *
	 * @return string
	 */
	public static function getBioPortalEmail() {
		return wfMessage( "wp-ontology-bio-portal-email" )->plain();
	}

	/**
	 * Parser hook for <OntologyTags>
	 *
	 * @param string $input inside the tag
	 * @param array $argv attributes
	 * @param Parser $parser object
	 * @return string
	 */
	public static function tag( $input, $argv, Parser $parser ) {
		global $wgOut, $opath;

		$title = $parser->getTitle();
		$userCanEdit = $title->userCan( 'edit' ) ? 1 : 0;

		$yuiModule = "wpi.OntNoEdit";
		if ( $userCanEdit ) {
			$yuiModule = "wpi.OntCanEdit";
		}

		$wgOut->addModules( [
			'wpi.CurationTags', 'wpi.AuthorInfo', 'wpi.XrefPanel', 'wpi.OntologyTags', $yuiModule
		] );

		/* This is all bogus */
		// $wgOut->addScript(
		// 	"<script type=\"{$wgJsMimeType}\">" .
		// 	"var opath=\"$opath\";" .
		// 	"var otagloggedIn = \"$userCanEdit\";" .
		// 	"var ontologiesJSON = '$wgOntologiesJSON';" .
		// 	"</script>\n"
		// );

		$wgStylePath = "/extensions/WikiPathways";
		if ( $userCanEdit ) {
			$output = <<<HTML
<div id="otagprogress" style="display:none" align='center'><span><img src='$wgStylePath/images/progress.gif'> Saving...</span></div>
<div id="ontologyContainer" class="yui-skin-sam">
	<div id="ontologyMessage" style="display:none;">No Tags!</div>
	<div id="ontologyTags" style="display:none;"></div>
	<div id="ontologyTagDisplay">&nbsp;</div>
	<a href="javascript:toggleOntologyControls();" id="ontologyEditLabel">Add Ontology tags</a><br /><br />
	<div id="ontologyEdit" style="display:none;">
		<div id="myAutoComplete">
			<input id="ontologyACInput" type="text" onfocus="clearBox();" value="Type Ontology term.."/>
			<div id="myContainer"></div>
		</div>
		<div id="otaghelp" class="otaghelp">To add a tag, either select from the available ontology trees below or type a search term in the search box.</div>
		<div style="clear:both;"></div>
		<div id="ontologyTrees"></div>
	</div>
</div>
<div style="clear:both;"></div>
<script type="text/javascript">
	YAHOO.util.Event.onDOMReady(ontologytree.init, ontologytree,true);
</script>
HTML;
		} else {
			$output = <<<HTML
<div id="otagprogress" style="display:none" align='center'><span><img src='$wgStylePath/images/progress.gif'> Saving...</span></div>
<div id="ontologyContainer" class="yui-skin-sam">
<div id="ontologyMessage" style="display:none;">No Tags!</div>
<div id="ontologyTags" style="display:none;"> </div>
<div id="ontologyTagDisplay">&nbsp;</div>
</div>
HTML;
		}
		return '<!-- ENCODED_CONTENT '.base64_encode( $output ).' -->';
	}
}