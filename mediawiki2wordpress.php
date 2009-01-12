<?php
/*
Plugin Name: mediawiki2wordpress
Plugin URI: http://auzigog.com
Description: Allows link between MediaWiki and Wordpress.
Version: 0.3
Author: Jeremy Blanchard
Author URI: http://auzigog.com
*/


define(MW2WP_WIKI_REWRITE_CONDITION, '(?:/)?(.*)'); // Combine with the wiki slug to make wiki/* rule
define(MW2WP_WIKI_DEFAULT_PAGE, 'Main_Page');

// TODO: turn these into options
define(MW2WP_WIKI_SLUG, 'wiki');
define(MW2WP_WIKI_PAGE_NAME, 'Wiki');

define(MW2WP_ALLOW_SHORTCODES_IN_WIKI, true);
define(MW2WP_ALLOW_CONTENT_FILTERS_IN_WIKI, true);
define(MW2WP_USE_CLI, true);
define(MW2WP_MEDIAWIKI_PATH, '/Users/eyeRmonkey/www/mediawiki-test');
define(MW2WP_MEDIAWIKI_URL, 'http://localhost/mediawiki-test');


// Holds all the information from mediawiki and various other bits of info
$mw2wp = new mediawiki2wordpress();


class mediawiki2wordpress {
	public $active;			// wp2mw is active for this request
	public $request_path;   // like array('Main_Page', 'edit', 'foo');

	public $api_response;	// Full array from the API call
	public $page_name;		// like Main_Page
	public $page_title;		// like Main Page
	public $page_content;


	/**
	 * Get's the data from the API either through the command line (quicker, but doesn't work
	 * on certain servers -- especially shared hosts) or over HTTP (slower because it happens
	 * over HTTP).
	 */
	public function make_api_call($params, $use_cli = true) {
		$api_response = null;

		if($use_cli) {
			$api_response = $this->mediawiki_api_cli($params);
		} else {
			$api_response = $this->mediawiki_api_http($params);
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
	function mediawiki_api_cli($params) {
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

		$api_reponse = $this->mediawiki_api_cli_decode($api_response_string);
		return $api_reponse;
	}


	/**
	 * The CLI output is encoded to ensure maximum compatibility between systems
	 */
	function mediawiki_api_cli_decode($result) {
		$string = trim(base64_decode(trim($result)));
		$arr = unserialize($string);
		return $arr;
	}

	/**
	 * HTTP version of an API call. This is much slower and could pound the server hosting the wiki, but it functions on many more
	 * servers than the CLI version. It can also work for wikis that aren't hosted on the same server.
	 */
	function mediawiki_api_http($params) {
		$url_params = '';
		$i = 0;
		foreach($params as $key=>$val) {
			$url_params .= urlencode($key).'='.urlencode($val);
			if(++$i < count($params))
				$url_params .= '&';
		}

		$request_url = MW2WP_MEDIAWIKI_URL . '/api.php?' . $url_params;

		// Get the file over HTTP
		// TODO: use CURL
		// TODO: make sure it's gzipped
		$response_string = file_get_contents($request_url);

		$api_response = unserialize(trim($response_string));
		return $api_response;
	}

	/**
	 * Gets all the data for the current $page_name
	 */
	public function run_for_current_page() {
		$api_params = array(
				'action' => 'parse',
				'title' => $this->page_name,
				'text' => '{{:'.$this->page_name.'}}',
				'format' => 'php'
			);

		$api_response = $this->make_api_call($api_params, MW2WP_USE_CLI);

		$this->page_content = $api_response['parse']['text']['*'];

		return $api_response;
	}

	public function display_current_wiki_page() {
		
	}

}

function debug($var) {
	echo '<pre>';
	if($var == 'trace') {
		echo "Printing stack trace!!";
		print_r(debug_backtrace());
	} else {
		print_r($var);
	}
	echo '</pre>';
}

function mw2wp_flush_rewrite_rules() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

function mw2wp_add_rewrite_rules($wp_rewrite) {
	// Redirect anything under the wiki "subdirectory" to the wiki page.
	$rewrite_condition = MW2WP_WIKI_SLUG.MW2WP_WIKI_REWRITE_CONDITION;
	$rewrite_rule = 'index.php?pagename='.MW2WP_WIKI_SLUG;  //.'&path=' . $wp_rewrite->preg_index(1);
	
	$new_rules = array($rewrite_condition => $rewrite_rule);

	$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
}

/**
 * If this is the general wiki page, load the content. Called by "wp" action before headers are loaded.
 */
function mw2wp_load_content($wp) {
	$is_the_wiki_page = !empty($wp->query_vars['pagename']) && $wp->query_vars['pagename'] == MW2WP_WIKI_SLUG;

	if($is_the_wiki_page) {
		global $mw2wp;

		// Make it clear that the plugin is active for this request
		$mw2wp->active = true;

		// Parse out the request string to get the wiki page name
		$full_request_path = $wp->request;	// Includes the wiki slug

		// Parse out the path
		$wiki_request_path = mw2wp_parse_wiki_request_path($full_request_path);
		$mw2wp->request_path = $wiki_request_path;

		// Default page name if nothing is specified
		if(empty($wiki_request_path[0])) {
			$mw2wp->page_name = MW2WP_WIKI_DEFAULT_PAGE; // Main_Page
		} else {
			$mw2wp->page_name = $wiki_request_path[0];
		}

		$mw2wp->page_title = urldecode($mw2wp->page_name);

		// Make the API call
		$mw2wp->run_for_current_page();
	}
}

/**
 * Get the request path
 */
function mw2wp_parse_wiki_request_path($full_request_path) {
	$matches = array();
	$pattern = '|'.MW2WP_WIKI_SLUG.MW2WP_WIKI_REWRITE_CONDITION.'|';
	preg_match($pattern, $full_request_path, $matches);
	$wiki_request_path_string = $matches[1];	// something like 'Main_Page/foo/bar';

	// Parse the path into parts
	$wiki_request_path = explode('/', $wiki_request_path_string);
	return $wiki_request_path;
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

	// TODO: Handle short code params for specific pages!!

	$wiki_content = mw2wp_get_content();

	return $wiki_content;
}

/**
 * All the filters and such that go along with getting the API response
 */
function mw2wp_run_complete() {
	global $mw2wp;
	// The output buffer allows us to echo things instead of storing everything to a vairable
	ob_start();

	// Do the work!
	mw2wp_get_content();

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
function mw2wp_get_content() {
	global $mw2wp;

	$wiki_content = $mw2wp->page_content;
	echo $wiki_content;
}



/**
 * <title> filter
 */
function mw2wp_wp_title_filter($title, $sep, $seplocation) {
	global $mw2wp;
	if($mw2wp->active)  {
		$search = MW2WP_WIKI_PAGE_NAME;
		if($seplocation == 'right')
			$replace = $mw2wp->page_title . " $sep " . MW2WP_WIKI_PAGE_NAME;
		else
			$replace = MW2WP_WIKI_PAGE_NAME . " $sep " . $mw2wp->page_title;
		return str_replace($search, $replace, $title);
	}
	return $title;
}



// Add rewrite rules
add_action('init', 'mw2wp_flush_rewrite_rules');
add_action('generate_rewrite_rules', 'mw2wp_add_rewrite_rules');

// Add wp action to load the actual data (if it's the proper page)
add_action('wp', 'mw2wp_load_content');

// Add shortcode tag
add_shortcode('mediawiki2wordpress', 'mw2wp_shortcode_handler');

// Allows us to override the title that would have been displayed
add_filter('wp_title', 'mw2wp_wp_title_filter', 10, 3); // For <title>


?>
