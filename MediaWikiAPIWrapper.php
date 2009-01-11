<?php


$mediawiki_root = '/Users/eyeRmonkey/www/mediawiki-test';
putenv("MW_INSTALL_PATH=$mediawiki_root");
// Initialise common code
require ($mediawiki_root . '/includes/WebStart.php');


/**
 * 
 * @author auzigog
 */
class MediaWikiAPIWrapper {
	public $mediawiki_root;

    public function __construct() {
		wfProfileIn('api.php');
	}

	/**
	 *
	 * @param <type> $data the array of keys and values that would have appeared in the URL if this were a normal request. See API documentation
	 * @return <type>
	 */
	public function make_fake_request($params) {
		$request = new FauxRequest($params, true);

		$api = new ApiMain($request);

		// Process data & use an output buffer to capture the resutls
		$api->execute();
		$result = $api->getResult();
		$data = &$result->getData();

		return $data;
	}

	public function __destruct() {
		$this->api_kill();
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
