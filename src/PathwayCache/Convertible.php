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
use WikiPathways\GPML\Converter;
use WikiPathways\Pathway;
use WikiPathways\PathwayCache\Factory;

class Convertible extends Base {
	protected $mimeType = "unknown/unknown";

	public function doRender() {
		$gpml = Factory::getCache( "GPML", $this->pathway );

		if ( !$gpml->isCached() ) {
			throw new MWException( "No GPML!" );
		}
		$input = $gpml->getPath();
		$output = $this->getPath();

		$this->convert( $input, $output );
		return file_get_contents( $output );
	}

	/**
	 * Convert the given GPML file to another
	 * file format, using GPMLConverter.
	 * The file format will be determined by the
	 * output file extension.
	 *
	 * @param string $gpmlFile path to source
	 * @param string $outFile path to destination
	 * @param array $opts = [] options
	 * @return bool
	 */
	private function convert( $gpmlFile, $outFile, $opts = [] ) {
		if ( file_exists( $outFile ) ) {
			return true;
		}

		#$gpmlPath = $this->getFileLocation( FILETYPE_GPML );
		$identifier = $this->pathway->getId();
		$version = $this->pathway->getActiveRevision();
		$organism = $this->pathway->getSpecies();

		# TODO: this doesn't seem to match what's going on elsewhere
		Converter::convert(
			$gpmlFile,
			$outFile,
			[ "identifier" => $identifier,
			  "version" => $version,
			  "organism" => $organism ]
		);

		return true;
	}
}
