<?php
/**
 * Minimal WordPress stubs for unit tests.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/stubs/' );
}

$GLOBALS['_rest_filter_actions'] = array();
$GLOBALS['_rest_filter_hooks']   = array();

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['_rest_filter_actions'][ $hook ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['_rest_filter_hooks'][ $hook ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) {
		foreach ( $GLOBALS['_rest_filter_hooks'][ $hook ] ?? array() as $callback ) {
			$value = call_user_func( $callback, $value );
		}
		return $value;
	}
}

if ( ! function_exists( 'get_post_types' ) ) {
	function get_post_types( $args = array(), $output = 'names' ) {
		$types = array();
		foreach ( array( 'post', 'page', 'dev-note', 'event', 'product' ) as $name ) {
			$obj       = new stdClass();
			$obj->name = $name;
			$types[]   = $obj;
		}
		return $types;
	}
}

// Fake WP global carrying public query vars (incl. bogo's `lang`).
$GLOBALS['wp']              = new stdClass();
$GLOBALS['wp']->public_query_vars = array(
	'p', 'page_id', 'pagename', 's', 'cat', 'tag', 'author_name',
	'year', 'monthnum', 'day', 'w', 'm', 'post_type', 'name',
	'order', 'orderby', 'posts_per_page', 'lang', 'post_status',
);

require_once dirname( __DIR__ ) . '/plugin.php';
