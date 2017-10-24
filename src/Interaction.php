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

class Interaction {
	// The interaction elements (all SimpleXML elements)
	private $source;
	private $target;
	private $edge;
	private $type;

	public function __construct( $source, $target, $edge, $type ) {
		$this->source = $source;
		$this->target = $target;
		$this->edge = $edge;
		$this->type = $type;
	}
	public function getSource() {
		return $this->source;
	}
	public function getTarget() {
		return $this->target;
	}
	public function getEdge() {
		return $this->edge;
	}
	public function getType() {
		return $this->type;
	}

	public function getName() {
		$source = $this->source['TextLabel'];
		if ( !$source ) { $source = $this->source->getName() . $this->source['GraphId'];
		}
		$target = $this->target['TextLabel'];
		if ( !$target ) { $target = $this->target->getName() . $this->target['GraphId'];
		}
		$type = $this->type;
		return $source. " -> " . $type . " -> " . $target;
	}
	public function getNameSoft() {
		$source = $this->source['TextLabel'];
		if ( !$source ) { $source = "";
		}
		$target = $this->target['TextLabel'];
		if ( !$target ) { $target = "";
		}
		$type = $this->type;
		return $source. " -> " . $type . " -> " . $target;
	}
	public function getPublicationXRefs( $pathwayData ) {
		$xrefs = $pathwayData->getPublicationXRefs();
		foreach ( $this->edge->BiopaxRef as $bpref ) {
			$myrefs[] = $xrefs[(string)$bpref];
		}
		return $myrefs;
	}
}
