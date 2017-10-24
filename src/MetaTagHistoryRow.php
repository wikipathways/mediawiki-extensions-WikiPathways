<?php
/**
 * Represent a row in the tag history table.
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
class MetaTagHistoryRow {
	private $tag_name;
	private $page_id;
	private $action;
	private $user;
	private $time;
	private $text;

	function __construct( $dbRow ) {
		$this->tag_name = $dbRow->tag_name;
		$this->page_id = $dbRow->page_id;
		$this->action = $dbRow->action;
		$this->user = $dbRow->action_user;
		$this->time = $dbRow->time;
		$this->text = $dbRow->text;
	}

	/**
	 * Get the action that was performed on the tag
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * Get the id of the user that performed the action
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * Get the time the action was performed
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * Get the tag name
	 */
	public function getTagName() {
		return $this->tag_name;
	}

	/**
	 * Get the page id the tag applies to
	 */
	public function getPageId() {
		return $this->page_id;
	}

	/**
	 * Get the contents of the tag at time of
	 * this history item
	 */
	public function getText() {
		return $this->text;
	}
}
