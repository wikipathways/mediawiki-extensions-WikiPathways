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
 * @author Mark A. Hershberger
 */

# Passwords and secrets and such
require_once "$IP/../pass.php";

wfLoadExtensions( [
	"Cite",
	"ConfirmEdit",
	"ConfirmEdit/QuestyCaptcha",
	"EmbedVideo",
	"Gadgets",
	"ImageMap",
	"InputBox",
	"Interwiki",
	"LabeledSectionTransclusion",
	"MostEditedPathways",
	"Nuke",
	"ParserFunctions",
	"PopularPathways",
	"Renameuser",
	"RSS",
	"SyntaxHighlight_GeSHi",
	"TitleBlacklist",
	"UserLoginLog",
	"UserSnoop",
	"WikiEditor",
	"WikiPathways"
] );
require_once "$IP/extensions/ContributionScores/ContributionScores.php";
require_once "$IP/extensions/googleAnalytics/googleAnalytics.php";
require_once "$IP/extensions/UserMerge/UserMerge.php";
require_once "$IP/extensions/BiblioPlus/BiblioPlus.php";

wfLoadSkin( "Vector" );

require_once "globals.php";
require_once "wpi.php";
require_once "siteStats.php";
require_once "imageSize.php";
require_once "NewPathwaysPage/NewPathwaysPage.php";
require_once "CreatePathwayPage/CreatePathwayPage.php";
require_once "LabeledSectionTransclusion/compat.php";
require_once "LabeledSectionTransclusion/lst.php";
require_once "LabeledSectionTransclusion/lsth.php";
require_once "SearchPathways/SearchPathways.php";
require_once "SearchPathways/searchPathwaysBox.php";
require_once "button.php";
require_once "imageLink.php";
require_once "listPathways.php";
require_once "movePathway.php";
require_once "deletePathway.php";
require_once "SpecialWishList/SpecialWishList.php";
require_once "SpecialWishList/TopWishes.php";
require_once "DiffAppletPage/DiffAppletPage.php";
require_once "DiffViewer/DiffViewer.php";
require_once "CheckGpmlOnSave.php";
require_once "CreateUserPage.php";
require_once "AuthorInfo/AuthorInfo.php";
require_once "CurationTags/SpecialCurationTags/SpecialCurationTags.php";
require_once "ShowError/ShowError.php";
require_once "pathwayParserFunctions.php";
require_once "PrivatePathways/PrivatePathways.php";
require_once "PrivatePathways/ListPrivatePathways.php";
require_once "PrivatePathways/PrivateContributions.php";
require_once "otag/otags_main.php";
require_once "ontologyindex/ontologyindex.php";
require_once "StubManager/StubManager.php";
require_once "SecureHTML/SecureHTML.php";
require_once "PageEditor/PageEditor.php";
require_once "PullPages/PullPages.php";
require_once "search.php";
require_once "TissueAnalyzer/TissueAnalyzer.php";

$wgCaptchaClass = 'QuestyCaptcha';

// Set to true if you want to exclude Bots from the reporting - Can be omitted.
$contribScoreIgnoreBots = true;

// Each array defines a report - 7,50 is "past 7 days" and "LIMIT 50" - Can be omitted.
$contribScoreReports = [
	[ 7, 50 ],
	[ 30, 50 ],
	[ 0, 50 ]
];

/* Biblio extension
Isbndb account: thomas.kelder@bigcat.unimaas.nl / BigC0w~wiki
*/
$isbndb_access_key = 'BR5539IJ';

// Interwiki extension
$wgGroupPermissions['*']['interwiki'] = false;
$wgGroupPermissions['sysop']['interwiki'] = true;

// UserMerge settings
$wgGroupPermissions['bureaucrat']['usermerge'] = true;

// Google analytics settings (key should be in pass.php)
$wgGoogleAnalyticsIgnoreSysops = false;

// Set enotif for watch page changes to true by default
$wgDefaultUserOptions ['enotifwatchlistpages'] = 1;

# Cascading Style Sheets
# Default is {$wgScriptPath}/skins

$wgShowExceptionDetails = true;
$wgShowSQLErrors = true;

$wgReadOnlyFile = "readonly.enable";

// Increase recent changes retention time
$wgRCMaxAge = 60 * 24 * 3600;

// JS Type http://developers.pathvisio.org/ticket/1567
$wgJsMimeType = "text/javascript";

/* Users have to have a confirmed email address to edit.  This also
 * requires a valid email at account creation time. */
$wgEmailConfirmToEdit = true;

