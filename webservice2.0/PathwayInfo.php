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

// Class definitions
/**
 * @namespace http://www.wikipathways.org/webservice
 */
class PathwayInfo {
    function __construct( $pathway ) {
        $this->id = $pathway->getIdentifier();
        $this->revision = $pathway->getLatestRevision();
        $this->species = $pathway->species();
        $this->name = formatXml( $pathway->name() );
        $this->url = $pathway->getTitleObject()->getFullURL();

        // Hack to make response valid in case of missing revision
        if ( !$this->revision ) { $this->revision = 0;
        }
    }

    /**
     * @var string $id - the pathway identifier
     */
    public $id;

    /**
     * @var string $url - the url to the pathway
     **/
    public $url;
    /**
     * @var string $name - the pathway name
     **/
    public $name;
    /**
     * @var string $species - the pathway species
     **/
    public $species;
    /**
     * @var string $revision - the revision number
     **/
    public $revision;
}

