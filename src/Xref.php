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

use MWException;

class Xref {
	private $id;
	private $system;

	public function __construct( $id, $system ) {
		$this->id = $id;
		$this->system = $system;
	}

	public function getId() {
		return $this->id;
	}

	public function getSystem() {
		return $this->system;
	}

	public static function fromText( $txt ) {
		$data = explode( ':', $txt );
		if ( count( $data ) !== 2 ) {
			throw new MWException( "Tried to create an Xref from incomplete text: '$txt'" );
		}
		return new Xref( $data[0], $data[1] );
	}

	public function asText() {
		return "{$this->id}:{$this->system}";
	}

	public function __toString() {
		return $this->asText();
	}
}
