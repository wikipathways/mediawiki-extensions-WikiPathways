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

class Factory {

	private static $prefix = "WikiPathways\\PathwayCache\\";
	private static $converter;

	/**
	 * @param string $type of cached pathway
	 * @param Pathway $pathway object
	 * @return WikiPathways\PathwayCache\Base;
	 */
	public static function getCache( $type, Pathway $pathway ) {
		$class = self::$prefix . strtoupper( $type );
		if ( !class_exists( $class ) ) {
			throw new MWException( "No Cache object for $type!" );
		}
		$pathId = $pathway->getID();
		if ( !isset( self::$converter[$pathId] ) ) {
			self::$converter[$pathId] = new Converter( $pathId );
		}
		return new $class( $pathway, self::$converter[$pathId],  $type );
	}
}
