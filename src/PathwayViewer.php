<?php
/**
 * Enable pvjs (interactive pathway viewer/editor)
 * Used in both pathway page and widget.
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
 * @author ...
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways;

class PathwayViewer {
	public static function getJsDependencies() {
		global $wgScriptPath;

		$scripts = [
			// What are these for?
			"$wgScriptPath/wpi/js/jquery/plugins/jquery.mousewheel.js",
			"$wgScriptPath/wpi/js/jquery/plugins/jquery.layout.min-1.3.0.js",

			// pvjs and dependencies
			"//cdnjs.cloudflare.com/ajax/libs/d3/3.5.5/d3.min.js",
			"//mithril.js.org/archive/v0.2.2-rc.1/mithril.min.js",

			// TODO remove the polyfill bundle below once the autopolyfill
			// work is complete. Until then, leave it as-is.
			"$wgScriptPath/wpi/lib/pvjs/release/polyfills.bundle.min.js",
			// "$wgScriptPath/wpi/lib/pvjs/dev/pvjs.core.js",
			// "$wgScriptPath/wpi/lib/pvjs/dev/pvjs.custom-element.js",

			"$wgScriptPath/wpi/lib/pvjs/release/pvjs.core.min.js",
			"$wgScriptPath/wpi/lib/pvjs/release/pvjs.custom-element.min.js",
		];

		return $scripts;
	}

	public static function enable( &$parser, $pwId, $imgId ) {
		global $wgStylePath, $wpiJavascriptSources,
			$wpiJavascriptSnippets, $wgRequest, $wgJsMimeType;

		// FIXME: This requires jsquery so check verson deps
		try {
			$wpiJavascriptSources = array_merge(
				$wpiJavascriptSources, self::getJsDependencies()
			);

			$revision = $wgRequest->getval( 'oldid' );

			$pathway = Pathway::newFromTitle( $pwId );

			if ( $revision ) {
				$pathway->setActiveRevision( $revision );
			}
		} catch ( Exception $e ) {
			return "invalid pathway title: $e";
		}
	}

}
