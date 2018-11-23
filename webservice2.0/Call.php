<?php
/**
 * Copyright (C) 2015-2018  J. David Gladstone Institutes
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
 * @author tina
 * @author nuno
 * @author anders
 * @author Mark A. Hershberger
 */

namespace WikiPathways\WebService;

use Exception;
use Title;
use User;
use WikiPathways\PathwayIndex;
use WikiPathways\OntologyFunctions;
use WikiPathways\Xref;

class Call {
	/**
	 * Get a list of all available organisms.
	 * @return array of string $organisms Array with the names of all
	 * supported organisms
	 **/
	public static function listOrganisms() {
		return [ "organisms" => \WikiPathways\Pathway::getAvailableSpecies() ];
	}

	/**
	 * Get a list of all available pathways.
	 * @param string $organism The organism to filter by (optional)
	 * @return array of object PathwayInfo $pathways Array of pathway
	 * info objects
	 **/
	public static function listPathways( $organism=false ) {
		try {
			$pathways = \WikiPathways\Pathway::getAllPathways( $organism );
			$objects = [];
			foreach ( $pathways as $p ) {
				$objects[] = new PathwayInfo( $p );
			}
			return [ "pathways" => $objects ];
		} catch ( Exception $e ) {
			wfDebug( "ERROR: $e" );
			throw new Fault( "Receiver", $e );
		}
	}

	/**
	 * Get the GPML code for a pathway
	 * @param string $pwId The pathway identifier
	 * @param int $revision The revision number of the pathway (use 0
	 * for most recent)
	 * @return object Pathway $pathway The pathway
	 **/
	public static function getPathway( $pwId, $revision = 0 ) {
		try {
			$pathway = new \WikiPathways\Pathway( $pwId );
			if ( $revision ) {
				$pathway->setActiveRevision( $revision );
			}
			$pwi = new Pathway( $pathway );
			if (
				!isset( $_REQUEST["format"] )
				|| $_REQUEST["format"] === "XML"
			) {
				$pwi->gpml = base64_encode( $pwi->gpml );
			}
			// return array("pathway" => base64_encode($pwi));
			return [ "pathway" => $pwi ];
		} catch ( Exception $e ) {
			wfDebug( "ERROR: $e" );
			throw new Fault( "Receiver", $e );
		}
	}

	/**
	 * Get some general info about the pathway, such as the name, species
	 * and latest revision
	 * @param string $pwId The pathway identifier
	 * @return object PathwayInfo $pathwayInfo The pathway info
	 **/
	public static function getPathwayInfo( $pwId ) {
		try {
			$pathway = new \WikiPathways\Pathway( $pwId );
			$pwi = new PathwayInfo( $pathway );
			return [ "pathwayInfo" => $pwi ];
		} catch ( Exception $e ) {
			wfDebug( __METHOD__ . " (ERROR): $e\n" );
			throw new Fault( "Receiver", $e );
		}
	}

