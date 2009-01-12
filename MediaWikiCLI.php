<?php
/**
 * This file provides an interface to the MediaWikiAPIWrapper through the PHP
 * command line interface (CLI).
 */

$args = arguments($argv);

$page_name = $args['commands']['page'];

require_once('MediaWikiAPIWrapper.php');

$api = new MediaWikiAPIWrapper();

// Set up some fake URL arguments to trick the API with.
$params = $args['commands'];
//$params = array(
//		'action' => 'parse',
//		'title' => $page_name,
//		'text' => '{{:'.$page_name.'}}',
//		'format' => 'php'
//	);


$response_arr = $api->make_fake_request($params);

// Output the serialized array. Needs to be the last line of output
echo base64_encode(serialize($response_arr));



// From: http://us.php.net/manual/en/features.commandline.php#81176
function arguments ( $args ) {
    array_shift( $args );
    $args = join( $args, ' ' );
    preg_match_all('/ (--\w+ (?:[= ] [^-]+ [^\s-] )? ) | (-\w+) | (\w+) /x', $args, $match );
    $args = array_shift( $match );
    $ret = array(
        'input'    => array(),
        'commands' => array(),
        'flags'    => array()
    );
    foreach ( $args as $arg ) {
        // Is it a command? (prefixed with --)
        if ( substr( $arg, 0, 2 ) === '--' ) {
            $value = preg_split( '/[= ]/', $arg, 2 );
            $com   = substr( array_shift($value), 2 );
            $value = join($value);
            $ret['commands'][$com] = !empty($value) ? $value : true;
            continue;
        }
        // Is it a flag? (prefixed with -)
        if ( substr( $arg, 0, 1 ) === '-' ) {
            $ret['flags'][] = substr( $arg, 1 );
            continue;
        }
        $ret['input'][] = $arg;
        continue;
    }
    return $ret;
}
?>
