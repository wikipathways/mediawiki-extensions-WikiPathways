<?php
/**
 * Copyright (C) 2017  J. David Gladstone Institutes
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
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
 * @author Mark A. Hershberger
 */
namespace WikiPathways;

class DiffApplet extends \SpecialPage {
	public function __construct() {
		parent::__construct( "DiffAppletPage" );
	}

	public function execute( $par ) {
		$this->setHeaders();
		$request = $this->getRequest();
		$out = $this->getOutput();

		try {
			$revOld = $request->getVal( 'old' );
			$revNew = $request->getVal( 'new' );
			$pwTitle = $request->getVal( 'pwTitle' );
			$pathway = Pathway::newFromTitle( $pwTitle );
		} catch ( Exception $e ) {
			$out->addHTML(
				'<H2>Error</H2><P>The given title is not a pathway page!</P>'
			);
			return;
		}
		$pwName = $pathway->name() . ' (' . $pathway->species() . ')';
		$headerTable = <<<TABLE
<TABLE width="100%"><TBODY>
<TR align="center">
<TD>{$pwName}, revision {$revOld}
<TD>{$pwName}, revision {$revNew}
</TBODY></TABLE>
TABLE;
		$out->addHTML( $headerTable );
		$out->addHTML( self::createDiffApplet( $pathway, $revOld, $revNew ) );
	}

	public static function createDiffApplet( $pathway, $revOld, $revNew ) {
		$pathway->setActiveRevision( $revOld );
		$file1 = $pathway->getFileURL( FILETYPE_GPML );

		$pathway->setActiveRevision( $revNew );
		$file2 = $pathway->getFileURL( FILETYPE_GPML );

		$base = EditApplet::getAppletBase();
		$archive_string = '';
		$jardir = WPI_SCRIPT_PATH . '/applet';
		$cache_archive = explode( ' ', file_get_contents( "$jardir/cache_archive" ) );
		foreach ( $cache_archive as $jar ) {
			# check for file existence
			filemtime( "$jardir/$jar" );
			$archive_string .= $jar . ', ';
		}

		$applet = <<<APPLET
	<applet
		width="100%"
		height="500"
		standby="Loading DiffView applet ..."
		codebase="$base"
		archive="$archive_string"
		type="application/x-java-applet"
		code="org.wikipathways.gpmldiff.AppletMain.class">
		<param name="old" value="$file1"/>
		<param name="new" value="$file2"/>
	</applet>
APPLET;
	return $applet;
	}
}
