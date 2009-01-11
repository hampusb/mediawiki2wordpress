<?php
/*
Plugin Name: mediawiki2wordpress
Plugin URI: http://auzigog.com
Description: Allows link between MediaWiki and Wordpress.
Version: 0.3
Author: Jeremy Blanchard
Author URI: http://auzigog.com
*/

add_action('init', 'mw2wp_flush_rewrite_rules');
add_action('generate_rewrite_rules', 'mw2wp_add_rewrite_rules');

function mw2wp_flush_rewrite_rules()
{
   global $wp_rewrite;
   $wp_rewrite->flush_rules();
}

function mw2wp_add_rewrite_rules( $wp_rewrite )
{

	$new_rules = array('wiki/(*)$' => 'index.php?pagename=wiki&title='. $wp_rewrite->preg_index(1));

	$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
}


?>
