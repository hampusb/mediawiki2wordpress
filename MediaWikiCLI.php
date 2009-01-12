<?php
/**
 * This file provides an interface to the MediaWikiAPIWrapper through the PHP
 * command line interface (CLI).
 */


// Get the CLI arguments
$cli_args = mw2wp_parse_cli_args();
$api_params = $cli_args['params'];
$mediawiki_root = $cli_args['mediawiki_root'];


// Load up the code
require_once('MediaWikiAPIWrapper.php');
$api = new MediaWikiAPIWrapper();

// Actually make the request to the API
// TODO: Add output buffering here incase of errors
$response_arr = $api->make_fake_request($api_params);

// Output the serialized array. Needs to be the last line of output. Base64 encode it so no encoding issues occur.
$cli_result = mw2wp_encode_cli_response($response_arr);
echo $cli_result;


function mw2wp_encode_cli_response($response_arr) {
	$seriallized = serialize($response_arr);
	$encoded = base64_encode($seriallized);
	return $encoded;
}

function mw2wp_parse_cli_args() {
	// reference: http://us2.php.net/getopt
	$args = getopt('p:d:');

	$params_arg = $args['p'];
	$mediawiki_directory_arg = $args['d'];

	// Base64 decode the "params" argument. It is encoded to ensure nothing odd happens when sending
	//	it over the command line
	$params_string = trim(base64_decode(trim($params_arg)));
	$params = unserialize($params_string);

	// Get the mediawiki path
	$mediawiki_root = $mediawiki_directory_arg;

	$result = array(
			'params' => $params,
			'mediawiki_root' => $mediawiki_root,
		);
	return $result;
}

?>