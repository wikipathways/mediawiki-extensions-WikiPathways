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

use WikiPathways\Pathway;
use WikiPathways\GPML\Converter;

class JSON extends Base {
	protected $mimeType = "application/json";

	/**
	 * Get the JSON for this pathway, as a string (the active revision
	 * will be used, see Pathway::getActiveRevision) Gets the JSON
	 * representation of the GPML code, formatted to match the
	 * structure of SVG, as a string.  TODO: we aren't caching this
	 */
	public function doRender() {
		$gpml = Factory::getCache( 'GPML', $this->pathway );

		if ( !$gpml->isCached() ) {
			error_log( "No file for GPML!" );
			return false;
		}

		$pathId = $this->pathway->getId();
		$ver = $this->pathway->getActiveRevision();
		$json = $this->converter->gpml2pvjson(
			$gpml->fetchText(),
			[ "identifier" => $pathId, "version" => $ver,
			  "organism" => $this->pathway->getSpecies() ]
		);
		if ( $json ) {
			wfDebugLog( __METHOD__,  "Converted gpml to pvjson\n" );
			return $json;
		}
		$err = error_get_last();
		$msg = "Trouble converting $pathId (v $ver) : {$err['message']}";
		wfDebugLog( __METHOD__,  "$msg\n" );
		error_log( $msg );
		return false;
	}
}
