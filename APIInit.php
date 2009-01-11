<?php
/**
 * This class must be included directly so the various global variables stay global.
 *
 * B
 */

// Trick MW into thinking we are running this from the root directory.
$mediawiki_root = '/Users/eyeRmonkey/www/mediawiki-test';
putenv("MW_INSTALL_PATH=$mediawiki_root");

// Initialise common code
require ($mediawiki_root . '/includes/WebStart.php');

wfProfileIn('api.php');

// URL safety checks
//
// See RawPage.php for details; summary is that MSIE can override the
// Content-Type if it sees a recognized extension on the URL, such as
// might be appended via PATH_INFO after 'api.php'.
//
// Some data formats can end up containing unfiltered user-provided data
// which will end up triggering HTML detection and execution, hence
// XSS injection and all that entails.
//
// Ensure that all access is through the canonical entry point...
//
if( isset( $_SERVER['SCRIPT_URL'] ) ) {
	$url = $_SERVER['SCRIPT_URL'];
} else {
	$url = $_SERVER['PHP_SELF'];
}
if( strcmp( "$wgScriptPath/api$wgScriptExtension", $url ) ) {
	wfHttpError( 403, 'Forbidden',
		'API must be accessed through the primary script entry point.' );
	return;
}

// Verify that the API has not been disabled
if (!$wgEnableAPI) {
	echo 'MediaWiki API is not enabled for this site. Add the following line to your LocalSettings.php';
	echo '<pre><b>$wgEnableAPI=true;</b></pre>';
	die(-1);
}

?>