<?php
/**
 * Featured pathway is a slight modification on PathwayOfTheDay, it
 * does get pathways from a limited collection, kept on the
 * FeaturedPathway wiki page
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
 * @author
 * @author Mark A. Hershberger <mah@nichework.com>
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class FeaturedPathway extends PathwayOfTheDay {

	/**
	 * Select a random pathway from the list on the wiki page
	 * FeaturedPathway
	 */
	protected function fetchRandomPathway() {
		wfDebug( "Fetching random pathway...\n" );
		$pathwayList = Pathway::parsePathwayListPage( $this->arg );
		if ( count( $pathwayList ) == 0 ) {
			throw new Exception( "{$this->arg} doesn't contain any valid pathway!" );
		}
		return $pathwayList[rand( 0, count( $pathwayList ) - 1 )]->getTitleObject()->getDbKey();
	}
}
