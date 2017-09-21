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
 *
 * @author
 * @author Mark A. Hershberger <mah@nichework.com>
 */

namespace WikiPathways;

use Exception;

class EditApplet {
	private $pathwayId;
	private $pathwayName;
	private $pathwaySpecies;
	private $mainClass;
	private $idReplace;
	private $idClick;
	private $isNew;
	private $width, $height;
	private $param;
	private $noresize;

	/**
	 * Creates the applet
	 * @param type, type of the applet to start (editor, bibliography, ...)
	 * @param $idClick Id of the element to attach an 'onclick' event
	 * to that will trigger the applet to start. If this argument equals 'direct',
	 * the applet will be activated directly.
	 * @param $idReplace Id of the element that will be replaced by the applet
	 * @param $new Whether the pathway is yet to be created (will be passed on to the applet)
	 * and whether it should be private. Possible values:
	 * - '' or false: not a new pathway
	 * - 'private': a new pathway that should also be private
	 * - any other value: a new pathway that should be public
	 * @param $pwTitle The title of the pathway to be edited (Species:Pathwayname)
	 */
	public static function createApplet(
		&$parser, $idClick = 'direct', $idReplace = 'pwThumb', $new = false,
		$pwTitle = '', $type = 'editor', $width = 0, $height = '500px'
	) {
		global $wgUser, $wgScriptPath, $loaderAdded, $wpiJavascriptSources,
			$jsJQuery;

		// Check user rights
		if ( !$wgUser->isLoggedIn() || wfReadOnly() ) {
			// Don't return any applet code
			return "";
		}

		$parser->disableCache();

		// Extra parameters
		$param = [];
		$main = 'org.wikipathways.applet.gui.';
		$noresize = 'false';
		switch ( $type ) {
		case 'bibliography':
			$main .= 'BibliographyApplet';
			$noresize = 'true';
			break;
		case 'description':
			$main .= 'DescriptionApplet';
			$noresize = 'true';
			break;
		default: $main .= 'AppletMain';
		}

		if ( $new == 'private' ) {
			$param['private'] = "true";
		}

		try {
			// Pathway title contains species:name
			if ( $new ) {
				$editApplet = new EditApplet(
					null, $main, $idReplace, $idClick, $width, $height,
					$noresize, $param
				);
				$title = explode( ':', $pwTitle );
				$editApplet->setPathwaySpecies( array_pop( $title ) );
				$editApplet->setPathwayName( array_pop( $title ) );
			} else {
				// Check if the title is a pathway before continuing
				if ( $parser->mTitle->getNamespace() != NS_PATHWAY ) {
					return "";
				}
				$title = $parser->mTitle->getDbKey();
				$editApplet = new EditApplet(
					$title, $main, $idReplace, $idClick, $width, $height,
					$noresize, $param
				);
			}

			$appletCode = $editApplet->makeAppletFunctionCall();
			$jardir = $wgScriptPath . '/wpi/applet';

			// Add editapplet.js script
			$wpiJavascriptSources[] = JS_SRC_EDITAPPLET;
			XrefPanel::addXrefPanelScripts();
			$output = $appletCode;
		} catch ( Exception $e ) {
			return "Error: $e";
		}

		return [ $output, 'isHTML' => 1, 'noparse' => 1 ];
	}

	public static function scriptTag( $code, $src = '' ) {
		$src = $src ? 'src="' . $src . '"' : '';
		return '<script type="text/javascript" ' . $src . '>' . $code . '</script>';
	}

	public static function createJsArray( $array ) {
		$jsa = "new Array(";
		foreach ( $array as $elm ) {
			$jsa .= "'{$elm}', ";
		}
		return substr( $jsa, 0, strlen( $jsa ) - 2 ) . ')';
	}

	public static function increase_version( $old ) {
		$numbers = explode( '.', $old );
		$last = hexdec( $numbers[count( $numbers ) - 1] );
		$numbers[count( $numbers ) - 1] = dechex( ++$last );
		return implode( '.', $numbers );
	}

	public function __construct(
		$pathwayId, $mainClass, $idReplace, $idClick, $width, $height,
		$noresize, $param = []
	) {
		$this->pathwayId = $pathwayId;
		$this->mainClass = $mainClass;
		$this->idReplace = $idReplace;
		$this->idClick = $idClick;
		$this->isNew = $pathwayId ? false : true;
		$this->width = $width;
		$this->height = $height;
		$this->param = $param;
		$this->noresize = $noresize;
	}

	public function setPathwayName( $name ) {
		$this->pathwayName = $name;
	}
	public function setPathwaySpecies( $species ) {
		$this->pathwaySpecies = $species;
	}

	private static $version_string = false;
	private	static $archive_string = false;

