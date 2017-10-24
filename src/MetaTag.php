<?php
/**
 * MetaTag API
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
 *
 * This class represents a single metatag, providing support
 * for reading and writing tags. This class also takes care of
 * updating the tag history.
 */
namespace WikiPathways;

use MWException;
use Title;

class MetaTag {
	public static $TAG_HISTORY_TABLE = "tag_history";
	public static $TAG_TABLE = "tag";

	public static $ACTION_UPDATE = "update";
	public static $ACTION_REMOVE = "remove";
	public static $ACTION_CREATE = "create";

	private $exists = false;

	private $name;
	private $text;
	private $page_id;
	private $revision;
	private $user_add;
	private $user_mod;
	private $time_add;
	private $time_mod;

	private $storeHistory = true;
	private $permissions = [ 'edit' ];

	/**
	 * Create a new metatag object
	 * @param string $name The tag name
	 * @param string $page_id The id of the page that will be tagged
	 */
	public function __construct( $name, $page_id ) {
		if ( !$name ) {
			throw new MetaTagException( $this, "Name can't be empty" );
		}
		if ( !$page_id ) {
			throw new MetaTagException( $this, "Page id can't be empty" );
		}

		$this->name = $name;
		$this->page_id = $page_id;
		$this->loadFromDB();
	}

	public function __toString() {
		$t = $this->getText();
		if ( is_string( $t ) ) {
			return $t;
		} else {
			return "";
		}
	}

	/**
	 * Specify whether a history should be stored when modifying this tag. Set to false to disable
	 * storing history.
	 * By default, a history record is stored upon saving and removing, but this can
	 * be disabled for better performance if a tag history is not needed
	 * (e.g. if the tag is only used for caching data).
	 *
	 * @param bool $history to store or not
	 */
	public function setUseHistory( $history ) {
		$this->storeHistory = $history;
	}

	/**
	 * Set the permissions to check before saving the tag.
	 * Default permission that is checked is 'edit'.
	 * @param mixed $actions The action (e.g. 'edit', or 'delete') or an array
	 *     of actions that the current user must have permission for
	 *     to write the tag.
	 */
	public function setPermissions( $actions ) {
		if ( is_array( $actions ) ) {
			$this->permissions = $actions;
		} else {
			$this->permissions = [ $actions ];
		}
	}

	/**
	 * Return matching tags
	 *
	 * @param string $tag_name to get
	 * @return array
	 */
	public static function getTags( $tag_name ) {
		$tags = [];

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			self::$TAG_TABLE,
			[ 'page_id' ],
			[ 'tag_name' => $tag_name ]
		);
		$row = $dbr->fetchObject( $res );
		if ( $row ) {
			do {
				$tags[] = new MetaTag( $tag_name, $row->page_id );
				$row = $dbr->fetchObject( $res );
			} while ( $row );
		}

