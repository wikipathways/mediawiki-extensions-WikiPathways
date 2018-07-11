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
 */
namespace WikiPathways;

/**
 * Object that stores user permissions for a page
 */
class PagePermissions {
	private $pageId;
	private $permissions = []; # Array where key is action, value is array of users
	private $expires;

	public function __construct( $pageId ) {
		$this->pageId = $pageId;
	}

	public function getPermissions() {
		return $this->permissions;
	}

	public function getPageId() {
		return $this->pageId;
	}

	/**
	 * Find out if the user can perform the given
	 * action based on the permissions in this object
	 */
	public function userCan( $action, $user ) {
		if ( $user instanceof User ) {
			$user = $user->getId();
		}
		$p = $this->permissions[$action] ?? null;
		if ( $p ) {
			return (bool)$p[$user];
		}
		return false;
	}

	/**
	 * Permit the user to perform the given action
	 */
	public function setUserPermission( $user_id, $action ) {
		$this->permissions[$action][$user_id] = $user_id;
	}

	/**
	 * Permit the user to read/write this page
	 */
	public function addReadWrite( $user_id ) {
		$this->setUserPermission( $user_id, 'read' );
		$this->setUserPermission( $user_id, 'edit' );
	}

	public function getManageUsers() {
		return $this->permissions[PermissionManager::$ACTION_MANAGE];
	}

	/**
	 * Permit the user to manage the permissions of this page
	 */
	public function addManage( $user_id ) {
		$this->setUserPermission( $user_id, PermissionManager::$ACTION_MANAGE );
	}

	/**
	 * Remove all permissions for the given user
	 */
	public function clearPermissions( $user_id ) {
		foreach ( $this->permissions as &$a ) {
			unset( $a[$user_id] );
		}
	}

	/**
	 * Forbid the user to perform the given action
	 */
	public function removeUserPermission( $user_id, $action ) {
		unset( $this->permissions[$action][$user_id] );
	}

	/**
	 * Set the expiration date of the permissions.
	 * The permissions will be cleared automatically
	 * after the given date.
	 */
	public function setExpires( $timestamp ) {
		$this->expires = $timestamp;
	}

	public function getExpires() {
		return $this->expires;
	}

	/**
	 * Check if the permissions are expired
	 */
	public function isExpired() {
		return $this->expires && ( (float)$this->expires - (float)wfTimestamp( TS_MW ) ) <= 0;
	}

	/**
	 * Check if there are any permissions specified
	 * in this object.
	 * @return true if no permissions are specified
	 */
	public function isEmpty() {
		$empty = true;
		foreach ( $this->permissions as &$a ) {
			if ( count( $a ) > 0 ) {
				$empty = false;
				break;
			}
		}
		return $empty;
	}
}

class_alias( "WikiPathways\\PagePermissions", "PagePermissions" );
