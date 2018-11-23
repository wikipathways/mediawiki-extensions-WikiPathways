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
 * @author Thomas Kelder <thomaskelder@gmail.com>
 * @author Mark A, Hershberger <mah@nichework.com>
 */
namespace WikiPathways;

/**
 * Set of utility functions to handle MediaWiki data.
 */
class Util {
	/**
	 * Find out if the given user is the only author of the page
	 * @param $user The user or user id
	 * @param $pageId The article id
	 */
	public static function isOnlyAuthor( $user, $pageId ) {
		$userId = $user;
		if ( $user instanceof User ) {
			$userId = $user->getId();
		}
		foreach ( self::getAuthors( $pageId ) as $author ) {
			if ( $userId != $author ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get all authors for a page
	 * @param int $pageId The article id
	 * @return array with the user ids of the authors
	 */
	public static function getAuthors( $pageId ) {
		$users = [];
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( "revision", "DISTINCT(rev_user) as user", [ "rev_page" => $pageId ] );
		foreach ( $res as $row ) {
			$users[] = $row->user;
		}
		return $users;
	}

	/**
	 * Get the timestamp of the latest edit
	 * @param int $inNS Only for this namespace
	 */
	public static function getLatestTimestamp( $inNS = null ) {
		$revision = Revision::newFromId( self::getLatestRevision( $inNS ) );
		return $revision->getTimestamp();
	}

	/**
	 * Get the latest revision for all pages.
	 * @param int $inNS Only include pages for the given namespace
	 */
	public static function getLatestRevision( $inNS = null ) {
		$dbr = wfGetDB( DB_SLAVE );
		$namespace = [];
		if ( $inNS !== null ) {
			$namespace['page_namespace'] = $inNS;
		}
		$res = $dbr->select( "page", "MAX(page_latest) as latest", $namespace );
		$row = $dbr->fetchObject( $res );
		$rev = $row->latest;
		$dbr->freeResult( $res );
		return $rev;
	}
}
