<?php



/**
 * 
 * @author auzigog
 */
class MediaWikiAPIWrapper {
    public function __construct() {
		$mediawiki_root = '/Users/eyeRmonkey/www/mediawiki-test';s
		$processor = $this->api_init($mediawiki_root);
		
		/* Construct an ApiMain with the arguments passed via the URL. What we get back
		 * is some form of an ApiMain, possibly even one that produces an error message,
		 * but we don't care here, as that is handled by the ctor.
		 */
		$processor = new ApiMain($wgRequest, $wgEnableWriteAPI);

		// Process data & print results
		$processor->execute();
	}


	/**
	 * Modified version of api.php from mediawiki 1.13.3
	 */
	public function api_init($mediawiki_root) {

		// Initialise common code
		require (dirname(__FILE__) . '/includes/WebStart.php');
		
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

	}

	function api_kill() {
		// Execute any deferred updates
		wfDoUpdates();

		// Log what the user did, for book-keeping purposes.
		wfProfileOut('api.php');
		wfLogProfilingData();

		// Shut down the database
		wfGetLBFactory()->shutdown();
	}
}
?>
