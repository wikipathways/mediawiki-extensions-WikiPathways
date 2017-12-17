<?php
global $wgScriptPath;

// File types
define( "FILETYPE_IMG", "svg" );
define( "FILETYPE_GPML", "gpml" );
define( "FILETYPE_MAPP", "mapp" );
define( "FILETYPE_PNG", "png" );
define( "FILETYPE_PDF", "pdf" );
define( "FILETYPE_PWF", "pwf" );
define( "FILETYPE_TXT", "txt" );
define( "FILETYPE_BIOPAX", "owl" );

// pathname containing wpi script
$wpiPathName = '/extensions/WikiPathways';

// temp path name
$wpiTmpName = 'tmp';

// cache path name
$wpiCacheName = 'cache';

$wpiScriptFile = 'wpi.php';
$wpiModulePath = "$wgScriptPath/extensions/WikiPathways/modules";
$wpiScriptPath = realpath( __DIR__ );
$wpiScript = "$wpiScriptPath/$wpiScriptFile";
$wpiTmpPath = "$wpiScriptPath/$wpiTmpName";

$host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : "www.wikipathways.org";
$siteURL = "//$host";

$wpiURL = "$siteURL$wpiPathName";
$wpiCachePath = "$wpiScriptPath/$wpiCacheName";

define( "WPI_SCRIPT_PATH", $wpiScriptPath );
define( "WPI_SCRIPT", $wpiScript );
define( "WPI_TMP_PATH", $wpiTmpPath );
define( "SITE_URL", $siteURL );
define( "WPI_URL",  $wpiURL );
define( "WPI_SCRIPT_URL", WPI_URL . '/' . $wpiScriptFile );
define( "WPI_TMP_URL", WPI_URL . '/' . $wpiTmpName );
define( "WPI_CACHE_PATH", $wpiCachePath );
define( "WPI_CACHE_URL", WPI_URL . '/' . $wpiCacheName );

// JS info
define( "JS_SRC_EDITAPPLET", $wgScriptPath . "/wpi/js/editapplet.js" );
define( "JS_SRC_RESIZE", $wgScriptPath . "/wpi/js/resize.js" );
define( "JS_SRC_PROTOTYPE", $wgScriptPath . "/wpi/js/prototype.js" );

// User account for maintenance scripts
define( "USER_MAINT_BOT", "MaintBot" );

// WikiPathways data
define( 'COMMENT_WP_CATEGORY', 'WikiPathways-category' );
define( 'COMMENT_WP_DESCRIPTION', 'WikiPathways-description' );