/* This section allows you to set wgEmailConfirmToEdit to fals (so
 * that an email isn't required to create an account) but still
 * require a confirmed email before the user can edit. */
# Disable for everyone.
$wgGroupPermissions['*']['edit'] = false;
# Disable for users, too: by default 'user' is allowed to edit, even if '*' is not.
$wgGroupPermissions['user']['edit'] = false;
# Make it so users with confirmed e-mail addresses are in the group.
$wgAutopromote['confirmed'] = APCOND_EMAILCONFIRMED;
# Hide group from user list.
$wgImplicitGroups[] = 'confirmed';
# Finally, set it to true for the desired group.
$wgGroupPermissions['confirmed']['edit'] = true;

$ceAllowConfirmedEmail = false;

/* Turn on CAPTCHA for editing and page creation by setting these to true */
$wgCaptchaTriggers['edit'] = false;
$wgCaptchaTriggers['create'] = false;

/* In case you ever to turn on the CAPTCHA for editing, you will
 * probably want to let privleged users skip them */
$wgGroupPermissions[ 'sysop'      ][ 'skipcaptcha'    ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'skipcaptcha'    ] = true;

$wgGroupPermissions[ 'curator'    ][ 'skipcaptcha'    ] = true;

$wgGroupPermissions[ 'curator'    ][ 'autocurate'     ] = true;

// If a pathway has been editted within this number of days, it will
// be highlighted on the browse page
$wgPathwayRecentSinceDays = 30;

# Set to the title of a wiki page that describes your license/copyright
$wgRightsPage = "WikiPathways:License_Terms";
$wgRightsUrl = "https://creativecommons.org/publicdomain/zero/1.0/";
$wgRightsText = "our terms of use";
$wgRightsIcon = "https://licensebuttons.net/p/zero/1.0/88x31.png";
# $wgRightsCode = ""; # Not yet used

$wgDiff3 = "/usr/bin/diff3";

# When you make changes to this configuration file, this will make
# sure that cached pages are cleared.
$configdate = gmdate( 'YmdHis', filemtime( __FILE__ ) );
$wgCacheEpoch = max( $wgCacheEpoch, $configdate );

$wgGroupPermissions['autoconfirmed']['autoconfirmed'] = false;

$wgGroupPermissions['*'    ]['createaccount'] = true;

// Disable read for all users, this will be handled by the private pathways extension
// $wgGroupPermissions['*'    ]['read']            = true;

$wgGroupPermissions['*'    ]['edit'] = false;
$wgGroupPermissions['*'    ]['createpage'] = false;
$wgGroupPermissions['*'    ]['createtalk'] = false;

# Non-defaults:

# Allow slow parser functions ({{PAGESINNS:ns}})
$wgAllowSlowParserFunctions = true;

# Logo
$wgLogo = "http://www.wikipathways.org/skins/common/images/earth-or-pathway_text3_beta.png";

# Allow gpml extension and larger image files
$wgFileExtensions = [ 'pdf', 'png', 'gif', 'jpg', 'jpeg', 'svg', 'gpml', 'mapp' ];
$wgUploadSizeWarning = 1024 * 1024 * 5;

## Better SVG converter
/** Pick one of the above */
$wgSVGConverter = 'inkscape';
$wgSVGConverters['inkscape'] = '$path/inkscape -z -b white -w $width -f $input -e $output';

# $wgMimeDetectorCommand = "file -bi"; #This doesn't work for svg!!!
# $wgCheckFileExtensions = false;

# Allow direct linking to external images (so we don't have to upload them to the wiki)
$wgAllowExternalImages = true;

# Ontology data

# Ontologies in JSON format for use in the Javascript
# Format : ["<Ontology Name>", <Ontology Id>, <Version Id>]
$wgOntologiesJSON = '[' . '["Pathway Ontology","PW:0000001",1035,46237,"PW"]'
				  . ',' . '["Disease","DOID:4",1009,46309,"DOID"]' . ','
				  . '["Cell Type","CL:0000000",1006,46163,"CL"]]';
# Ontologies Array to be used in the PHP Code
$wgOntologiesArray = json_decode( $wgOntologiesJSON );

# Email address for the User Identification parameter to be used while making
# REST calls to BioPortal
$wgOntologiesBioPortalEmail = "apico@gladstone.ucsf.edu";

# Maximum number of search results returned while searching BioPortal
$wgOntologiesBioPortalSearchHits = 12;
# Time after which data in the cache is refreshed (in Seconds)
$wgOntologiesExpiryTime = 3600 * 24 * 7;

