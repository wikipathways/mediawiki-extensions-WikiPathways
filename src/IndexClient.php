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

use Exception;
use HttpRequest;
use MWException;
use SearchHit;
use SimpleXMLElement;

/**
 * Handles the requests to the REST indexer service.
 * The base url to this service can be specified using the global variable $indexServiceUrl
 */
class IndexClient {
	private static function doQuery( $url ) {
		if ( !extension_loaded( "curl" ) ) {
			throw new MWException( "Please install curl for PHP" );
		}

		$ch = curl_init( $url );

		// curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
		// curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		$success = true;
		$result = curl_exec( $ch );
		$info = curl_getinfo( $ch );

		if ( $info['http_code'] != 200 ) {
			$success = false;
			throw new IndexNotFoundException( \Status::newFatal( 'wp-lucene-not-working' ) );
		}

		curl_close( $ch );

		if ( $success ) {
			$xml = new SimpleXMLElement( $result );
			$results = [];
			// Convert the response to SearchHit objects
			foreach ( $xml->SearchResult as $resultNode ) {
				$score = $resultNode['Score'];
				$fields = [];
				foreach ( $resultNode->Field as $fieldNode ) {
					$fields[(string)$fieldNode['Name']][] = (string)$fieldNode['Value'];
				}
				// Remove duplicate values
				foreach ( array_keys( $fields ) as $fn ) {
					$fields[$fn] = array_unique( $fields[$fn] );
				}
				$results[] = new SearchHit( $score, $fields );
			}
			return $results;
		} else {
			$txt = $r->getResponseBody();
			if ( strpos( $txt, '<?xml' ) !== false ) {
				$xml = new SimpleXMLElement( $r->getResponseBody() );
				throw new Exception( $xml->message );
			} else {
				throw new Exception( $r->getResponseBody() );
			}
		}
	}

	private static function postQuery( $url, $ids, $codes ) {
		$r = new HttpRequest( $url, HttpRequest::METH_POST );
		$r->addPostFields(
			[
				'id' => '210',
				'code' => 'L'
			] );
		try {
			$r->send();
		} catch ( Exception $e ) {
			throw new IndexNotFoundException( $e );
		}
		if ( $r->getResponseCode() == 200 ) {
			$xml = new SimpleXMLElement( $r->getBody() );
			$results = [];
			// Convert the response to SearchHit objects
			foreach ( $xml->SearchResult as $resultNode ) {
				$score = $resultNode['Score'];
				$fields = [];
				foreach ( $resultNode->Field as $fieldNode ) {
					$fields[(string)$fieldNode['Name']][] = (string)$fieldNode['Value'];
				}
				// Remove duplicate values
				foreach ( array_keys( $fields ) as $fn ) {
					$fields[$fn] = array_unique( $fields[$fn] );
				}
				$results[] = new SearchHit( $score, $fields );
			}
			return $results;
		} else {
			$txt = $r->getResponseBody();
			if ( strpos( $txt, '<?xml' ) ) {
				$xml = new SimpleXMLElement( $r->getResponseBody() );
				throw new Exception( $xml->message );
			} else {
				throw new Exception( $r->getResponseBody() );
			}
		}
	}

	/**
	 * Performs a query on the index service and returns the results
	 * as an array of SearchHit objects.
	 */
	public static function query( $query, $analyzer = '' ) {
		$url = self::getServiceUrl() . 'search?query=' . urlencode( $query );
		if ( $analyzer ) {
			$url .= "&analyzer=$analyzer";
		}
		return self::doQuery( $url );
	}

	public static function queryXrefs( $ids, $codes ) {
		$enc_ids = [];
		$enc_codes = [];
		foreach ( $ids as $i ) {
			$enc_ids[] = urlencode( $i );
		}
		foreach ( $codes as $c ) {
			$enc_codes[] = urlencode( $c );
		}

		$url = self::getServiceUrl() . 'searchxrefs?';
		$url .= 'id=' . implode( '&id=', $enc_ids );
		if ( count( $enc_codes ) > 0 ) {
			$url .= '&code=' . implode( '&code=', $enc_codes );
		}
		return self::doQuery( $url );
		# return self::postQuery($url, $ids, $codes);
	}

	/**
	 * Get the xrefs for a pathway, translated to the given system code.
	 * @return an array of strings containing the ids.
	 */
	static function xrefs( $pathway, $code, $local='TRUE' ) {
		$source = $pathway->getTitleObject()->getFullURL();
		if ( $local == 'FALSE' ) {
			$source = preg_replace( '/localhost/', 'www.wikipathways.org', $source );
		}
		$url = self::getServiceUrl() . "xrefs/" . urlencode( $source ) . "/"
			 . urlencode( $code );

		if ( !extension_loaded( "curl" ) ) {
			throw new MWException( "Please install curl for PHP" );
		}
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		$r = curl_exec( $ch );
		$info = curl_getinfo( $ch );

		if ( $info['http_code'] != 200 ) {
			$success = false;
			throw new IndexNotFoundException( $e );
		}

		curl_close( $ch );

		return explode( "\n", $r );
	}

	/**
	 * Get the service URL
	 * @return string
	 */
	public static function getServiceUrl() {
		global $indexServiceUrl;
		if ( !$indexServiceUrl ) {
			throw new IndexNotFoundException();
		}
		return $indexServiceUrl;
	}
}
