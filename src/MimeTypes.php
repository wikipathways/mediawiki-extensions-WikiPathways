<?php
/**
 * Static class that keeps track of mime-types for
 * file extensions. Allows you to register a custom
 * extension
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
 */
namespace WikiPathways;

class MimeTypes {
	private static $types = [
		FILETYPE_SVG => "image/svg+xml",
		FILETYPE_IMG => "image/svg+xml",
		FILETYPE_GPML => "text/xml",
		FILETYPE_PNG => "image/png",
		"pdf" => "application/pdf",
		"pwf" => "text/plain",
		"owl" => "application/vnd.biopax.rdf+xml"
	];

	/**
	 * Register a new mime type
	 *
	 * @param string $extension for the file
	 * @param string $mime type for the mimetype
	 */
	public static function registerMimeType( $extension, $mime ) {
		self::$types[$extension] = $mime;
	}

	/**
	 * Given an extension, return the mime type
	 *
	 * @param string $extension for the file
	 * @return string type for the mimetype
	 */
	public static function getMimeType( $extension ) {
		return isset( self::$types[$extension] ) ? self::$types[$extension] : null;
	}
}