# Custom namespaces
// NS_PATHWAY is same as NS_GPML since refactoring
define( "NS_PATHWAY", 102 );
define( "NS_PATHWAY_TALK", 103 );
define( "NS_GPML", 102 );
define( "NS_GPML_TALK", 103 );
define( "NS_WISHLIST", 104 );
define( "NS_WISHLIST_TALK", 105 );
define( "NS_PORTAL", 106 );
define( "NS_PORTAL_TALK", 107 );
define( "NS_QUESTION", 108 );
define( "NS_QUESTION_TALK", 109 );

$wgExtraNamespaces[100]              = "Pw_Old";
$wgExtraNamespaces[101]              = "Pw_Old_Talk";
$wgExtraNamespaces[NS_PATHWAY]       = "Pathway";
$wgExtraNamespaces[NS_PATHWAY_TALK]  = "Pathway_Talk";
$wgExtraNamespaces[NS_WISHLIST]      = "Wishlist";
$wgExtraNamespaces[NS_WISHLIST_TALK] = "Wishlist_Talk";
$wgExtraNamespaces[NS_PORTAL]        = "Portal";
$wgExtraNamespaces[NS_PORTAL_TALK]   = "Portal_Talk";

$wgNamespacesToBeSearchedDefault[100] = false;
$wgNamespacesToBeSearchedDefault[101] = false;
$wgNamespacesToBeSearchedDefault[NS_PATHWAY]      = true;
$wgNamespacesToBeSearchedDefault[NS_PATHWAY_TALK] = true;

$wgContentNamespaces += [ NS_PATHWAY, NS_PATHWAY_TALK ];

# Protecting non-pathway namespaces from user edits
$wgNamespaceProtection[NS_HELP]          = [ 'help-edit' ];
$wgNamespaceProtection[NS_HELP_TALK]     = [ 'help-talk-edit' ];
$wgNamespaceProtection[NS_PATHWAY]       = [ 'pathway-edit' ];
$wgNamespaceProtection[NS_PATHWAY_TALK]  = [ 'pathway-talk-edit' ];
$wgNamespaceProtection[NS_WISHLIST]      = [ 'wishlist-edit' ];
$wgNamespaceProtection[NS_WISHLIST_TALK] = [ 'wishlist-talk-edit' ];
$wgNamespaceProtection[NS_PORTAL]        = [ 'portal-edit' ];
$wgNamespaceProtection[NS_PORTAL_TALK]   = [ 'portal-tlk-edt' ];

$wgGroupPermissions[ '*'          ][ 'read'                  ] = true;
$wgGroupPermissions[ '*'          ][ 'edit'                  ] = false;
$wgGroupPermissions[ '*'          ][ 'createpage'            ] = false;
$wgGroupPermissions[ '*'          ][ 'createtalk'            ] = false;
$wgGroupPermissions[ '*'          ][ 'move'                  ] = false;
$wgGroupPermissions[ '*'          ][ 'delete'                ] = false;

$wgGroupPermissions[ 'user'       ][ 'read'                  ] = true;
$wgGroupPermissions[ 'user'       ][ 'edit'                  ] = false;
$wgGroupPermissions[ 'user'       ][ 'createpage'            ] = false;
$wgGroupPermissions[ 'user'       ][ 'createtalk'            ] = false;
$wgGroupPermissions[ 'user'       ][ 'upload'                ] = false;
$wgGroupPermissions[ 'user'       ][ 'reupload'              ] = false;
$wgGroupPermissions[ 'user'       ][ 'reupload-shared'       ] = false;
$wgGroupPermissions[ 'user'       ][ 'minoredit'             ] = false;
$wgGroupPermissions[ 'user'       ][ 'move'                  ] = false;
$wgGroupPermissions[ 'user'       ][ 'move-subpages'         ] = false;
$wgGroupPermissions[ 'user'       ][ 'delete'                ] = false;

$wgGroupPermissions[ 'confirmed'  ][ 'read'                  ] = true;
$wgGroupPermissions[ 'confirmed'  ][ 'edit'                  ] = true;
$wgGroupPermissions[ 'confirmed'  ][ 'createpage'            ] = true;
$wgGroupPermissions[ 'confirmed'  ][ 'createtalk'            ] = true;
$wgGroupPermissions[ 'confirmed'  ][ 'createpathway'         ] = true;
$wgGroupPermissions[ 'confirmed'  ][ 'upload'                ] = true;
$wgGroupPermissions[ 'confirmed'  ][ 'reupload'              ] = true;
$wgGroupPermissions[ 'confirmed'  ][ 'reupload-shared'       ] = true;
$wgGroupPermissions[ 'confirmed'  ][ 'move'                  ] = false;
$wgGroupPermissions[ 'confirmed'  ][ 'move-subpages'         ] = true;
$wgGroupPermissions[ 'confirmed'  ][ 'delete'                ] = false;
$wgGroupPermissions[ 'confirmed'  ][ 'pathway-edit'          ] = true;
$wgGroupPermissions[ 'confirmed'  ][ 'pathway-talk-edit'     ] = true;
$wgGroupPermissions[ 'confirmed'  ][ 'wishlist-edit'         ] = true;
$wgGroupPermissions[ 'confirmed'  ][ 'wishlist-talk-edit'    ] = true;

