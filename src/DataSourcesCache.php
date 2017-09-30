<?php
/**
 * Manages downloading of the bridgedb datasources file
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

use Exception;

class DataSourcesCache {
	private static $url = "http://svn.bigcat.unimaas.nl/bridgedb/trunk/org.bridgedb.bio/resources/org/bridgedb/bio/datasources.txt";
	static $file = "datasources.txt";
	static $content = null;

	public static function update() {
		## Download a fresh datasources file
		$txt = file_get_contents(self::$url);
		if($txt) { //Only update if file could be downloaded
			$f = WPI_CACHE_PATH . "/" . self::$file;
			$fh = fopen($f, 'w');
			if( $fh !== false ) {
				fwrite($fh, $txt);
				fclose($fh);
				chmod($f, 0666);
			} else {
				throw new Exception( "Could't open $f for writing!" );
			}
			self::$content = $txt;
		}
	}

	private static function read() {
		$f = WPI_CACHE_PATH . "/" . self::$file;
		if(file_exists($f)) {
			return file_get_contents($f);
		}
	}

	public static function getContent() {
		if(!self::$content) {
			//Try to read from cached file
			$txt = self::read();
			if(!$txt) { //If no cache exists, update it
				self::update();
			} else {
				self::$content = $txt;
			}
		}
		return self::$content;
	}
}
?>
