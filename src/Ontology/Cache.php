<?php
/*
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
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways\Ontology;

class Cache {

	static function addKey( $url ) {
		global $wpiBioportalKey;
		return $url . '&apikey=' . $wpiBioportalKey;
	}

	public static function updateCache( $function, $params, $response ) {
		$dbw =& wfGetDB( DB_MASTER );
		$dbw->begin();
		$dbw->delete(
			'ontologycache', [
				'function' => $function,
				'params' => $params
			],
			'Database::delete'
		);
		$dbw->insert(
			'ontologycache', [
				'function' => $function,
				'params' => $params,
				'timestamp'=> time(),
				'response' => $response
			],
			'ontology-cache-insert',
			'IGNORE'
		);
		$dbw->commit();
	}

	public static function fetchCache( $function,$params, $timeOut = 0 ) {

		global $wgOntologiesExpiryTime;

		$params = self::addKey( $params );

		if ( $timeOut == 0 )
			$time = time() - $wgOntologiesExpiryTime; else $time = time() - $timeOut;

		$dbr =& wfGetDB( DB_SLAVE );
		$query = "SELECT * FROM `ontologycache` where function = '$function' AND params = '$params' ORDER BY timestamp DESC ";
		$res = $dbr->query( $query );
		// $res = $dbr->select( 'ontology', array('term','term_id','ontology'), array( 'pw_id' => $title ), $fname = 'Database::select', $options = array('Group by' => 'ontology' ));

		if ( $row = $dbr->fetchObject( $res ) )
		{
			if ( $row->timestamp > $time )
				return ( $row->response ); else {
				if ( $xml = @simplexml_load_file( $params ) )
				{
					$xml = $xml->asXML();
					ontologycache::updateCache( $function, $params, $xml );
					return ( $xml );
				} else {
					$dbw =& wfGetDB( DB_MASTER );
					$dbw->begin();
					$dbw->update( 'ontologycache', [ 'timestamp'=>time() ], [ "function"=>$function,"params"=>$params ], $fname = 'Database::update', $options = [] );
					$dbw->commit();
					return ( $row->response );
				}
			 }
		} else {
			if ( $xml = @simplexml_load_file( $params ) )
			{
				$xml = $xml->asXML();
				ontologycache::updateCache( $function, $params, $xml );
				return ( $xml );
			}
		}
		$dbr->freeResult( $res );
	}

	public static function fetchBrowseCache( $params, $timeOut = 0 ) {

		global $wgOntologiesExpiryTime;
		$function = 'browse';

		if ( $timeOut == 0 )
			$time = time() - $wgOntologiesExpiryTime; else $time = time() - $timeOut;

		$dbr =& wfGetDB( DB_SLAVE );
		$query = "SELECT * FROM `ontologycache` where function = '$function' AND params = '$params' ORDER BY timestamp DESC ";
		$res = $dbr->query( $query );

		if ( $row = $dbr->fetchObject( $res ) )
		{
			if ( $row->timestamp > $time ) {
				return ( $row->response );
			} else {
				return false;
			}
		}
		$dbr->freeResult( $res );
	}

}
class_alias( "WikiPathways\\Ontology\\Cache", "OntologyCache" );
