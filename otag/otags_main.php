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

if ( !defined( 'MEDIAWIKI' ) ) {
	echo "This file is part of MediaWiki, it is not a valid entry point.\n";
	exit( 1 );
}
$opath = WPI_URL . "/extensions/otag";
$wgExtensionFunctions[] = "wfotag";

function wfotag() {
	global $wgHooks;
	global $wgParser;
	$wgHooks['ParserAfterTidy'][] = 'oheader';
	$wgParser->setHook( "OntologyTags", "ofunction" );
}

function oheader( &$parser, &$text ) {
	$text = preg_replace_callback(
		'/<!-- ENCODED_CONTENT ([0-9a-zA-Z\\+]+=*) -->/',
		function ( $matches ) {
			return base64_decode( $matches[1] );
		}, $text );

	return true;
}

function ofunction( $input, $argv, $parser ) {
	global $wgTitle, $wgOut,  $opath, $wgOntologiesJSON, $wgStylePath, $wgJsMimeType;
	$oldStylePath = $wgStylePath;
	$wgStylePath = $opath . "/css/";

	$title = $parser->getTitle();
	$loggedIn = $title->userCan( 'edit' ) ? 1 : 0;

	if ( $loggedIn ) {
		$wgOut->addScript( '<script type="text/javascript" src="' . $opath . '/js/yui2.7.0.allcomponents.js"></script>' );
		$wgOut->addStyle( "yui2.7.0.css" );
	} else {
		$wgOut->addScript( '<script type="text/javascript" src="' . $opath . '/js/yui2.7.0.mincomponents.js"></script>' );
	}

	$wgOut->addStyle( "otag.css" );
	$wgStylePath = $oldStylePath;

	$wgOut->addScript(
		"<script type=\"{$wgJsMimeType}\">" .
		"var opath=\"$opath\";" .
		"var otagloggedIn = \"$loggedIn\";" .
		"var ontologiesJSON = '$wgOntologiesJSON';" .
		"</script>\n"
	);

	if ( $loggedIn ) {
		$output = <<<HTML
<div id="otagprogress" style="display:none" align='center'><span><img src='$wgStylePath/common/images/progress.gif'> Saving...</span></div>
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
<script type="text/javascript" src="$opath/js/script.js"></script>
<script type="text/javascript">
	YAHOO.util.Event.onDOMReady(ontologytree.init, ontologytree,true);
</script>
HTML;
	} else {
		$output = <<<HTML
<div id="otagprogress" style="display:none" align='center'><span><img src='$wgStylePath/common/images/progress.gif'> Saving...</span></div>
<div id="ontologyContainer" class="yui-skin-sam">
<div id="ontologyMessage" style="display:none;">No Tags!</div>
<div id="ontologyTags" style="display:none;"> </div>
<div id="ontologyTagDisplay">&nbsp;</div>
</div>
<script type="text/javascript" src="$opath/js/script.js"></script>
HTML;
	}
	return '<!-- ENCODED_CONTENT '.base64_encode( $output ).' -->';
}
