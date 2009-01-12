<?php
/*
Plugin Name: mediawiki2wordpress
Plugin URI: http://auzigog.com
Description: Allows link between MediaWiki and Wordpress.
Version: 0.3
Author: Jeremy Blanchard
Author URI: http://auzigog.com
*/



// TODO: turn these into options
define(MW2WP_ALLOW_SHORTCODES_IN_WIKI, true);
define(MW2WP_ALLOW_CONTENT_FILTERS_IN_WIKI, true);
define(MW2WP_USE_CLI, true);
define(MW2WP_MEDIAWIKI_PATH, '/Users/eyeRmonkey/www/mediawiki-test');

function mw2wp_debug($var = null) {
	echo '<pre>';
	if($var) {
		print_r($var);
	} else {
		print_r(debug_backtrace());
	}
	echo '</pre>';
}

function mw2wp_flush_rewrite_rules() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

function mw2wp_add_rewrite_rules($wp_rewrite) {
	// Redirect anything under the wiki "subdirectory" to the wiki page.
	$rewrite_condition = 'wiki/(.*)';
	$rewrite_rule = 'index.php?pagename=wiki&path=' . $wp_rewrite->preg_index(1);
	
	$new_rules = array($rewrite_condition => $rewrite_rule);

	$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
}

/**
 * Handel the [mediawiki2wordpress] shortcode tag
 * 
 * @param <type> $content 
 */
function mw2wp_shortcode_handler($params) {
	$allowed_params = array(
			'foo' => 'foo default'
		);
	$shortcode_params = shortcode_atts($allowed_params, $params);

	// The output buffer allows us to echo things instead of storing everything to a vairable
	ob_start();

	// Do the work!
	mw2wp_run($shortcode_params);
	$wiki_content = ob_get_clean();

	// Process any wordpress
	if(MW2WP_ALLOW_SHORTCODES_IN_WIKI)
		do_shortcode($wiki_content);
	if(MW2WP_ALLOW_CONTENT_FILTERS_IN_WIKI)
		apply_filters('the_content', $wiki_content);

	return $wiki_content;
}


/**
 * Generates the output for the [mediawiki2wordpress] shortcode tag
 */
function mw2wp_run($shortcode_params) {

	$api_params = array(
			'action' => 'parse',
			'title' => 'Main_Page',
			'text' => '{{:Main_Page}}',
			'format' => 'php'
		);
	
	$api_response = mw2wp_mediawiki_api_call($api_params);

	echo $api_response['parse']['text']['*'];
}

/**
 * Get's the data from the API either through the command line (quicker, but doesn't work
 * on certain servers -- especially shared hosts) or over HTTP (slower because it happens
 * over HTTP).
 */
function mw2wp_mediawiki_api_call($params) {

	$api_response = null;
	if(MW2WP_USE_CLI) {
		$api_response = mw2wp_mediawiki_api_cli($params);
	} else {
		// TODO: get an HTTP version working using curl or file_get_contents
	}

	if(!empty($api_response)) {
		return $api_response;
	} else {
		// TODO: better error handeling
		die('API was empty! :(');
	}


}

/**
 * Uses exec() to get the wiki content over the command line. This is better than the file_get_contents() method that involves
 * an HTTP request. exec() doesn't work with wikis that are on a separate server or if your server
 * is running PHP in safe_mode.
 *
 * This approach is necessary (instead of just using include() ) because mediawiki's framework clashes with
 * wordpress's in many ways. Both try to register __autoload functions, for example. So mediawiki must be run
 * in a separent environment from wordpress.
 */
function mw2wp_mediawiki_api_cli($params) {
	// Get the params ready for the command line
//	$cli_args = "";
//	foreach($params as $key=>$val) {
//		$cli_args .= "--$key=\"$val\" ";
//	}

	// Be safe and base64 the params array before sending it over the command line
	$cli_args =  '-p '.base64_encode(serialize($params));
	$cli_args .= ' -d "'.MW2WP_MEDIAWIKI_PATH.'"';
	
	$cli_path = dirname(__FILE__) . '/MediaWikiCLI.php';

	$command = "php $cli_path $cli_args";
	$api_complete_output = array();
	$api_response_string = exec("php $cli_path $cli_args", $api_complete_output);

	$api_reponse = mw2wp_mediawiki_api_cli_decode($api_response_string);
	return $api_reponse;
}

/**
 * The CLI output is encoded to ensure maximum compatibility between systems
 */
function mw2wp_mediawiki_api_cli_decode($result) {
	$string = trim(base64_decode(trim($result)));
	$arr = unserialize($string);
	return $arr;
}




// Add rewrite rules
add_action('init', 'mw2wp_flush_rewrite_rules');
add_action('generate_rewrite_rules', 'mw2wp_add_rewrite_rules');

// Add shortcode tag
add_shortcode('mediawiki2wordpress', 'mw2wp_shortcode_handler');


?>
