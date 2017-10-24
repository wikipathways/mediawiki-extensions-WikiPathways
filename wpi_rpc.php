<?php

error_reporting( E_ERROR ); // Supress warnings etc...will disrupt the rpc response

// Load XML-RCP libraries
require "includes/xmlrpc.inc";
require "includes/xmlrpcs.inc";
require "includes/docxmlrpcs.inc";

// Load WikiPathways Interface


// Definition of functions
$updatePathway_sig = [
	[
		$xmlrpcInt,
		$xmlrpcString, $xmlrpcString, $xmlrpcBase64,
		$xmlrpcInt
	],
	[
		$xmlrpcBoolean,
		$xmlrpcString, $xmlrpcString, $xmlrpcBase64,
		$xmlrpcInt, $xmlrpcStruct
	],
];

$updatePathway_doc = "Update a pathway on wikipathways.";

$updatePathway_docsig = [
	[
		"The newest revision number when the update was successful, 0 otherwise",
		"The pathway name (e.g. Apoptosis)",
		"The pathway species (e.g. Human)",
		"Description of the modifications",
		"The updated GPML data (base64 encoded)",
		"The revision id on which the updated GPML is based"
	],
	[
		"The newest revision number when the update was successful, 0 otherwise",
		"The pathway identifier",
		"Description of the modifications",
		"The updated GPML data (base64 encoded)",
		"The revision id on which the updated GPML is based",
		"The authentication data, a struct with the key/value pairs:" .
		"<BR>'user', the username<BR>'token', the authentication token"
	]
];

$createPathway_sig = [
	[
		$xmlrpcStruct,
		$xmlrpcString, $xmlrpcBase64,
	],
	[
		$xmlrpcStruct,
		$xmlrpcString, $xmlrpcBase64, $xmlrpcBoolean,
	],
	[
		$xmlrpcStruct,
		$xmlrpcString, $xmlrpcBase64, $xmlrpcBoolean,
		$xmlrpcStruct
	],
];

$createPathway_doc = "Create a new pathway on wikipathways.";

$createPathway_docsig = [
	[
		"A struct with the key/value pairs:" .
		"<BR>'id', the pathway id<BR>'revision', the newest revision number<BR>" .
		"'url', the url to the pathway page",
		"Description of the modifications",
		"The GPML data (base64 encoded)",
	],
	[
		"A struct with the key/value pairs:" .
		"<BR>'id', the pathway id<BR>'revision', the newest revision number<BR>" .
		"'url', the url to the pathway page",
		"Description of the modifications",
		"The GPML data (base64 encoded)",
		"Boolean indicated whether the pathway should be private or not",
	],
	[
		"A struct with the key/value pairs:" .
		"<BR>'id', the pathway id<BR>'revision', the newest revision number<BR>" .
		"'url', the url to the pathway page",
		"Description of the modifications",
		"The GPML data (base64 encoded)",
		"Boolean indicated whether the pathway should be private or not",
		"The authentication data, a struct with the key/value pairs:" .
		"<BR>'user', the username<BR>'token', the authentication token"
	]
];

$convertPathway_sig = [
	[
		$xmlrpcBase64,
		$xmlrpcBase64, $xmlrpcString
	]
];

$convertPathway_doc = "Convert GPML code to the given file format";

$convertPathway_docsig = [
	[
		"The converted file data (base64 encoded)",
		"The GPML code to convert (base64 encoded)",
		"The file extension to convert to (e.g. svg)",
	]
];

$getPathway_sig = [
	[
		$xmlrpcStruct,
		$xmlrpcString ],
	[
		$xmlrpcStruct,
		$xmlrpcString, $xmlrpcInt ],
];

$getPathway_doc = "Get the GPML code for a pathway";

$getPathway_docsig = [
	[
		"A struct containing the following key/value pairs:<dl>" .
		"<dt>gpml<dd>The GPML code (base64 encoded)" .
		"<dt>revision<dd>The revision id of the returned GPML",
		"The pathway identifier"
	],
	[
		"A struct containing the following key/value pairs:<dl>" .
		"<dt>gpml<dd>The GPML code (base64 encoded)" .
		"<dt>revision<dd>The revision id of the returned GPML",
		"The pathway identifier",
		"The revision id (use '0' for current revision)"
	]
];

$login_sig = [
	[
		$xmlrpcString,
		$xmlrpcString, $xmlrpcString
	]
];

$login_doc = "Start a logged in session, using an existing WikiPathways account.
This will return an authentication code
that can be used to excecute methods that need authentication (e.g.
updatePathway)";

