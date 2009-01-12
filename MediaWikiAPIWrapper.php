<?php

putenv("MW_INSTALL_PATH=$mediawiki_root");
// Initialise common code
require ($mediawiki_root . '/includes/WebStart.php');


/**
 * 
 * @author auzigog
 */
class MediaWikiAPIWrapper {
	/**
	 *
	 * @param <type> $params the array of keys and values that would have appeared in the URL if this were a normal request. See API documentation
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
}
?>