	public static function getCacheParameters() {
		if ( self::$version_string && self::$archive_string ) {
			return [
				"version" => self::$version_string,
				"archive" => self::$archive_string
			];
		}

		// Read cache jars and update version
		$jardir = WPI_SCRIPT_PATH . '/applet';
		if ( !file_exists( "$jardir/cache_version" ) ) {
			if ( touch( "$jardir/cache_version" ) === false ) {
				throw new Exception( "The path $jardir isn't writable!" );
			}
		}

		$cache_archive = file_get_contents( "$jardir/cache_archive" );
		$version_file = file_get_contents( "$jardir/cache_version" );
		if ( $version_file === false || $cache_archive === false ) {
			throw new Exception(
				"cache_archive or cache_version in $jardir wasn't readable."
			);
		}
		$cache_archive = explode( ' ', $cache_archive );
		$version_file = explode( "\n", $version_file );
		$cache_version = [];
		if ( $version_file ) {
			foreach ( $version_file as $ver ) {
				$jarver = explode( "|", $ver );
				if ( $jarver && count( $jarver ) == 3 ) {
					$cache_version[$jarver[0]] = [
						'ver' => $jarver[1], 'mod' => $jarver[2]
					];
				}
			}
		}
		self::$archive_string = "";
		self::$version_string = "";
		foreach ( $cache_archive as $jar ) {
			$jarfile = "$jardir/$jar";
			if ( is_readable( $jarfile ) ) {
				$mod = filemtime( $jarfile );
				$ver = $cache_version[$jar];
				if ( $ver ) {
					if ( $ver['mod'] < $mod ) {
						$realversion = self::increase_version( $ver['ver'] );
					} else {
						$realversion = $ver['ver'];
					}
				} else {
					$realversion = '0.0.0.0';
				}
				$cache_version[$jar] = [ 'ver' => $realversion, 'mod' => $mod ];
				self::$archive_string .= $jar . ', ';
				self::$version_string .= $realversion . ', ';
			} else {
				throw new Exception( "Jar file isn't readable: $jarfile" );
			}
		}
		self::$version_string = substr( self::$version_string, 0, -2 );
		self::$archive_string = substr( self::$archive_string, 0, -2 );

		// Write new cache version file
		$out = "";
		foreach ( array_keys( $cache_version ) as $jar ) {
			$out .= $jar . '|' . $cache_version[$jar]['ver']
				 . '|' . $cache_version[$jar]['mod'] . "\n";
		}
		writefile( "$jardir/cache_version", $out );
		return [
			"archive" => self::$archive_string, "version" => self::$version_string
		];
	}

	public static function getParameterArray(
		$pathwayId, $pathwayName, $pathwaySpecies, $param = []
	) {
		global $wgUser, $wpiBridgeAppletUrl;

		// bridgedb web service support can be disabled by setting
		// $wpiBridgeDb to false
		if ( !isset( $wpiBridgeUrl ) ) {
			$wpiBridgeUrl = 'http://webservice.bridgedb.org/';
		}

		if ( $pathwayId ) {
			$pathway = new Pathway( $pathwayId );
			$revision = $pathway->getLatestRevision();
			$pathwaySpecies = $pathway->getSpecies();
			$pathwayName = $pathway->getName();
			$pwUrl = $pathway->getFileURL( FILETYPE_GPML );
		} else {
			$revision = 0;
			$pwUrl = '';
		}

		$cache = self::getCacheParameters();
		$archive_string = $cache["archive"];
		$version_string = $cache["version"];

		$args = [
			'rpcUrl' => WPI_URL . "/wpi_rpc.php",
			'pwId' => $pathwayId,
			'pwName' => $pathwayName,
			'pwSpecies' => $pathwaySpecies,
			'pwUrl' => $pwUrl,
			'cache_archive' => $archive_string,
			'cache_version' => $version_string,
			'gdb_server' => $wpiBridgeAppletUrl,
			'revision' => $revision,
			'siteUrl' => SITE_URL
		];

		if ( $wgUser && $wgUser->isLoggedIn() ) {
			$args = array_merge( $args, [ 'user' => $wgUser->getRealName() ] );
		}
		$args = array_merge( $args, $param );
		return $args;
	}

	public function getJsParameters() {
		$args = self::getParameterArray(
			$this->pathwayId, $this->pathwayName, $this->pathwaySpecies,
			$this->param
		);
		$keys = self::createJsArray( array_keys( $args ) );
		$values = self::createJsArray( array_values( $args ) );
		return [ 'keys' => $keys, 'values' => $values ];
	}

	public function makeAppletObjectCall() {
		global $wgScriptPath;
		$site = SITE_URL;
		$param = $this->getJsParameters();
		$base = self::getAppletBase();
		$keys = $param['keys'];
		$values = $param['values'];
		return "doApplet('{$this->idReplace}', 'applet', '$base', "
			. "'{$this->mainClass}', '{$this->width}', '{$this->height}', "
			. "{$keys}, {$values}, {$this->noresize}, '{$site}', "
			. "'{$wgScriptPath}');";
	}

	public static function getAppletBase() {
		global $wgScriptPath;
		return "$wgScriptPath/wpi/applet";
	}

	public function makeAppletFunctionCall() {
		$base = self::getAppletBase();
		$param = $this->getJsParameters();
		$keys = $param['keys'];
		$values = $param['values'];

		$function = $this->makeAppletObjectCall();
		if ( $this->idClick == 'direct' ) {
			return self::scriptTag( $function );
		} else {
			return self::scriptTag(
				"var elm = document.getElementById('{$this->idClick}');" .
				"var listener = function() { $function };" .
				"if(elm.attachEvent) { elm.attachEvent('onclick',listener); }" .
				"else { elm.addEventListener('click',listener, false); }" .
				"registerAppletButton('{$this->idClick}', '$base', $keys, $values);"
			);
		}
	}
}
