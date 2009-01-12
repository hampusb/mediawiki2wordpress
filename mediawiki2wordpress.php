<?php
/*
Plugin Name: mediawiki2wordpress
Plugin URI: http://auzigog.com
Description: Allows link between MediaWiki and Wordpress.
Version: 0.3
Author: Jeremy Blanchard
Author URI: http://auzigog.com
*/

//error_reporting(E_ALL);
//require_once('MediaWikiAPIWrapper.php');
echo 'HEREEEEEEEEEEEEEEE!';

// TODO: turn these into options
define(MW2WP_ALLOW_SHORTCODES_IN_WIKI, true);
define(MW2WP_ALLOW_CONTENT_FILTERS_IN_WIKI, true);


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
	$final_params = shortcode_atts($allowed_params, $params);

	// The output buffer allows us to echo things instead of storing everything to a vairable
	ob_start();

	// Do the work!
	mw2wp_run($final_params);
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
function mw2wp_run($params) {
	mw2wp_run_exec($params);
}

/**
 * Uses exec() to get the wiki content. This is better than the file_get_contents() method that involves
 * an HTTP request. exec() doesn't work with wikis that are on a separate server or if your server
 * is running PHP in safe_mode
 */
function wp2wp_run_exec($params) {
	$mediawiki_output = array();
	exec('php -f /Users/eyeRmonkey/www/wp-test/wp-content/plugins/mediawiki2wordpress/tester.php', $mediawiki_output);
	mw2wp_debug(implode("\n", $mediawiki_output));
	echo 'baaaaaaaaar';
}




// Add rewrite rules
add_action('init', 'mw2wp_flush_rewrite_rules');
add_action('generate_rewrite_rules', 'mw2wp_add_rewrite_rules');

// Add shortcode tag
add_shortcode('mediawiki2wordpress', 'mw2wp_shortcode_handler');





?>
