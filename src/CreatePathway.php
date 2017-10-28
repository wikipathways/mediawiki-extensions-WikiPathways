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

use Title;

class CreatePathway extends \SpecialPage {
	private $this_url;
	private $create_priv_msg;

	function __construct() {
		parent::__construct( "CreatePathwayPage" );
	}

	function execute( $par ) {
		global $wgParser;
		$this->setHeaders();
		$this->this_url = SITE_URL . '/index.php';

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();
		$wgParser->mStripState = new \StripState;
		$wgParser->mOptions = new \ParserOptions;
		$wgParser->mTitle = Title::newFromText( wfMessage( 'createpathwaypage' )->plain() );
		$wgParser->clearState();

		$this->create_priv_msg = $wgParser->recursiveTagParse( wfMessage( 'create_private' )->plain() );
		$wgParser->replaceLinkHolders( $this->create_priv_msg );

		if ( wfReadOnly() ) {
			$out->readOnlyPage( "" );
		}

		if ( !$user->isAllowed( 'createpathway' ) ) {
			if ( !$user->isLoggedIn() ) {
				/* Two different messages so we can keep the old error */
				$out->showPermissionsErrorPage( [ [ 'wpi-createpage-not-logged-in' ] ] );
			} else {
				$out->showPermissionsErrorPage( [ [ 'wpi-createpage-permission' ] ] );
			}
			return;
		}

		$pwName = $request->getVal( 'pwName' );
		$pwNameLen = strlen( $pwName );
		$pwSpecies = $request->getVal( 'pwSpecies' );
		$override = $request->getVal( 'override' );
		$private = $request->getVal( 'private' );
		$uploading = $request->getVal( 'upload' );
		$private2 = $request->getVal( 'private2' );

		if ( $request->getVal( 'create' ) == '1' ) {
			// Submit button pressed
			// Check for pathways with the same name and species
			$exist = Pathway::getPathwaysByName( $pwName, $pwSpecies );
			if ( count( $exist ) > 0 && !$override ) {
				// Print warning
				$pre = "A pathway";
				if ( count( $exist ) > 1 ) {
					$pre = "Pathways";
				}
				$out->addWikiText( "== Warning ==\n<font color='red'>$pre with the name '$pwName' already exist on WikiPathways:</font>\n" );
				foreach ( $exist as $p ) {
					$out->addWikiText(
						"* [[Pathway:" . $p->getIdentifier() . "|" . $p->getName() . " (" . $p->getSpecies() . ")]]"
					);
				}
				$out->addWikiText( "'''You may consider editing the existing pathway instead of creating a new one.'''\n" );
				$out->addWikiText( "'''If you still want to create a new pathway, please use a unique name.'''\n" );
				$out->addWikiText( "----\n" );
				$this->showForm( $pwName, $pwSpecies, true, $private );
			} elseif ( !$pwName ) {
				$out->addWikiText( "== Warning ==\n<font color='red'>No pathway name given!</font>\n'''Please specify a name for the pathway'''\n----\n" );
				$this->showForm( $pwName, $pwSpecies, true, $private );
			} elseif ( !$pwSpecies ) {
				$out->addWikiText( "== Warning ==\n<font color='red'>No species given!</font>\n'''Please specify a species for the pathway'''\n----\n" );
				$this->showForm( $pwName, $pwSpecies, true, $private );
			} elseif ( $pwNameLen > 200 ) {
				$out->addWikiText( "== Warning ==\n<font color='red'>Your pathway name is too long! ''($pwNameLen characters)''</font>\n" );
				$out->addWikiText( "'''Please specify a name with less than 200 characters.'''\n----\n" );
				$this->showForm( $pwName, $pwSpecies, false, $private );
			} else {
				$this->createPage( $pwName, $pwSpecies, $private );
			}
		} elseif ( $uploading == '1' ) {
			// Upload button pressed
			$this->doUpload( $uploading, $private2 );
		} else {
			$this->showForm();
		}
	}

	function doUpload( $uploading, $private2 ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		try {
			// Check for something... anything
			if ( !empty( $_FILES['gpml']['name'] ) ) {
				$size = $_FILES['gpml']['size'];
				// Check file size
				if ( $size > 1000000 ) {
					$size = $size / 1000000;
					$out->addWikiText( "== Warning ==\n<font color='red'>File too large! ''($size MB)''</font>\n'''Please select a GPML file under 1MB.'''\n----\n" );
					$out->addWikiText( "----\n" );
					$this->showForm( '', '', false, '', $uploading, $private2 );
				}
				$file = $_FILES['gpml']['name'];
				// Check for gpml extension
				if ( !eregi( ".gpml$", $file ) ) {
					$out->addWikiText( "== Warning ==\n<font color='red'>Not a GPML file!</font>\n'''Please select a GPML file for upload.'''\n----\n" );
					$out->addWikiText( "----\n" );
					$this->showForm( '', '', false, '', $uploading, $private2 );
				} else {
					// It looks good, let's create a new pathway!
					$gpmlTempFile = $_FILES['gpml']['tmp_name'];
					$GPML = fopen( $gpmlTempFile, 'r' );
					$gpmlData = fread( $GPML, filesize( $gpmlTempFile ) );
					fclose( $GPML );
					$pathway = Pathway::createNewPathway( $gpmlData );
					$title = $pathway->getTitleObject();
					$name = $pathway->getName();
					if ( $private2 ) { $pathway->makePrivate( $user );
					}
					$out->addWikiText( "'''<font color='green'>Pathway successfully upload!</font>'''\n'''Check it out:  [[$title|$name]]'''\n----\n" );
					$this->showForm( '', '', false, '', $uploading, $private2 );
				}
			} else {
				$out->addWikiText( "== Warning ==\n<font color='red'>No file detected!</font>\n'''Please try again.'''\n----\n" );
				$this->showForm( '', '', false, '', $uploading, $private2 );
			}
		} catch ( Exception $e ) {
			$out->addWikiText( "== Error ==\n<b><font color='red'>{$e->getMessage()}</font></b>\n\n<pre>$e</pre>\n'''Please try again.'''\n----\n" );
			$this->showForm( '', '', false, '', $uploading, $private2 );
		}
	}

