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
class Relation {

    public function __construct( $result ) {
        if ( $result->pwId_1 ) {
            $this->pathway1 = new WSPathwayInfo(
                Pathway::newFromTitle( $result->pwId_1 )
            );
        }
        if ( $result->pwId_2 ) {
            $this->pathway2 = new WSPathwayInfo(
                Pathway::newFromTitle( $result->pwId_2 )
            );
        }
        $this->type = $result->type;
        $this->score = (float)$result->score;
    }

    /**
     * @var object WSPathwayInfo $pathway1 for the first pathway
     */
    public $pathway1;

    /**
     * @var object WSPathwayInfo $pathway2 for the second pathway
     */
    public $pathway2;

    /**
     *@var string $type The type of the relation
     */
    public $type;

    /**
     *@var float $score The degree of relativeness(score) between the pair of pathways
     */
    public $score;
}
