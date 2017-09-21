<?php
/**
 * Provide an information and cross-reference panel for xrefs on a wiki page.
 *
 * <Xref id="1234" datasource="L" species="Homo sapiens">Label</Xref>
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
 * @author
 * @author Mark A. Hershberger <mah@nichework.com>
 */

namespace WikiPathways;

class XrefPanel {
	public static function xref() {
		global $wgParser;
		$wgParser->setHook( "Xref", "XrefPanel::renderXref" );

		self::addXrefPanelScripts();
	}

	public static function renderXref( $input, $argv, &$parser ) {
		return wpiXrefHTML(
			$argv['id'], $argv['datasource'], $input, $argv['species']
		);
	}

	public static function getXrefHTML(
		$id, $datasource, $label, $text, $species
	) {
		$datasource = json_encode( $datasource );
		$label = json_encode( $label );
		$id = json_encode( $id );
		$species = json_encode( $species );
		$url = SITE_URL . '/skins/common/images/info.png';
		$fun = 'XrefPanel.registerTrigger(this, '
			 . "$id, $datasource, $species, $label);";
		$title = "Show additional info and linkouts";
		$html = $text . " <img title='$title' style='cursor:pointer;'"
			  . " onload='$fun' src='$url'/>";
		return $html;
	}

	public static function getJsDependencies() {
		global $jsJQueryUI, $wgScriptPath;

		$js = [ "$wgScriptPath/wpi/js/xrefpanel.js", $jsJQueryUI ];

		return $js;
	}

	public static function getJsSnippets() {
		global $wpiXrefPanelDisableAttributes, $wpiBridgeUrl,
			$wpiBridgeUseProxy;

		$js = [];

		$js[] = 'XrefPanel_searchUrl = "' . SITE_URL
			  . '/index.php?title=Special:SearchPathways'
			  . '&doSearch=1&ids=$ID&codes=$DATASOURCE&type=xref";';
		if ( $wpiXrefPanelDisableAttributes ) {
			$js[] = 'XrefPanel_lookupAttributes = false;';
		}

		$bridge = "XrefPanel_dataSourcesUrl = '" . WPI_CACHE_URL
				. "/datasources.txt';\n";

		if ( $wpiBridgeUrl !== false ) {
			if ( !isset( $wpiBridgeUrl ) || $wpiBridgeUseProxy ) {
				// Point to bridgedb proxy by default
				$bridge .= "XrefPanel_bridgeUrl = '" . WPI_URL
						. '/extensions/bridgedb.php' . "';\n";
			} else {
				$bridge .= "XrefPanel_bridgeUrl = '$wpiBridgeUrl';\n";
			}
		}
		$js[] = $bridge;

		return $js;
	}

	public static function addXrefPanelScripts() {
		global $wpiJavascriptSources, $wpiJavascriptSnippets,
			$cssJQueryUI, $wgScriptPath, $wgStylePath, $wgOut,
			$jsRequireJQuery;

		$jsRequireJQuery = true;

		// Hack to add a css that's not in the skins directory
		$oldStylePath = $wgStylePath;
		$wgStylePath = dirname( $cssJQueryUI );
		$wgOut->addStyle( basename( $cssJQueryUI ) );
		$wgStylePath = $oldStylePath;
	}
}