	function createPage( $pwName, $pwSpecies, $private ) {
		$backlink = '<a href="javascript:history.back(-1)">Back</a>';
		try {
						$gpmlData = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
								'<Pathway xmlns="http://pathvisio.org/GPML/2013a" Name="'.$pwName.'" Organism="'.$pwSpecies.'">'."\n".
								'<Graphics BoardWidth="30.0" BoardHeight="30.0" />'."\n".
								'<InfoBox CenterX="0.0" CenterY="0.0" />'."\n".
								'</Pathway>';
						$pathway = Pathway::createNewPathway( $gpmlData );
						$title = $pathway->getTitleObject();
						$name = $pathway->getName();
						if ( $private2 ) { $pathway->makePrivate( $user );
						}
						$out->addWikiText( "'''<font color='green'>Pathway successfully created!</font>'''\n'''Check it out:  [[$title|$name]]'''\n----\n" );
		} catch ( Exception $e ) {
			$out->addHTML( "<B>Error:</B><P>{$e->getMessage()}</P><BR>$backlink</BR>" );
			return;
		}
	}

	function showForm( $pwName = '', $pwSpecies = '', $override = '', $private = '', $uploading = 0, $private2 = '' ) {
		$form_method = null;
		$form_extra = null;
		$upload_check = null;
		$editor_vis = null;
		$editor_check = null;
		$upload_vis = null;
		if ( $uploading ) {
			$form_method = "post";
			$form_extra = "enctype='multipart/form-data'";
			$upload_check = 'CHECKED';
			// switch the other one off
			$editor_vis = 'style="display:none;"';
		} else {
			$form_method = "get";
			$form_extra = "";
			$editor_check = 'CHECKED';
			// switch the other one off
			$upload_vis = 'style="display:none;"';
		}
		if ( $private2 ) { $private2 = 'CHECKED';
		}
		$html_upload = "<FORM action='$this->this_url' method='post' enctype='multipart/form-data'>
				<table style='margin-left: 20px;'><td>
					<INPUT type='file' name='gpml' size='40'>
					<tr><td>
					<INPUT type='checkbox' name='private2' value='1' $private2> $this->create_priv_msg
					<input type='hidden' name='upload' value='1'>
				<input type='hidden' name='title' value='Special:CreatePathwayPage'>
					<tr><td><INPUT type='submit' value='Upload pathway'></table></FORM>";
			$html_editor = " <FORM action='$this->this_url' method='get'>
				<table style='margin-left: 20px;'><td>Pathway name:
								<td><input type='text' name='pwName' value='$pwName'>
								<tr><td>Species:<td>
								<select name='pwSpecies'>";
				$species = Pathway::getAvailableSpecies();
				if ( !$pwSpecies ) {
					$pwSpecies = $species[0];
				}
				foreach ( $species as $sp ) {
					$html_editor .= "<option value='$sp'" . ( $sp == $pwSpecies ? ' selected' : '' ) . ">$sp";
				}
				$html_editor .= '</select>';
				if ( $override ) {
					$html_editor .= "<input type='hidden' name='override' value='1'>";
				}

				if ( $private ) {
					// private is array? array to string conversion notice
					$private = 'CHECKED';
				}
				$html_editor .= "<tr><td colspan='2'><input type='checkbox' name='private' value='1' $private> $this->create_priv_msg
				<input type='hidden' name='create' value='1'>
				<input type='hidden' name='title' value='Special:CreatePathwayPage'>
				<tr><td><input type='submit' value='Create pathway'> </table></FORM><BR>";

					$out->addHTML( "
			<P>Select to either use the pathway editor or upload a gpml file:<P>
						<FORM>
						<TABLE width='100%'><TBODY>
						<TR><TD><INPUT onclick='showEditor()' type='radio' name='visibility' value='editor' $editor_check><B>Use Editor</B>
						<DIV id='editor' $editor_vis>
			$html_editor
			</DIV>
						<TR><TD><INPUT onclick='showUpload()' type='radio' name='visibility' value='upload' $upload_check><B>Upload File</B>
						<DIV id='upload' $upload_vis>
			$html_upload
						</DIV>
						</TBODY></TABLE>
						</FORM>
						"
					);

					$out->addScript( "
<script type='text/javascript'>
				function showEditor() {
						var elm = document.getElementById('editor');
						elm.style.display = '';
						var elm = document.getElementById('upload');
						elm.style.display = 'none';
				}
				function showUpload() {
						var elm = document.getElementById('upload');
						elm.style.display = '';
						var elm = document.getElementById('editor');
						elm.style.display = 'none';
				}

</script>

		" );
	}
}
