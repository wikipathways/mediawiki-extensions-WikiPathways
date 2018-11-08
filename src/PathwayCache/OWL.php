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

class OWL extends Base {
	protected $mimeType = "application/vnd.biopax.rdf+xml";

	/**
	 * Get the OWL for the given GPML
	 * @return string
	 */
	public function doRender() {
		$gpml = Factory::getCache( 'GPML', $this->pathway );

		if ( !$gpml->isCached() ) {
			error_log( "No file for GPML!" );
			return false;
		}

		$owl = $this->converter->getgpml2owl(
			$gpml->fetchText(),
			[]
		);
		if ( $owl ) {
			wfDebugLog( __METHOD__,  "Converted gpml to owl\n" );
			return $owl;
		}
		$err = error_get_last();
		$pathId = $this->pathway->getId();
		$ver = $this->pathway->getActiveRevision();
		$msg = "Trouble converting $pathId (v $ver) : {$err['message']}";
		wfDebugLog( __METHOD__,  "$msg\n" );
		error_log( $msg );
		return false;
	}
}