	/**
	 * Get the revision history of the pathway.
	 * @param string $pwId The pathway identifier
	 * @param string $timestamp Limit by time, only history items
	 * after the given time will be included.
	 * @return object PathwayHistory $history The pathway history
	 **/
	public static function getPathwayHistory( $pwId, $timestamp ) {
		try {
			$pathway = new \WikiPathways\Pathway( $pwId );
			$id = $pathway->getTitleObject()->getArticleId();
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				"revision",
				[ "rev_id", "rev_user_text", "rev_timestamp", "rev_comment" ],
				[
					'rev_page' => $id,
					'rev_timestamp >= ' . $dbr->addQuotes( $timestamp )
				]
			);

			$hist = new PathwayHistory( $pathway );

			while ( $row = $dbr->fetchObject( $res ) ) {
				$hr = new HistoryRow();
				$hr->revision = $row->rev_id;
				$hr->comment = $row->rev_comment;
				$hr->user = $row->rev_user_text;
				$hr->timestamp = $row->rev_timestamp;
				$hist->history[] = $hr;
			}

			$dbr->freeResult( $res );

			return [ 'history' => $hist ];
		} catch ( Exception $e ) {
			wfDebug( __METHOD__ . " (ERROR): $e\n" );
			throw new Fault( "Receiver", $e );
		}
	}

	/**
	 * Update the GPML code of a pathway on the wiki
	 * @param string $pwId The pathway identifier
	 * @param string $description A description of the modifications
	 * @param string $gpml The updated GPML code
	 * @param int $revision The revision the GPML code is based on
	 * @param string $auth The authentication key
	 * @param string $username The username
	 * @return bool $success Whether the update was successful
	 **/
	public static function updatePathway( $pwId, $description, $gpml, $revision, $auth, $username ) {
		global $wgUser;

		$gpml = self::decodeGpml( $gpml ); // checks if its base64

		global $wpiPathwayUpdateLog;
		$file = fopen( $wpiPathwayUpdateLog, 'a' );
		$resp = -1;

		try {
			if ( $auth ) {
				// Authenticate first, if token is provided
				authenticate( $username, $auth, true );
				fwrite( $file, "authenticate\n" );
			}
			$pathway = new \WikiPathways\Pathway( $pwId );
			// Only update if the given revision is the newest
			// Or if this is a new pathway

			if (
				!$pathway->exists()
				|| $revision == $pathway->getLatestRevision()
			) {
				fwrite( $file, "update pathway\n" );
				$pathway->updatePathway( $gpml, $description );
				$resp = $pathway->getLatestRevision();
			} else {
				fwrite( $file, "error - revision out of date\n" );
				throw new Fault(
					"Sender", "Revision out of date: your GPML code originates "
					. "from an old revision. This means somebody else modified "
					. "the pathway since you downloaded it. Please apply your "
					. "changes on the newest version"
				);
			}
		} catch ( Exception $e ) {
			fwrite( $file, "exception - " . $e . "\n" );
			if ( $e instanceof Fault ) {
				// throw $e;
				return [ $e->getMessage() ];
			} else {
				return [ $e->getMessage() ];
				// throw new Fault("Receiver", $e);
				wfDebug( "ERROR: $e" );
			}
		}
		fwrite( $file, "ending\n" );
		fclose( $file );
		return [ "success" => $resp ];
	}

	/**
	 * Cteate a new pathway on the wiki
	 * @param string $gpml The GPML code for the new pathway
	 * @param string $auth The authentication info
	 * @param string $username The user name
	 * @return object PathwayInfo $pathwayInfo The pathway info of the
	 * created pathway
	 **/
	public static function createPathway( $gpml, $auth, $username ) {
		$gpml = self::decodeGpml( $gpml );

		try {
			// Authenticate first, if token is provided
			if ( $auth ) {
				authenticate( $username, $auth, true );
			}

			$pathway = \WikiPathways\Pathway::createNewPathway(
				$gpml, "New pathway"
			);
			return [ "pathwayInfo" => new PathwayInfo( $pathway ) ];
		} catch ( Exception $e ) {
			throw new Fault( "Receiver", $e );
			wfDebug( "FAULT: $e" );
		}
	}

	/**
	 * Start a logged in session, using an existing WikiPathways account.
	 * This function will return an authentication code that can be used
	 * to excecute methods that need authentication (e.g. updatePathway)
	 * @param string $name The username
	 * @param string $pass The password
	 * @return string $auth The authentication code
	 **/
	public static function login( $name, $pass ) {
		global $wgUser, $wgAuth;

		if ( $wgUser->isLoggedIn() ) {
			return [ "auth" => $wgUser->getToken() ];
		}

		$user = User::newFromName( $name );
		if ( is_null( $user ) || $user->getID() == 0 ) {
			// throw new Exception("Invalid user name");
			throw new Fault( "Sender", "Invalid user name" );
		}
		$user->load();
		if ( $user->checkPassword( $pass ) ) {
			$wgAuth->updateUser( $user );
			$wgUser = $user;
			return [ "auth" => $user->getToken() ];
		} else {
			// throw new Exception("Wrong password");
			throw new Fault( "Sender", "Wrong password" );
		}
	}

	public static function loginold( $name, $pass ) {
		global $wgUser, $wgAuth;

		$user = User::newFromName( $name );
		if ( is_null( $user ) || $user->getID() == 0 ) {
			// throw new Exception("Invalid user name");
			throw new Fault( "Sender", "Invalid user name" );
		}
		$user->load();
		if ( $user->checkPassword( $pass ) ) {
			$wgAuth->updateUser( $user );
			$wgUser = $user;
			return [ "auth" => $user->getToken() ];
		} else {
			// throw new Exception("Wrong password");
			throw new Fault( "Sender", "Wrong password" );
		}
	}

	/**
	 * Download a pathway in the specified file format.
	 * @param string $fileType The file type to convert to, e.g.
	 * 'svg', 'png' or 'txt'
	 * @param string $pwId The pathway identifier
	 * @param int $revision The revision number of the pathway (use 0
	 * for most recent)
	 * @return base64Binary $data The converted file data (base64 encoded)
	 **/
	public static function getPathwayAs( $fileType, $pwId, $revision = 0 ) {
		try {
			$p = new \WikiPathways\Pathway( $pwId );
			$p->setActiveRevision( $revision );
			$data = file_get_contents( $p->getFileLocation( $fileType ) );
		} catch ( Exception $e ) {
			throw new Fault( "Receiver", "Unable to get pathway: " . $e );
		}

		if (
			!isset( $_REQUEST["format"] )
			|| strtolower( $_REQUEST["format"] ) === "xml"
			|| $_REQUEST["format"] === "json"
		) {
			return [ "data" => base64_encode( $data ) ];
		} else {
			return [ "data" => $data ];
		}
	}

	/**
	 * Get the recently changed pathways. Note: the recent changes table
	 * only retains items for a limited time, so it's not guaranteed
	 * that you will get all changes since the given timestamp.
	 * @param string $timestamp Get the changes after this time
	 * @return array of object PathwayInfo $pathways A list of the
	 * changed pathways
	 **/
	public static function getRecentChanges( $timestamp ) {
		// check safety of $timestamp, must be exactly 14 digits and
		// nothing else.
		if ( !preg_match( "/^\d{14}$/", $timestamp ) ) {
			throw new Fault(
				"Sender", "Invalid timestamp " . htmlentities( $timestamp )
			);
		}

		$dbr = wfGetDB( DB_SLAVE );
		$forceclause = $dbr->useIndexClause( "rc_timestamp" );
		$recentchanges = $dbr->tableName( 'recentchanges' );

		$sql = "SELECT
				rc_namespace,
				rc_title,
				MAX(rc_timestamp)
			FROM $recentchanges $forceclause
			WHERE
				rc_namespace = " . NS_PATHWAY . "
				AND
				rc_timestamp > '$timestamp'
			GROUP BY rc_title
			ORDER BY rc_timestamp DESC
		";

		// ~ wfDebug ("SQL: $sql");

		$res = $dbr->query( $sql, "getRecentChanges" );

		$objects = [];
		while ( $row = $dbr->fetchRow( $res ) ) {
			try {
				$ts = $row['rc_title'];
				$p = \WikiPathways\Pathway::newFromTitle(
					Title::newFromText( $ts, NS_PATHWAY )
				);
				if ( !$p->getTitleObject()->isRedirect() && $p->isReadable() ) {
					$objects[] = new PathwayInfo( $p );
				}
			} catch ( Exception $e ) {
				wfDebug(
					"Unable to create pathway object for recent changes: $e"
				);
			}

		}
		return [ "pathways" => $objects ];
	}

	/**
	 * Find pathways by a textual search.
	 * @param string $query The query, e.g. 'apoptosis'
	 * @param string $species Optional, limit the query by species. Leave
	 * blank to search on all species
	 * @return array of object SearchResult $result Array of
	 * SearchResult objects
	 **/
	public static function findPathwaysByText( $query, $species = '' ) {
		try {
			$objects = [];
			$results = PathwayIndex::searchByText( $query, $species );
			foreach ( $results as $r ) {
				$objects[] = new SearchResult( $r, [] );
			}
			return [ "result" => $objects ];
		} catch ( Exception $e ) {
			wfDebug( "ERROR: $e" );
			throw new Fault( "Receiver", $e );
		}
	}

	/**
	 * Find pathways by a datanode xref.
	 * @param array of string $ids The datanode identifier (e.g. 'P45985')
	 * @param array of string $codes Optional, limit the query by
	 * database (e.g. 'S' for UniProt). Leave blank to search on all
	 * databases
	 * @return array of object SearchResult $result Array of
	 * SearchResult objects
	 **/
	public static function findPathwaysByXref( $ids, $codes = '' ) {
		try {
			if ( $codes ) {
				if ( count( $codes ) == 1 ) { // One code for all ids
					$codes = array_fill( 0, count( $ids ), $codes[0] );
				} elseif ( count( $codes ) != count( $ids ) ) {
					throw new Fault(
						"Sender", "Number of supplied ids does not match "
						. "number of system codes" );
				}
			} else {
				$codes = array_fill( 0, count( $ids ), '' );
			}
			$xrefs = [];
			$xrefsStr = [];
			for ( $i = 0; $i < count( $ids ); $i += 1 ) {
				$x = new XRef( $ids[$i], $codes[$i] );
				$xrefs[] = $x;
				$xrefsStr[] = (string)$x;
			}
			$objects = [];
			$results = PathwayIndex::searchByXref( $xrefs, true );
			foreach ( $results as $r ) {
				$wsr = new SearchResult( $r );
				$objects[] = $wsr;
			}
			return [ "result" => $objects ];
		} catch ( Exception $e ) {
			wfDebug( "ERROR: $e" );
			throw new Fault( "Receiver", $e );
		}
	}

	/**
	 * Find pathways by literature references.
	 * @param string $query The query, can be a pubmed id, author name
	 * or title keyword.
	 * @return array of object SearchResult $result Array of
	 * SearchResult objects
	 */
	public static function findPathwaysByLiterature( $query ) {
		try {
			$results = PathwayIndex::searchByLiterature( $query );
			$combined = [];
			foreach ( $results as $r ) {
				$nwsr = new SearchResult( $r, [
					PathwayIndex::$f_graphId,
					PathwayIndex::$f_literature_pubmed,
					PathwayIndex::$f_literature_title,
				] );
				$source = $r->getFieldValue( PathwayIndex::$f_source );
				if ( $combined[$source] ) {
					$wsr =& $combined[$source];
					foreach ( array_keys( $wsr->fields ) as $fn ) {
						if ( $nwsr->fields[$fn] ) {
							$newvalues = array_merge(
								$nwsr->fields[$fn]->values,
								$wsr->fields[$fn]->values
							);
							$newvalues = array_unique( $newvalues );
							$wsr->fields[$fn]->values = $newvalues;
						}
					}
				} else {
					$combined[$source] = $nwsr;
				}
			}
			return [ "result" => $combined ];
		} catch ( Exception $e ) {
			wfDebug( "ERROR: $e" );
			throw new Fault( "Receiver", $e );
		}
	}

	/**
	 * Find interactions.
	 * @param string $query The name of an entity to find interactions
	 * for (e.g. 'P53')
	 * @return array of object SearchResult $result Array of
	 * SearchResult objects
	 **/
	public static function findInteractions( $query ) {
		try {
			$objects = [];
			$results = PathwayIndex::searchInteractions( $query );
			foreach ( $results as $r ) {
				$objects[] = new SearchResult( $r );
			}
			return [ "result" => $objects ];
		} catch ( Exception $e ) {
			throw new Fault( "Receiver", $e );
		}
	}

	/**
	 * List the datanode xrefs of a pathway, translated to the given
	 * identifier system. Note that the number of items may differ from
	 * the number of datanodes on the pathway (due to a many-to-many mapping
	 * between the different databases).
	 * @param string $pwId The pathway identifier.
	 * @param string $code The database code to translate to (e.g. 'S'
	 * for UniProt).
	 * @return array of string $xrefs The translated xrefs.
	 */
	public static function getXrefList( $pwId, $code ) {
		try {
			$list = PathwayIndex::listPathwayXrefs(
				new \WikiPathways\Pathway( $pwId ), $code
			);
			return [ "xrefs" => $list ];
		} catch ( Exception $e ) {
			throw new Fault( "Receiver", "Unable to process request: " . $e );
		}
	}

	/**
	 * Apply a curation tag to a pahtway. This operation will
	 * overwrite any existing tag with the same name.
	 * @param string $pwId The pathway identifier
	 * @param string $tagName The name of the tag to apply
	 * @param string $tagText The tag text (optional)
	 * @param int $revision The revision this tag applies to
	 * @param string $auth The authentication key
	 * @param string $username The user name
	 * @return bool $success
	 */
	public static function saveCurationTag(
		$pwId, $tagName, $text, $revision, $auth, $username
	) {
		if ( $auth ) {
			authenticate( $username, $auth, true );
		}

		try {
			$pathway = new \WikiPathways\Pathway( $pwId );
			if ( $pathway->exists() ) {
				$pageId = $pathway->getTitleObject()->getArticleId();
				\WikiPathways\CurationTag::saveTag( $pageId, $tagName, $text, $revision );
			}
		} catch ( Exception $e ) {
			wfDebug( "ERROR: $e" );
			throw new Fault( "Receiver", $e );
		}
		return [ "success" => true ];
	}

	/**
	 * Remove a curation tag from a pathway.
	 * @param string $pwId The pathway identifier
	 * @param string $tagName The name of the tag to apply
	 * @param string $auth The authentication data
	 * @param string $username The user name
	 * @return bool $success
	 **/
	public static function removeCurationTag(
		$pwId, $tagName, $auth, $username
	) {
		if ( $auth ) {
			authenticate( $username, $auth, true );
		}

		try {
			$pathway = new \WikiPathways\Pathway( $pwId );
			if ( $pathway->exists() ) {
				$pageId = $pathway->getTitleObject()->getArticleId();
				\WikiPathways\CurationTag::removeTag( $tagName, $pageId );
			}
		} catch ( Exception $e ) {
			wfDebug( "ERROR: $e" );
			throw new Fault( "Receiver", $e );
		}
		return [ "success" => true ];
	}

	/**
	 * Get all curation tags for the given pathway.
	 * @param string $pwId The pathway identifier
	 * @return array of object CurationTag $tags The curation tags.
	 **/
	public static function getCurationTags( $pwId ) {
		$pw = new \WikiPathways\Pathway( $pwId );
		$pageId = $pw->getTitleObject()->getArticleId();
		$tags = \WikiPathways\CurationTag::getCurationTags( $pageId );
		$wstags = [];
		foreach ( $tags as $t ) {
			$wstags[] = new CurationTag( $t );
		}
		return [ "tags" => $wstags ];
	}

	/**
	 * Get all curation tags for the given
	 * tag name.
	 * @param string $tagName The tag name
	 * @return array of object CurationTag $tags The curation tags
	 */
	public static function getCurationTagsByName( $tagName ) {
		$tags = \WikiPathways\CurationTag::getCurationTagsByName( $tagName );
		$wstags = [];
		foreach ( $tags as $t ) {
			$wst = new CurationTag( $t );
			if ( $wst->pathway ) {
				$wstags[] = $wst;
			}
		}
		return [ "tags" => $wstags ];
	}

	/**
	 * Get the curation tag history for the given pathway.
	 * @param string $pwId The pathway identifier
	 * @param string $timestamp Only include history from after the given date
	 * @return array of object CurationTagHistory $history The history
	 **/
	public static function getCurationTagHistory( $pwId, $timestamp = 0 ) {
		$pw = new \WikiPathways\Pathway( $pwId );
		$pageId = $pw->getTitleObject()->getArticleId();
		$hist = \WikiPathways\CurationTag::getHistory( $pageId, $timestamp );
		$wshist = [];
		foreach ( $hist as $h ) {
			$wshist[] = new CurationTagHistory( $h );
		}
		return [ "history" => $wshist ];
	}

	/**
	 * Get a colored image version of the pahtway.
	 * @param string $pwId The pathway identifier
	 * @param string $revision The revision of the pathway (use '0'
	 * for most recent)
	 * @param array of string $graphId An array with graphIds of the
	 * objects to color
	 * @param array of string $color An array with colors of the
	 * objects (should be the same length as $graphId)
	 * @param string $fileType The image type (One of 'svg', 'pdf' or 'png').
	 * @return base64Binary $data The image data (base64 encoded)
	 **/
	public static function getColoredPathway(
		$pwId, $revision, $graphId, $color, $fileType
	) {
		try {
			$p = new \WikiPathways\Pathway( $pwId );
			$p->setActiveRevision( $revision );
			$gpmlFile = realpath( $p->getFileLocation( FILETYPE_GPML ) );

			$outFile = WPI_TMP_PATH . "/" . $p->getTitleObject()->getDbKey()
					 . '.' . $fileType;

			if ( count( $color ) != count( $graphId ) ) {
				throw new Exception(
					"Number of colors doesn't match number of graphIds"
				);
			}
			$colorArg = '';
			for ( $i = 0; $i < count( $color ); $i++ ) {
				$colorArg .= " -c '{$graphId[$i]}' '{$color[$i]}'";
			}

			$basePath = WPI_SCRIPT_PATH;
			$cmd = "java -jar $basePath/bin/pathvisio_color_exporter.jar "
				 . "'$gpmlFile' '$outFile' $colorArg 2>&1";
			wfDebug( "COLOR EXPORTER: $cmd\n" );
			exec( $cmd, $output, $status );

			$msg = "";
			foreach ( $output as $line ) {
				$msg .= $line . "\n";
			}
			if ( $status != 0 ) {
				throw new Exception(
					"Unable to convert to $outFile:\n"
					. "Status:$status\nMessage:$msg"
				);
			}
			$data = file_get_contents( $outFile );
		} catch ( Exception $e ) {
			throw new Fault( "Receiver", "Unable to get pathway: " . $e );
		}
		return [ "data" => base64_encode( $data ) ];
	}

	// Non ws functions
	public static function authenticate( $username, $token, $write = false ) {
		global $wgUser, $wgAuth;
		$user = User::newFromName( $username );
		if ( is_null( $user ) || $user->getID() == 0 ) {
			throw new Fault( "Sender", "Invalid user name" );
		}
		$user->load();
		if ( $user->getToken() == $token ) {
			$wgAuth->updateUser( $user );
			$wgUser = $user;
		} else {
			throw new Fault( "Sender", "Wrong authentication token" );
		}
		if ( $write ) { // Also check for write access
			$rights = $user->getRights();
			if ( !in_array( 'webservice_write', $rights ) ) {
				throw new Fault(
					"Sender", "Account doesn't have write access for the "
					. "web service. \nContact the site administrator to "
					. "request write permissions."
				);
			}
		}
	}

	/**
	 * Apply a ontology tag to a pahtway.
	 * @param string $pwId The pathway identifier
	 * @param string $term The ontology term to apply
	 * @param string $termId The identifier of the term in the ontology
	 * @param string $auth The authentication key
	 * @param string $user The username
	 * @return bool $success
	 */
	public static function saveOntologyTag(
		$pwId, $term, $termId, $auth, $user
	) {
		if ( $auth ) {
			authenticate( $user, $auth, true );
		}
		try {
			$pathway = new \WikiPathways\Pathway( $pwId );
			if ( $pathway->exists() ) {
				OntologyFunctions::addOntologyTag( $termId, $term, $pwId );
			}
		} catch ( Exception $e ) {
			wfDebug( "ERROR: $e" );
			throw new Fault( "Receiver", $e );
		}
		return [ "success" => true ];
	}

	/**
	 * Remove an ontology tag from a pathway
	 * @param string $pwId The pathway identifier
	 * @param string $termId The ontology term identifier in the ontology
	 * @param string $auth The authentication key
	 * @param string $user The username
	 * @return bool $success
	 */
	public static function removeOntologyTag( $pwId, $termId, $auth, $user ) {
		if ( $auth ) {
			authenticate( $user, $auth, true );
		}

		try {
			$pathway = new \WikiPathways\Pathway( $pwId );
			if ( $pathway->exists() ) {
				OntologyFunctions::removeOntologyTag( $termId, $pwId );
			}
		} catch ( Exception $e ) {
			wfDebug( "ERROR: $e" );
			throw new Fault( "Receiver", $e );
		}
		return [ "success" => true ];
	}

	/**
	 * Get a list of ontology terms for a given pathway
	 * @param string $pwId The pathway identifier
	 * @return array of object OntologyTerm $terms The ontology terms
	 **/
	public static function getOntologyTermsByPathway( $pwId ) {
		try {
			$pw = new \WikiPathways\Pathway( $pwId );
			$terms = [];
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				'ontology',
				[ '*' ],
				[ 'pw_id = ' . $dbr->addQuotes( $pwId ) ]
			);

			$terms = [];
			$count = 0;
			while ( $row = $dbr->fetchObject( $res ) ) {
				$term = new OntologyTerm();
				$term->id = $row->term_id;
				$term->name = $row->term;
				$term->ontology = $row->ontology;
				$terms[] = $term;
				$count++;
			}
			$dbr->freeResult( $res );

			$termObjects = [];
		} catch ( Exception $e ) {
			throw new Fault(
				"Receiver", "Unable to get ontology terms: " . $e
			);
		}
		return [ "terms" => $terms ];
	}

	/**
	 * Get a list of ontology terms from a given ontology
	 * @param string $ontology The Ontology name
	 * @return array of object OntologyTerm $terms The ontology terms
	 **/
	public static function getOntologyTermsByOntology( $ontology ) {
		try {
			$terms = [];
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				'ontology',
				[ '*' ],
				[ 'ontology = ' . $dbr->addQuotes( $ontology ) ]
			);

			$terms = [];
			$count = 0;
			while ( $row = $dbr->fetchObject( $res ) ) {
				$term = new OntologyTerm();
				$term->id = $row->term_id;
				$term->name = $row->term;
				$term->ontology = $row->ontology;
				$terms[] = $term;
				$count++;
			}
			$dbr->freeResult( $res );

			$termObjects = [];
		} catch ( Exception $e ) {
			throw new Fault(
				"Receiver", "Unable to get ontology terms: " . $e
			);
		}
		return [ "terms" => $terms ];
	}

	/**
	 * Get a list of pathways tagged with a given ontology term
	 * @param string $term The Ontology term
	 * @return array of object PathwayInfo $pathways Array of pathway
	 * info objects
	 **/
	public static function getPathwaysByOntologyTerm( $term ) {
		try {
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				'ontology',
				[ '*' ],
				[ 'term_id = ' . $dbr->addQuotes( $term ) ]
			);
			$objects = [];
			while ( $row = $dbr->fetchObject( $res ) ) {
				$pathway = \WikiPathways\Pathway::newFromTitle(
					Title::newFromText( $row->pw_id, NS_PATHWAY )
				);
				$objects[] = new PathwayInfo( $pathway );
			}
			$dbr->freeResult( $res );
		} catch ( Exception $e ) {
			throw new Fault( "Receiver", "Unable to get Pathways: " . $e );
		}
		return [ "pathways" => $objects ];
	}

	/**
	 * Get a list of pathways tagged with a ontology term which is the
	 * child of the given Ontology term
	 * @param string $term The Ontology term
	 * @return array of object PathwayInfo $pathways Array of pathway
	 * info objects
	 **/
	public static function getPathwaysByParentOntologyTerm( $term ) {
		try {
			$dbr = wfGetDB( DB_SLAVE );
			// added OR statement as temporary fix while term_path
			// method in otag is broken
			$res = $dbr->select( 'ontology', '*', $dbr->makeList( [
				'term_path' . $dbr->buildLike(
					$dbr->anyString(), $term, $dbr->anyString()
				),
				'term_id' => $term
			], $dbr::LIST_OR ), __METHOD__ );
			$objects = [];
			while ( $row = $dbr->fetchObject( $res ) ) {
				$pathway = \WikiPathways\Pathway::newFromTitle(
					Title::newFromText( $row->pw_id, NS_PATHWAY )
				);
				$objects[] = new PathwayInfo( $pathway );
			}
			$dbr->freeResult( $res );
		} catch ( Exception $e ) {
			throw new Fault( "Receiver", "Unable to get Pathways: " . $e );
		}
		return [ "pathways" => $objects ];
	}

	public static function formatXml( $xml ) {
		if ( is_array( $xml ) ) {
			return array_map( htmlentities, $xml );
		} else {
			return htmlentities( $xml );
		}
	}

	public static function getUserByOrcid( $orcid ) {
		$url = 'http://www.wikipathways.org/api.php'
			 . '?action=query&list=search&srwhat=text'
			 . '&srsearch=%22{{User+ORCID|'.$orcid.'}}%22'
			 . '&srnamespace=2&format=json';

		$ch = curl_init();

		// set url
		curl_setopt( $ch, CURLOPT_URL, $url );

		// return the transfer as a string
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		// $output contains the output string
		$output = curl_exec( $ch );

		// close curl resource to free up system resources
		curl_close( $ch );

		$result = json_decode( $output );

		if ( $result === null ) {
			$r["error"] = "Error decoding json: " . json_last_error();
		}
		if ( sizeof( $result->query->search ) == 0 ) {
			$r["error"] = "No results found";
		}
		if ( sizeof( $result->query->search ) > 1 ) {
			$r["error"] = "Ambiguous result. 2 or more results were found";
		}

		if ( !isset( $r["error"] ) ) {
			$r["success"] = $result->query->search[0]->title;
		}
		return $r;
	}

	public static function isClearGPML( $xml ) {
		return $xml[0] === "<";
	}

	public static function decodeGpml( $gpml ) {
		if ( !self::isClearGPML( $gpml ) ) {
			return base64_decode( $gpml );
		} else {
			return $gpml;
		}
	}
}