$wgGroupPermissions[ 'bureaucrat' ][ 'read'                  ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'edit'                  ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'createtalk'            ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'createpage'            ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'move'                  ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'move-subpages'         ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'upload'                ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'reupload'              ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'reupload-shared'       ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'delete'                ] = false;
$wgGroupPermissions[ 'bureaucrat' ][ 'main-edit'             ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'main-talk-edit'        ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'help-edit'             ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'help-talk-edit'        ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'portal-edit'           ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'portal-tlk-edt'        ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'pathway-edit'          ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'pathway-talk-edit'     ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'wishlist-edit'         ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'wishlist-talk-edit'    ] = true;

$wgGroupPermissions[ 'sysop'      ][ 'read'                  ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'edit'                  ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'createtalk'            ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'createpage'            ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'move'                  ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'move-subpages'         ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'upload'                ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'reupload'              ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'delete'                ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'main-edit'             ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'main-talk-edit'        ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'help-edit'             ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'help-talk-edit'        ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'portal-edit'           ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'portal-tlk-edt'        ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'pathway-edit'          ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'pathway-talk-edit'     ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'wishlist-edit'         ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'wishlist-talk-edit'    ] = true;

$wgGroupPermissions[ 'usersnoop'  ][ 'usersnoop'             ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'usersnoop'             ] = true;
$wgGroupPermissions[ 'bureaucrat' ][ 'usersnoop'             ] = true;
$wgGroupPermissions[ 'sysop'      ][ 'list_private_pathways' ] = true;
$wgGroupPermissions[ 'webservice' ][ 'webservice_write'      ] = true;
$wgGroupPermissions[ 'portal'     ][ 'portal-edit'           ] = true;
$wgGroupPermissions[ 'portal'     ][ 'portal-tlk-edt'        ] = true;

$wgHooks['AbortNewAccount'][] = 'abortOnBadDomain';

$wgEnableEmail      = true;
$wgEnableUserEmail  = true;

$wgEmergencyContact = "wikipathways@gladstone.ucsf.edu";
$wgPasswordSender = "no-reply@wikipathways.com";

$wgContentHandlerTextFallback = 'serialize';
$wgNamespaceContentModels[NS_PATHWAY]     = CONTENT_MODEL_PATHWAY;
$wgContentHandlers[CONTENT_MODEL_PATHWAY] = 'WikiPathways\\PathwayHandler';

if ( !isset( $wpiJavascriptSnippets ) ) {
	$wpiJavascriptSnippets = [];
}
if ( !isset( $wpiJavascriptSources ) ) {
	$wpiJavascriptSources = [];
}
if ( !isset( $jsJQuery ) ) {
	$jsJQuery = "$wgScriptPath/skins/wikipathways/jquery-1.8.3.min.js";
}
if ( !isset( $jsJQueryUI ) ) {
	$jsJQueryUI = "$wgScriptPath/wpi/js/jquery-ui/jquery-ui-1.8.10.custom.min.js";
}
if ( !isset( $cssJQueryUI ) ) {
	$cssJQueryUI = "$wgScriptPath/wpi/js/jquery-ui/jquery-ui-1.8.10.custom.css";
}
if ( !isset( $jsSvgWeb ) ) {
	$jsSvgWeb = "$wgScriptPath/wpi/js/svgweb/svg-uncompressed.js\""
			  . "data-path=\"$wgScriptPath/wpi/js/svgweb";
}
// Only load jquery when required by extension
$jsRequireJQuery = false;

// Disable email on test server
$wgEnableEmail = true;
$wgEnableUserEmail = false;
$wgEnotifUserTalk = false;
$wgEnotifWatchlist = false;

// enable ontology tags on pathway page
$wpiEnableOtag = true;

// Enable RSS feeds from front page
$wgRSSUrlWhitelist = [
	"https://wikipathways.github.io/academy/curators/curators_list.xml",
	"https://groups.google.com/forum/feed/wikipathways-discuss/msgs/rss_v2_0.xml"
];