$login_docsig = [
	[
		"The authentication code",
		"The user name",
		"The password"
	]
];

$getPathwayList_sig = [
	[
		$xmlrpcArray
	]
];

$getPathwayList_doc = "Get a list of all pathway titles (in the form of Species:PathwayName)";

$getPathwayList_docsig = [
	[
		"An array containing all pathway titles"
	]
];

$getRecentChanges_sig = [
	[
		$xmlrpcArray,
		$xmlrpcString
	]
];

$getRecentChanges_doc = "Get a list of recently changed pathways";

$getRecentChanges_docsig = [
	[
		"a list of names of recently changed pathways",
		"a SQL timestamp cutoff"
	]
];

// Definition of dispatch map
$disp_map = [
	"WikiPathways.updatePathway" =>
	[ "function" => "updatePathway",
		"signature" => $updatePathway_sig,
		"docstring" => $updatePathway_doc,
		"signature_docs" => $updatePathway_docsig ],
	"WikiPathways.createPathway" =>
	[ "function" => "createPathway",
		"signature" => $createPathway_sig,
		"docstring" => $createPathway_doc,
		"signature_docs" => $createPathway_docsig ],
	"WikiPathways.convertPathway" =>
	[ "function" => "convertPathway",
		"signature" => $convertPathway_sig,
		"docstring" => $convertPathway_doc,
		"signature_docs" => $convertPathway_docsig ],
	"WikiPathways.getPathway" =>
	[ "function" => "getPathway",
		"signature" => $getPathway_sig,
		"docstring" => $getPathway_doc,
		"signature_docs" => $getPathway_docsig ],
	"WikiPathways.login" =>
	[ "function" => "login",
		"signature" => $login_sig,
		"docstring" => $login_doc,
		"signature_docs" => $login_docsig ],
	"WikiPathways.getPathwayList" =>
	[ "function" => "getPathwayList",
		"signature" => $getPathwayList_sig,
		"docstring" => $getPathwayList_doc,
		"signature_docs" => $getPathwayList_docsig ],
	"WikiPathways.getRecentChanges" =>
	[ "function" => "getRecentChanges",
		"signature" => $getRecentChanges_sig,
		"docstring" => $getRecentChanges_doc,
		"signature_docs" => $getRecentChanges_docsig ],
];

// Setup the XML-RPC server
$s = new documenting_xmlrpc_server( $disp_map, 0 );
$s->functions_parameters_type = 'phpvals';
// $s->setDebug(3);
$s->service();

// Function implementations
function getPathwayList() {
	$pathways = Pathway::getAllPathways();
	$titles = [];
	foreach ( $pathways as $p ) {
		$titles[] = $p->getTitleObject()->getDbKey();
	}
	return $titles;
}

function updatePathway( $id, $description, $gpmlData, $revision, $auth = null ) {
	global $xmlrpcerruser, $wgUser;

	// Authenticate first, if token is provided
	if ( $auth ) {
		try {
			authenticate( $auth['user'], $auth['token'] );
		} catch ( Exception $e ) {
			return new xmlrpcresp( 0, $xmlrpcerruser, $e );
		}
	}

	$resp = 0;

	try {
		$pathway = new Pathway( $id );
		// Only update if the given revision is the newest
		// Or if this is a new pathway
		if ( !$pathway->exists() || $revision == $pathway->getLatestRevision() ) {
			$pathway->updatePathway( $gpmlData, $description );
			$resp = $pathway->getLatestRevision();
		} else {
			wfDebug( "REVISION: $revision , " . $pathway->getLatestRevision() );
			$resp = new xmlrpcresp( 0, $xmlrpcerruser,
				"Revision out of date: your GPML code originates from " .
				"an old revision. This means somebody else modified the pathway " .
				"since you downloaded it. Please apply your changes on the newest version"
			);
		}
	} catch ( Exception $e ) {
		wfDebug( "XML-RPC ERROR: $e" );
		$resp = new xmlrpcresp( 0, $xmlrpcerruser, $e );
	}
	ob_clean(); // Clean the output buffer, so nothing is printed before the xml response
	return $resp;
}

