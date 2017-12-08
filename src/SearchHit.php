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

class SearchHit {
	private $pathway;
	private $fields;
	private $score;

	public function __construct( $score, $fields ) {
		$this->score = $score;
		$this->fields = $fields;
	}

	public function getPathway() {
		if ( !$this->pathway ) {
			$this->pathway = PathwayIndex::pathwayFromSource(
				$this->fields[PathwayIndex::$f_source][0]
			);
		}
		return $this->pathway;
	}

	public function getScore() {
		return $this->score;
	}

	public function getFieldValues( $name ) {
		return $this->fields[$name];
	}

	public function setFieldValues( $name, $values ) {
		$this->fields[$name] = $values;
	}

	public function getFieldNames() {
		return array_keys( $this->fields );
	}

	public function getFieldValue( $name ) {
		$values = $this->fields[$name];
		if ( $values ) {
			return $values[0];
		}
	}
}
