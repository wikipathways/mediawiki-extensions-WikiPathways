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
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways;

class PathwaysPagerFactory {
	/**
	 * @param BrowsePathways $page object to use
	 * @return PathwaysPager
	 */
	public static function get( BrowsePathways $page ) {
		switch ( $page->view ) {
		case 'list':
			return new ListPathwaysPager( $page );
		  break;
		case 'single':
			return new SinglePathwaysPager( $page );
		  break;
		default:
			return new ThumbPathwaysPager( $page );
		}
	}
}
