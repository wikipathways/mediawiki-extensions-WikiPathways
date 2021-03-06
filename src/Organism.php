<?php
/**
 * Utility class to hold information about an organism and
 * maintain a list of registered organisms.
 *
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
 *
 * @author
 * @author Mark A. Hershberger <mah@nichework.com>
 */
namespace WikiPathways;

class Organism {
	private $latinName;
	private $code;

	private static $byLatinName = [];
	private static $byCode = [];

	/**
	 * Get the latin name for this organism
	 *
	 * @return string
	 */
	public function getLatinName() {
		return $this->latinName;
	}

	/**
	 * Get the short code for this organism
	 *
	 * @return string
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * Get an organism by latin name
	 *
	 * @param string $name latin name
	 * @return Organism|null
	 */
	public static function getByLatinName( $name ) {
		return isset( self::$byLatinName["$name"] )
			? self::$byLatinName["$name"]
			: null;
	}

	/**
	 * Get an organism by the short code
	 *
	 * @param string $code short code for organism
	 * @return Organism|null
	 */
	public static function getByCode( $code ) {
		return isset( self::$byCode["$code"] )
			? self::$byCode["$code"]
			: null;
	}

	/**
	 * List all registered organisms.
	 *
	 * @return array keys are the latin names and the values
	 * are instances of class Organism.
	 */
	public static function listOrganisms() {
		return self::$byLatinName;
	}

	/**
	 * Register a new organism for which pathways can be created.
	 *
	 * @param string $latinName the latin name
	 * @param string $code the organism's short code
	 */
	public static function register( $latinName, $code ) {
		$org = new Organism();
		$org->latinName = $latinName;
		$org->code = $code;
		self::$byLatinName[$latinName] = $org;
		self::$byCode[$code] = $org;
	}

	/**
	 * Remove an organism from the registry.
	 *
	 * @param Organism $org to de-register
	 */
	public static function remove( Organism $org ) {
		unset( self::$byLatinName[$org->latinName] );
		unset( self::$byCode[$org->code] );
	}

	/**
	 * Register all organisms supported by default on WikiPathways.
	 */
	public static function registerDefaultOrganisms() {
		self::register( 'Anopheles gambiae', 'Ag' );
		self::register( 'Arabidopsis thaliana', 'At' );
		self::register( 'Bacillus subtilis', 'Bs' );
		self::register( 'Beta vulgaris', 'Bv' );
		self::register( 'Bos taurus', 'Bt' );
		self::register( 'Caenorhabditis elegans', 'Ce' );
		self::register( 'Canis familiaris', 'Cf' );
		self::register( 'Clostridium thermocellum', 'Ct' );
		self::register( 'Danio rerio', 'Dr' );
		self::register( 'Drosophila melanogaster', 'Dm' );
		self::register( 'Escherichia coli', 'Ec' );
		self::register( 'Equus caballus', 'Qc' );
		self::register( 'Gallus gallus', 'Gg' );
		self::register( 'Glycine max', 'Gm' );
		self::register( 'Gibberella zeae', 'Gz' );
		self::register( 'Homo sapiens', 'Hs' );
		self::register( 'Hordeum vulgare', 'Hv' );
		self::register( 'Mus musculus', 'Mm' );
		self::register( 'Mycobacterium tuberculosis', 'Mx' );
		self::register( 'Oryza sativa', 'Oj' );
		self::register( 'Pan troglodytes', 'Pt' );
		self::register( 'Populus trichocarpa', 'Pi' );
		self::register( 'Rattus norvegicus', 'Rn' );
		self::register( 'Saccharomyces cerevisiae', 'Sc' );
		self::register( 'Solanum lycopersicum', 'Sl' );
		self::register( 'Sus scrofa', 'Ss' );
		self::register( 'Vitis vinifera', 'Vv' );
		self::register( 'Xenopus tropicalis', 'Xt' );
		self::register( 'Zea mays', 'Zm' );
	}
}
// Register the default organisms
Organism::registerDefaultOrganisms();
