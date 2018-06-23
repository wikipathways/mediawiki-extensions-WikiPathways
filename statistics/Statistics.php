<?php

/**
 * Implements functions to extract the statistics.
 *
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
 * @author Thomas Kelder <thomaskelder@gmail.com>
 * @author Alexander Pico <apico@gladstone.ucsf.edu>
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways\Statistics;

class Statistics {
	static private $excludeTags = [
		"Curation:Tutorial"
	];

	/**
	 * @param resource $fout handle to write to
	 * @param array $counts of frequency counts
	 * @param bool $includeKey whenter to include the key in the output
	 */
	public static function writeFrequencies( $fout, array $counts, $includeKey = false ) {
		arsort( $counts );
		$i = 0;
		foreach ( array_keys( $counts ) as $u ) {
			$row = [ $i, $counts[$u] ];
			if ( $includeKey ) {
				array_unshift( $row, $u );
			}
			fwrite( $fout, implode( "\t", $row ) . "\n" );
			$i += 1;
		}
	}

	/**
	 * Get page ids to exclude based on a test/tutorial curation tag.
	 * @return array of of pages to exclude
	 */
	public static function getExcludeByTag() {
		$exclude = [];
		foreach ( self::$excludeTags as $tag ) {
			$exclude = array_merge( $exclude, CurationTag::getPagesForTag( $tag ) );
		}
		return $exclude;
	}

	/**
	 * Get an array of timestamps, one for each month from $tsStart
	 * to $tsEnd. Timestamps are in MW format.
	 * @param int $tsStart
	 * @param int $tsEnd
	 * @return string[] dates
	 */
	public static function getTimeStampPerMonth( $tsStart, $tsEnd ) {
		$startD = (int)substr( $tsStart, 6, 2 );
		$startM = (int)substr( $tsStart, 4, 2 );
		$startY = (int)substr( $tsStart, 0, 4 );
		$ts = [];
		$tsCurr = $tsStart;
		$monthIncr = 0;
		while ( $tsCurr <= $tsEnd ) {
			$ts[] = $tsCurr;
			$monthIncr += 1;
			$tsCurr = date(
				'YmdHis', mktime( 0, 0, 0, $startM + $monthIncr, $startD, $startY )
			);
		}
		$nm = count( $ts );
		logger( "Monthly interval from $tsStart to $tsEnd: $nm months." );
		return $ts;
	}
}
