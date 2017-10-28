<?php
/**
 * Copyright (C) 2017  J. David Gladstone Institutes
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
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
 * @author Mark A. Hershberger
 */
namespace WikiPathways;

class Wish {
	private $id;
	private $title;
	private $article;
	private $firstRevision;
	private $voteArticle;
	private $votes;

	function __construct( $id ) {
		$this->id = $id;
		$this->title = Title::newFromID( $id );
		$this->article = new Article( $this->title );
		$this->voteArticle = new Article( $this->title->getTalkPage() );
	}

	static function createNewWish( $name, $comments ) {
		if ( !$name ) { throw new Exception( "Please fill in the pathway name" );
		}

		$title = Title::newFromText( $name, NS_WISHLIST );
		if ( !$title->userCan( 'create' ) ) {
			throw new Exception( "User can not create new request, are you logged in?" );
		}

		$wishArticle = new Article( $title );

		$succ = true;

		// Create the wish article, containing the comments
		$succ = $wishArticle->doEdit( $comments, "New wishlist item" );
		if ( !succ ) {
			throw new Exception( "Unable to create article $name" );
		}
		// Create the talk page, containing the votes in a hidden section
		$voteArticle = new Article( $title->getTalkPage() );
		$succ = $voteArticle->doEdit( "<!--VOTES\n-->", "New wishlist item" );
		if ( !succ ) {
			throw new Exception( "Unable to create article $name" );
		}

		$wishArticle->doWatch();

		return new Wish( $wishArticle->getID() );
	}

	function vote( $userId ) {
		if ( !$this->userCan( 'vote' ) ) {
			throw new Exception( "You have no permissions to vote" );
		}

		// Add the user id to the talk page
		$votes = $this->getVotes();
		if ( !in_array( $userId, $votes ) ) {
			$votes[] = $userId;
			$this->saveVotes( $votes );
		}
	}

	function unvote( $userId ) {
		if ( !$this->userCan( 'unvote' ) ) {
			throw new Exception( "You have no permissions to remove a vote" );
		}

		// Remove the user id from the talk page
		$votes = $this->getVotes();
		if ( in_array( $userId, $votes ) ) {
			$votes = array_diff( $votes, [ $userId ] );
			$this->saveVotes( $votes );
		}
	}

	private function saveVotes( $votes ) {
		// Save the votes to the talk page
		$voteText = implode( "\n", $votes );
		$voteText = "<!--VOTES\n{$voteText}\n-->";
		$succ = $this->voteArticle->doEdit( $voteText, "Added user vote" );
		if ( !succ ) {
			throw new Exception( "Unable to update votes for $name" );
		}
		$this->votes = ''; // clear vote cache
	}

	function countVotes() {
		return count( $this->getVotes() );
	}

	function getVotes() {
		if ( !$this->votes ) {
			$content = $this->voteArticle->getContent();
			// Find the <!--VOTES\s(.*)\s--> part
			$match = preg_match( '/<!--VOTES\s(.*)\s-->/s', $content, $matches );
			if ( $match ) {
				$votes = $matches[1];
			} else {
				$votes = "";
			}
			$this->votes = $votes ? explode( "\n", $votes ) : [];
		}
		return $this->votes;
	}

	function exists() {
		return $this->title->exists();
	}

	function getId() {
		return $this->id;
	}

	function getTitle() {
		return $this->title;
	}

	function getComments() {
		return $this->article->getContent();
	}

	function userIsWatching() {
		return $this->title->userIsWatching();
	}

	function watch() {
		$this->article->doWatch();
	}

	function unwatch() {
		$this->article->doUnwatch();
	}

	function remove() {
		if ( !$this->userCan( 'delete' ) ) {
			throw new Exception( "You have no permissions to delete the item" );
		}
		Pathway::deleteArticle( $this->title, "Removed wishlist item" );
	}

	function isResolved() {
		return $this->article->isRedirect();
	}

	function getResolvedPathway() {
		if ( !$this->isResolved() ) {
			return false;
		}
		$title = Title::newFromRedirect( $this->article->getContent() );
		return Pathway::newFromTitle( $title );
	}

	function markResolved( $pathway ) {
		// #REDIRECT [[pagename]]
		if ( !$this->userCan( 'resolve' ) ) {
			throw new Exception( "You have no permissions to resolve this item" );
		}

		$this->article->doEdit( "#REDIRECT [[{$pathway->getTitleObject()->getFullText()}]]",
					"Resolved wishlist item {$this->getTitle()->getText()}" );
	}

	private function getFirstRevision() {
		if ( !$this->firstRevision ) {
			$revs = Revision::fetchAllRevisions( $this->getTitle() );
			if ( $revs->numRows() > 0 ) {
				$revs->seek( $revs->numRows() - 1 );
			} else {
				return;
			}
			$row = $revs->fetchRow();
			$this->firstRevision = Revision::newFromId( $row['rev_id'] );
		}
		return $this->firstRevision;
	}

	function getRequestDate() {
		return $this->getFirstRevision()->getTimestamp();
	}

	function getResolvedDate() {
		if ( $this->isResolved() ) {
			return $this->article->getTimestamp();
		}
	}

	function getRequestUser() {
		$rev = $this->getFirstRevision();
		return User::newFromId( $rev->getUser() );
	}

	function userCan( $action ) {
		global $wgUser;
		$uid = $wgUser->getId();

		switch ( $action ) {
			case 'resolve':
				return $this->userCan( 'edit' );
			case 'vote':
				return $this->userCan( 'edit' ) &&
				!in_array( $uid, $this->getVotes() ) && // Not allowed when already voted
				$uid != $this->getRequestUser()->getId(); // Don't vote on own request
			case 'unvote':
				return $this->userCan( 'edit' ) && in_array( $uid, $this->getVotes() );
			default:
				return $this->title->userCan( $action ) && $wgUser->isAllowed( $action );
		}
	}
}
