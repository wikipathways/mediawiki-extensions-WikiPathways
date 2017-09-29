<?php
/**
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
namespace WikiPathways;

use MWException;
use Title;

class TaggedPathway extends PathwayOfTheDay {
	private $tag;

	/**
	 * Ye ole constructor
	 *
	 * @param string $id pathway id
	 * @param int $date seconds since epoch
	 * @param string $tag the tag
	 */
	function __construct( $id, $date, $tag ) {
		$this->tag = $tag;
		parent::__construct( $id, $date );
	}

	/**
	 * Select a random pathway from all pathways with the given tag
	 *
	 * @return a database row
	 */
	protected function fetchRandomPathway() {
		wfDebug( "Fetching random pathway...\n" );
		$pages = MetaTag::getPagesForTag( $this->tag );
		if ( count( $pages ) == 0 ) {
			throw new MWException( "There are no pathways tagged with '{$this->tag}'!" );
		}
		$pathways = [];
		foreach ( $pages as $p ) {
			$title = Title::newFromId( $p );
			if ( $title->getNamespace() == NS_PATHWAY && !$title->isRedirect() ) {
				$pathway = Pathway::newFromTitle( $title );
				if ( !$pathway->isDeleted() ) {
					$pathways[] = $pathway;
				}
			}
		}
		return $pathways[rand( 0, count( $pathways ) - 1 )]->getTitleObject()->getDbKey();
	}
}
