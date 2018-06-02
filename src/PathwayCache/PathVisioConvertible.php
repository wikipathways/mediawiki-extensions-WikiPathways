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
use WikiPathways\PathwayCache\Factory;

class PathVisioConvertible extends Base {
	protected $mimeType = "unknown/unknown";

	public function doRender() {
		$gpml = Factory::getCache( "GPML", $this->pathway );

		if ( !$gpml->isCached() ) {
			throw new MWException( "No GPML!" );
		}
		$input = $gpml->getPath();
		$output = $this->getPath();

		$this->convertWithPathVisio( $input, $output );
		return file_get_contents( $output );
	}

	/**
	 * Convert the given GPML file to another
	 * file format, using PathVisio-Java. The file format will be determined by the
	 * output file extension.
	 *
	 * @param string $gpmlFile source
	 * @param string $outFile destination
	 * @return bool
	 */
	private function convertWithPathVisio( $gpmlFile, $outFile ) {
		global $wgMaxShellMemory;
		if ( file_exists( $outFile ) ) {
			return true;
		}

		$gpmlFile = realpath( $gpmlFile );

		$basePath = WPI_SCRIPT_PATH;
		// Max script memory on java program in megabytes
		$maxMemoryM = intval( $wgMaxShellMemory / 1024 );

		$cmd = "java -Xmx{$maxMemoryM}M -jar '$basePath/bin/pathvisio_core.jar' "
			 . "'$gpmlFile' '$outFile' 2>&1";
		wfDebugLog( __METHOD__,  "CONVERTER: $cmd\n" );
		$msg = wfShellExec( $cmd, $status, [], [ 'memory' => 0 ] );

		if ( $status != 0 ) {
			throw new MWException(
				"Unable to convert to $outFile:\n\nStatus: $status\n\nMessage: $msg\n\n"
				. "Command: $cmd"
			);
			wfDebugLog( __METHOD__,
				"Unable to convert to $outFile: Status: $status   Message:$msg  "
				. "Command: $cmd"
			);
		} else {
			wfDebugLog( __METHOD__, "Convertible: $cmd" );
		}
		return true;
	}
}
