<?php
/**
 * Minimal WordPress stubs for unit tests.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/stubs/' );
}
if ( ! defined( 'WP_CLI' ) ) {
	define( 'WP_CLI', true );
}

$GLOBALS['_rest_filter_actions']    = array();
$GLOBALS['_rest_filter_hooks']      = array();
$GLOBALS['_rest_options']           = array();
$GLOBALS['_settings_errors']        = array();
$GLOBALS['_registered_settings']    = array();
$GLOBALS['_options_pages']          = array();
$GLOBALS['_rest_routes']            = array();
$GLOBALS['_current_user_can']       = true;
$GLOBALS['_wp_cli_commands']        = array();
$GLOBALS['_wp_cli_lines']           = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['_rest_filter_actions'][ $hook ][] = $callback;
	return true;
}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['_rest_filter_hooks'][ $hook ][] = $callback;
	return true;
}
function apply_filters( $hook, $value ) {
	foreach ( $GLOBALS['_rest_filter_hooks'][ $hook ] ?? array() as $callback ) {
		$value = call_user_func( $callback, $value );
	}
	return $value;
}
function get_post_types( $args = array(), $output = 'names' ) {
	$types = array();
	foreach ( array( 'post', 'page', 'dev-note', 'event', 'product' ) as $name ) {
		$obj       = new stdClass();
		$obj->name = $name;
		$types[]   = $obj;
	}
	return $types;
}

function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['_rest_options'] ) ? $GLOBALS['_rest_options'][ $name ] : $default;
}
function update_option( $name, $value, $autoload = null ) {
	$GLOBALS['_rest_options'][ $name ] = $value;
	return true;
}
function delete_option( $name ) {
	unset( $GLOBALS['_rest_options'][ $name ] );
	return true;
}
function add_settings_error( $setting, $code, $message ) {
	$GLOBALS['_settings_errors'][] = array( $setting, $code, $message );
}
function register_setting( $group, $name, $args = array() ) {
	$GLOBALS['_registered_settings'][ $name ] = array( $group, $args );
}
function add_options_page( $page_title, $menu_title, $cap, $slug, $callback ) {
	$GLOBALS['_options_pages'][] = compact( 'page_title', 'menu_title', 'cap', 'slug', 'callback' );
}
function current_user_can( $cap ) {
	return $GLOBALS['_current_user_can'];
}
function register_rest_route( $namespace, $route, $args ) {
	$GLOBALS['_rest_routes'][] = array( $namespace, $route, $args );
}
function rest_ensure_response( $response ) {
	return $response;
}
class WP_Error {
	public $code;
	public $message;
	public $data;
	public function __construct( $code = '', $message = '', $data = '' ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}
}

// WP-CLI stubs.
class WP_CLI_Command {}
class WP_CLI_Error_Exception extends Exception {}
class WP_CLI {
	public static function line( $msg = '' ) {
		$GLOBALS['_wp_cli_lines'][] = $msg;
	}
	public static function success( $msg ) {
		$GLOBALS['_wp_cli_lines'][] = "Success: $msg";
	}
	public static function error( $msg ) {
		throw new WP_CLI_Error_Exception( $msg );
	}
	public static function add_command( $name, $class ) {
		$GLOBALS['_wp_cli_commands'][ $name ] = $class;
	}
}
// Fake WP global carrying public query vars (incl. bogo's `lang`).
$GLOBALS['wp']                    = new stdClass();
$GLOBALS['wp']->public_query_vars = array(
	'p', 'page_id', 'pagename', 's', 'cat', 'tag', 'author_name',
	'year', 'monthnum', 'day', 'w', 'm', 'post_type', 'name',
	'order', 'orderby', 'lang', 'post_status',
);

require_once dirname( __DIR__ ) . '/plugin.php';
