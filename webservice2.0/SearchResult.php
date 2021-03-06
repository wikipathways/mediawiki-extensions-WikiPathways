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

/**
 * @namespace http://www.wikipathways.org/webservice
 */
class SearchResult extends PathwayInfo {
    /**
     * @param $searchHit an object of class SearchHit
     * @param $includeFields an array with the fields to include.
     * Leave 'null' to include all fields.
     **/
    function __construct( $hit, $includeFields = null ) {
        parent::__construct( $hit->getPathway() );
        $this->score = $hit->getScore();
        if ( $includeFields === null ) {
            $includeFields = $hit->getFieldNames();
        }
        $this->fields = [];
        foreach ( $includeFields as $fn ) {
            if ( in_array( $fn, $hit->getFieldNames() ) ) {
                $v = $hit->getFieldValues( $fn );
                if ( $v && count( $v ) > 0 ) {
                    $this->fields[$fn] = new IndexField( $fn, $v );
                }
            }
        }
    }

    /**
     * @var double $score - the score of the search result
     **/
    public $score;

    /**
     * @var array of object IndexField $fields - the url to the pathway
     **/
    public $fields;
}