function createPathway( $description, $gpmlData, $private = false, $auth = null ) {
	global $xmlrpcerruser, $wgUser;

	// Authenticate first, if token is provided
	if ( $auth ) {
		try {
			authenticate( $auth['user'], $auth['token'] );
		} catch ( Exception $e ) {
			return new xmlrpcresp( 0, $xmlrpcerruser, $e );
		}
	}

	$resp = 0;

	try {
		$pathway = Pathway::createNewPathway( $gpmlData, $description );
		$title = $pathway->getTitleObject();
		$resp = [
			"id" => $pathway->getIdentifier(),
			"url" => $title->getFullUrl(),
			"revision" => $pathway->getLatestRevision()
		];
		if ( $private ) {
			$pathway->makePrivate( $wgUser );
		}
	} catch ( Exception $e ) {
		wfDebug( "XML-RPC ERROR: $e" );
		$resp = new xmlrpcresp( 0, $xmlrpcerruser, $e );
	}
	ob_clean(); // Clean the output buffer, so nothing is printed before the xml response
	return $resp;
}

function convertPathway( $gpmlData64, $fileType ) {
	global $xmlrpcerruser;

	$gpmlData = base64_decode( $gpmlData64 );
	$gpmlFile = tempnam( WPI_TMP_PATH, "gpml" );
	writeFile( $gpmlFile, $gpmlData );
	$imgFile = tempnam( WPI_TMP_PATH, $fileType ) . ".$fileType";
	$cmd = "cd " . WPI_SCRIPT_PATH . "; java -jar bin/pathvisio_core.jar $gpmlFile $imgFile 2>&1";
	wfDebug( $cmd );
	exec( $cmd, $output, $status );

	foreach ( $output as $line ) {
		$msg .= $line . "\n";
	}
	wfDebug( "Converting to $fileType:\nStatus:$status\nMessage:$msg" );
	if ( $status != 0 ) {
		return new xmlrpcresp( 0, $xmlrpcerruser, "Unable to convert:\nStatus:$status\nMessage:$msg" );
	}
	$imgData = file_get_contents( $imgFile );
	$imgData64 = base64_encode( $imgData );
	unlink( $gpmlFile );
	unlink( $imgFile );
	ob_clean(); // Clean the output buffer, so nothing is printed before the xml response
	return $imgData64;
}

function getPathway( $id, $revision = 0 ) {
	global $xmlrpcerruser;

	try {
		$pathway = new Pathway( $id );
		$revision = $pathway->getLatestRevision();
		$gpmlData64 = base64_encode( $pathway->getGPML() );
		ob_clean();
		return [
			"gpml" => $gpmlData64,
			"revision" => $revision
		];
	} catch ( Exception $e ) {
		wfDebug( "XML-RPC ERROR: $e" );
		$resp = new xmlrpcresp( 0, $xmlrpcerruser, $e );
	}
}

function login( $name, $pass ) {
	global $wgUser, $wgAuth, $xmlrpcerruser;

	$user = User::newFromName( $name );
	if ( is_null( $user ) || $user->getID() == 0 ) {
		return new xmlrpcresp( 0, $xmlrpcerruser, "Invalid user name" );
	}
	$user->load();
	if ( $user->checkPassword( $pass ) ) {
		$wgAuth->updateUser( $user );
		$wgUser = $user;
		return $user->mToken;
	} else {
		return new xmlrpcresp( 0, $xmlrpcerruser, "Wrong password" );
	}
}

function getRecentChanges( $timestamp ) {
	global $xmlrpcerruser;

	// check safety of $timestamp, must be exactly 14 digits and nothing else.
	if ( !preg_match( "/^\d{14}$/", $timestamp ) ) {
			return new xmlrpcresp( 0, $xmlrpcerruser, "Invalid timestamp " . htmlentities( $timestamp ) );
	}

	$dbr =& wfGetDB( DB_SLAVE );
	$forceclause = $dbr->useIndexClause( "rc_timestamp" );
	$recentchanges = $dbr->tableName( 'recentchanges' );

	$sql = "SELECT
				rc_namespace,
				rc_title,
				MAX(rc_timestamp)
			FROM $recentchanges $forceclause
			WHERE
				rc_namespace = " . NS_PATHWAY . "
				AND
				rc_timestamp > '$timestamp'
			GROUP BY rc_title
			ORDER BY rc_timestamp DESC
		";

	// ~ wfDebug ("SQL: $sql");

	$res = $dbr->query( $sql, "getRecentChanges" );

	$titles = [];
	while ( $row = $dbr->fetchRow( $res ) ) {
			$titles[] = $row['rc_title'];
	}
	return $titles;
}

// Non-rpc functions
function authenticate( $username, $token ) {
	global $wgUser, $wgAuth;

	$user = User::newFromName( $username );
	if ( is_null( $user ) || $user->getID() == 0 ) {
		throw new Exception( "Invalid user name" );
	}
	$user->load();
	if ( $user->mToken == $token ) {
		$wgAuth->updateUser( $user );
		$wgUser = $user;
	} else {
		throw new Exception( "Wrong authentication token" );
	}
}
