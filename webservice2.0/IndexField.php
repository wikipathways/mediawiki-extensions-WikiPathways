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
class IndexField {
    function __construct( $name, $values ) {
        $this->name = $name;
        $this->values = $values;
        $this->values = formatXml( $this->values );
    }

    /**
     * @var string $name - the name of the index field
     **/
    public $name;

    /**
     * @var array of string - the value(s) of the field
     **/
    public $values;
}
