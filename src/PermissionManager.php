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
 * Manages loading and writing of permissions for a page
 */
class PermissionManager {
	/**
	 * Array containing namespaces for which the permissions
	 * can be managed
	 */
	public static $permission_namespaces = [ NS_PATHWAY ];

	private $pageId;
	private $permissions;

	public function __construct( $pageId ) {
		$this->pageId = $pageId;
		$this->read();
		if ( $this->permissions && $this->permissions->isExpired() ) {
			// Remove the permissions, since they are expired
			$this->clearPermissions( true );
		}
	}

	private function write( $force = false ) {
		$tag = new MetaTag( self::$TAG, $this->pageId );
		if ( $force ) {
			$tag->setPermissions( [] );
		}
		if ( $this->permissions ) {
			$tag->setText( serialize( $this->permissions ) );
			$tag->save();
		} else {
			$tag->remove();
		}
	}

	private function read() {
		$tag = new MetaTag( self::$TAG, $this->pageId );
		if ( $tag->exists() ) {
			$this->permissions = unserialize( $tag->getText() );
		}
	}

	/**
	 * Get the permissions.
	 * @returns A PagePermissions object, or NULL if there are no permissions
	 * set (the page is public).
	 */
	public function getPermissions() {
		return $this->permissions;
	}

	/**
	 * Set the permissions. Old permissions will be overwritten.
	 * @param $permissions The PagePermissions object containing the
	 * permissions. Passing an empty value (NULL, false, '') will
	 * clear all permissions and make the page public
	 */
	public function setPermissions( $permissions ) {
		$this->permissions = $permissions;
		if ( $permissions && $permissions->isEmpty() ) {
			$this->permissions = '';
		}
		$this->write();
	}

	/**
	 * Clear all current permissions and make the page public.
	 * @param $force Force write to database, even if the currently logged
	 * in user doesn't have permission to edit the page.
	 */
	public function clearPermissions( $force = false ) {
		$this->permissions = '';
		$this->write( $force );
	}

	/**
	 * Hook function that checks for page restrictions for the given user.
	 */
	static function checkRestrictions( $title, $user, $action, &$result ) {
		// Only manage permissions on specified namespaces
		if ( $title->exists() && in_array( $title->getNamespace(), self::$permission_namespaces ) ) {
			// Check if the user is in a group that's not affected by page permissions
			if ( in_array( "sysop", $user->getGroups() ) ) { // TODO: the override group should be dynamic
				// Sysops can always manage
				if ( $action == self::$ACTION_MANAGE ) {
					$result = true;
					return false;
				}
			} else { // If not, check page permissions
				$mgr = new PermissionManager( $title->getArticleID() );
				$perm = $mgr->getPermissions();

				if ( $perm ) {
					$result = $perm->userCan( $action, $user );
					return false;
				} else {
					// Manage condition needs special rules
					if ( $action == self::$ACTION_MANAGE ) {
						// User can only make a page private when:
						// - the user is the only author
						// - the user can edit the page
						if ( MwUtils::isOnlyAuthor( $user->getId(), $title->getArticleID() ) ) {
							$result = $title->userCan( 'edit' );
						} else {
							$result = false;
						}
						return false;
					}
				}
			}
		}

		// Otherwise, use the default permissions
		$result = null;
		return true;
	}

	/**
	 * Reset the expiration date of the given permissions
	 * to $ppExpiresAfter days from now
	 */
	public static function resetExpires( &$perm ) {
		global $ppExpiresAfter;
		if ( !$ppExpiresAfter ) {
			$ppExpiresAfter = 31; // Expire after 31 days by default
		}
		$expires = mktime( 0, 0, 0, date( "m" ), date( "d" ) + $ppExpiresAfter, date( "Y" ) );
		$expires = wfTimestamp( TS_MW, $expires );
		$perm->setExpires( $expires );
		return $perm;
	}

	/**
	 * Hook function that adds a tab to manage permissions, if
	 * the user is allowed to.
	 */
	static function addPermissionTab( &$content_actions ) {
		global $wgTitle, $wgRequest;

		if ( $wgTitle->userCan( self::$ACTION_MANAGE ) ) {
			$action = $wgRequest->getText( 'action' );

			// Permissions already set, publish
			$content_actions[self::$ACTION_MANAGE] = [
				'text' => 'permissions',
				'href' => $wgTitle->getLocalUrl( 'action=' . self::$ACTION_MANAGE ),
				'class' => $action == self::$ACTION_MANAGE ? 'selected' : false
			];
		}
		return true;
	}

	/**
	 * Excecuted when the user presses the 'permissions' tab
	 */
	static function permissionTabAction( $action, $article ) {
		if ( $action == self::$ACTION_MANAGE ) {
			$pp = new SetPermissionsPage( $article );
			$pp->execute();
			return false;
		}
		return true;
	}

	public static $TAG = "page_permissions"; # The name of the meta tag used to store the data
	public static $ACTION_MANAGE = "manage_permissions";
}

class_alias( "WikiPathways\\PermissionManager", "PermissionManager" );