		$dbr->freeResult( $res );
		return $tags;
	}

	/**
	 * Get all tags for the given page
	 * @param string $page_id The page id
	 * @return An array of MetaTag objects
	 */
	public static function getTagsForPage( $page_id ) {
		$tags = [];

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			self::$TAG_TABLE,
			[ 'tag_name' ],
			[ 'page_id' => $page_id ]
		);
		$row = $dbr->fetchObject( $res );
		if ( $row ) {
			do {
				$tags[] = new MetaTag( $row->tag_name, $page_id );
			} while ( $row );
		}

		$dbr->freeResult( $res );
		return $tags;
	}

	/**
	 * Get all pages that have the given tag.
	 * @param string $name The tag name
	 * @param string $text The tag text (optional)
	 * @param bool $case If true, use case sensitive search for tag text (default is true)
	 * @return An array with page ids
	 */
	public static function getPagesForTag( $name, $text = false, $case = true ) {
		$pages = [];

		$dbr = wfGetDB( DB_SLAVE );

		$name = $dbr->addQuotes( $name );

		$where = [ 'tag_name' => $name ];
		if ( $text !== false ) {
			$text_field = "tag_text";
			if ( !$case ) {
				$text = strtolower( $text );
				$text_field = "LOWER($text_field)";
			}
			$text = $dbr->addQuotes( $text );
			$text = " AND $text_field = $text ";
		}

		$query =
			"SELECT page_id FROM " . self::$TAG_TABLE .
			" WHERE tag_name = $name " .
			$text;

		wfDebug( __METHOD__ . ": $query\n" );
		$res = $dbr->query( $query );
		$row = $dbr->fetchObject( $res );
		if ( $row ) {
			do {
				$title = Title::newFromId( $row->page_id );
				if ( !$title || $title->isRedirect() || $title->isDeleted() ) {
					// Skip redirects and deleted
					continue;
				}
				$pages[] = $row->page_id;
				$row = $dbr->fetchObject( $res );
			} while ( $row );
		}

		$dbr->freeResult( $res );
		return $pages;
	}

	/**
	 * Attempts to load the tag information
	 * from the database if the tag exists
	 */
	private function loadFromDB() {
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			self::$TAG_TABLE,
			[ 'tag_text', 'revision', 'user_add', 'user_mod', 'time_add', 'time_mod' ],
			[ 'tag_name' => $this->name, 'page_id' => $this->page_id ]
		);
		$row = $dbr->fetchObject( $res );
		if ( $row ) {
			$this->exists = true;
			$this->text = $row->tag_text;
			$this->revision = $row->revision;
			$this->user_add = $row->user_add;
			$this->user_mod = $row->user_mod;
			$this->time_add = $row->time_add;
			$this->time_mod = $row->time_mod;
		}
		$dbr->freeResult( $res );
	}

	/**
	 * Write the tag information to the database. Existing tag with
	 * the same name/page_id will be overwritten. This method also checks if the
	 * current user ($wgUser) is allowed to write the tag (based on edit permissions
	 * of the page that will be tagged.
	 */
	public function save() {
		if ( $this->canWrite() ) {
			$this->doWriteToDB();
		} else {
			throw new MetaTagException( $this, "User not permitted to tag page" );
		}
	}

	/**
	 * Remove the tag from the database.
	 */
	public function remove() {
		if ( $this->canWrite() ) {
			$this->doRemove();
		} else {
			throw new MetaTagException( $this, "User not permitted to tag page" );
		}
	}

	private function canWrite() {
		// Check valid page and user permissions
		$title = Title::newFromID( $this->page_id );
		if ( $title ) {
			$can = true;
			foreach ( $this->permissions as $action ) {
				$can = $can && $title->userCan( $action );
				if ( !$can ) {
					// Stop checking once one action returns false
					break;
				}
			}
			return $can;
		} else {
			throw new MetaTagException( $this, "Unable to create title object" );
		}
	}

	private function doRemove() {
		$dbw =& wfGetDB( DB_MASTER );
		$dbw->immediateBegin();

		if ( $this->exists ) {
			$this->updateTimeStamps();
			$this->updateUsers();

			$dbw->delete(
				self::$TAG_TABLE,
				[ 'tag_name' => $this->name, 'page_id' => $this->page_id ]
			);

			$this->writeHistory( self::$ACTION_REMOVE );
		}

		$dbw->immediateCommit();
		$this->exists = false;
	}

	private function doWriteToDB() {
		$dbw =& wfGetDB( DB_MASTER );
		$dbw->immediateBegin();

		$this->updateTimeStamps();
		$this->updateUsers();

		$values = [
			'tag_text' => $this->text,
			'revision' => $this->revision,
			'user_mod' => $this->user_mod,
			'time_mod' => $this->time_mod
		];

		if ( $this->exists ) {
			$dbw->update(
				self::$TAG_TABLE,
				$values,
				[ 'tag_name' => $this->name, 'page_id' => $this->page_id ]
			);

			$dbw->immediateCommit();

			$this->writeHistory( self::$ACTION_UPDATE );
		} else {
			$values['tag_name'] = $this->name;
			$values['page_id'] = $this->page_id;
			$values['time_add'] = $this->time_add;
			$values['user_add'] = $this->user_add;
			$dbw->insert(
				self::$TAG_TABLE,
				$values
			);

			$this->exists = true;
			$dbw->immediateCommit();

			$this->writeHistory( self::$ACTION_CREATE );
		}
	}

	private function writeHistory( $action ) {
		if ( !$this->storeHistory ) {
			return;
		}

		$dbw =& wfGetDB( DB_MASTER );
		$dbw->immediateBegin();

		$dbw->insert(
			self::$TAG_HISTORY_TABLE,
			[
				'tag_name' => $this->name,
				'page_id' => $this->page_id,
				'action' => $action,
				'action_user' => $this->user_mod,
				'time' => $this->time_mod,
				'text' => $this->text
			]
		);

		$dbw->immediateCommit();
	}

	private function updateUsers() {
		global $wgUser;
		if ( $wgUser ) {
			$this->user_mod = $wgUser->getID();
			if ( !$this->exists ) {
				$this->user_add = $this->user_mod;
			}
		}
	}

	private function updateTimestamps() {
		$this->time_mod = wfTimestamp( TS_MW );
		if ( !$this->exists ) {
			$this->time_add = $this->time_mod;
		}
	}

	/**
	 * Check whether this tag already exists in the
	 * database
	 *
	 * @return bool
	 */
	public function exists() {
		return $this->exists;
	}

	/**
	 * Get the contents of the tag
	 *
	 * @return string
	 */
	public function getText() {
		return $this->text;
	}

	/**
	 * Set the contents of the tag
	 *
	 * @return string
	 */
	public function setText( $text ) {
		$this->text = $text;
	}

	/**
	 * Get the tag name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get the id of the page this tag applies to
	 *
	 * @return string
	 */
	public function getPageId() {
		return $this->page_id;
	}

	/**
	 * Get the page revision this tag applies to
	 *
	 * @return int
	 */
	public function getPageRevision() {
		return $this->revision;
	}

	/**
	 * Set the page revision this tag applies to
	 *
	 * @param int $revision revision #
	 */
	public function setPageRevision( $revision ) {
		$this->revision = $revision;
	}

	/**
	 * Get the id of the user that added this tag
	 *
	 * @return int
	 */
	public function getUserAdd() {
		return $this->user_add;
	}

	/**
	 * Get the id of the user that last modified this tag
	 *
	 * @return int
	 */
	public function getUserMod() {
		return $this->user_mod;
	}

	/**
	 * Get the timestamp of the tag creation
	 *
	 * @return timestamp
	 */
	public function getTimeAdd() {
		return $this->time_add;
	}

	/**
	 * Get the timestamp of the last tag modification
	 *
	 * @return timestamp
	 */
	public function getTimeMod() {
		return $this->time_mod;
	}

	/**
	 * Get the tag history, starting at the given time
	 *
	 * @param string $fromTime A timestamp in the TS_MW format
	 * @return array of MetaTagHistoryRow objects
	 */
	public function getHistory( $fromTime = '0' ) {
		return self::queryHistory( $this->page_id, $this->name, $fromTime );
	}

	/**
	 * Get the entire history for a single page.
	 *
	 * @param string $pageId page id
	 * @param string $fromTime in TS_MW format
	 * @return array of MetaTagHistoryRow objects
	 */
	public static function getHistoryForPage( $pageId, $fromTime = '0' ) {
		return self::queryHistory( $pageId, '', $fromTime );
	}

	/**
	 * Get the entire history for a single tag.
	 *
	 * @param string $tagName name of the tag
	 * @param string $fromTime in TS_MW format
	 * @return array of MetaTagHistoryRow objects
	 */
	public static function getAllHistory( $tagName = '', $fromTime = '0' ) {
		return self::queryHistory( '', $tagName, $fromTime );
	}

	/**
	 * Utility function for querying the db.
	 *
	 * @param string $pageId to restrict to if non blank
	 * @param string $tagName name of the tag to restrict to if not blank
	 * @param string $fromTime in TS_MW format, '0' for beginning of time
	 * @return array of MetaTagHistoryRow objects
	 */
	private static function queryHistory( $pageId, $tagName, $fromTime = '0' ) {
		$nameWhere = '';
		if ( $tagName ) {
			$nameWhere = "'{$tagName}' AND";
		}

		$pageWhere = '';
		if ( $pageId ) {
			$pageWhere = "page_id = $pageId AND";
		}

		$tagWhere = '';
		if ( $tagName ) {
			$tagWhere = "tag_name = '$tagName' AND";
		}

		$dbr = wfGetDB( DB_SLAVE );
		$tbl = self::$TAG_HISTORY_TABLE;
		$query = "SELECT * FROM $tbl WHERE " .
			"$nameWhere $pageWhere $tagWhere " .
			" time >= $fromTime ORDER BY time DESC";
		$res = $dbr->query( $query );
		$history = [];
		$row = $dbr->fetchObject( $res );
		if ( $row ) {
			do {
				$history[] = new MetaTagHistoryRow( $row );
				$row = $dbr->fetchObject( $res );
			} while ( $row );
		}
		$dbr->freeResult( $res );
		return $history;
	}
}
