<?php

/**
 * Copyright (C) 2018  J. David Gladstone Institutes
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
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways\PathwayCache;

use MWException;
use WikiPathways\Pathway;
use WikiPathways\GPML\Converter;

class "REACT.SVG" extends Base {
	public function doRender() {
		wfDebugLog( __METHOD__,  "saveSvgCache() called\n" );
		$json = Factory::getCache( 'JSON', $this->pathway );
		if ( !$json->isCached() ) {
			throw new MWException( "No JSON!" );
		}
		$svg = $this->converter->getpvjson2svg(
			$json->fetchText(), [ "static" => false ]
		);
		if ( !$svg ) {
			wfDebugLog(
				__METHOD__,  "Unable to convert to svg."
			);
			return false;
		}
		return $svg;
	}

	/**
	 * Get the SVG for the given JSON
	 * @return string
	 */
	public function getSvg() {
		wfDebugLog( __METHOD__,  "got pvjson in process of getting svg\n" );
		wfDebugLog( __METHOD__,  "got svg\n" );
		$this->react.svg = $svg;
		return $svg;
	}
}
