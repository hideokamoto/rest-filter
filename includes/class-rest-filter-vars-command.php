<?php
/**
 * WP-CLI surface: `wp rest-filter vars <list|add|remove|reset|set>`.
 *
 * Thin wrapper over the same option the admin page and REST route manage.
 *
 * @package RestFilter
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * `wp rest-filter vars` subcommands.
 */
class Rest_Filter_Vars_Command extends WP_CLI_Command {

	/**
	 * List the effective whitelist (one var per line, preceded by its source).
	 *
	 * @subcommand list
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Named arguments (unused).
	 * @return void
	 */
	public function list_( $args, $assoc_args ) {
		$saved = get_option( REST_FILTER_OPTION, null );
		WP_CLI::line( 'Source: ' . ( is_array( $saved ) ? 'option' : 'default' ) );
		foreach ( rest_filter_allowed_vars() as $var ) {
			WP_CLI::line( $var );
		}
	}

	/**
	 * Add one or more vars to the whitelist option.
	 *
	 * ## OPTIONS
	 *
	 * <var>...
	 * : Query var name(s) to allow.
	 *
	 * @param array $args Var names to allow.
	 * @return void
	 */
	public function add( $args ) {
		$this->mutate( $args, true );
	}

	/**
	 * Remove one or more vars from the whitelist option.
	 *
	 * ## OPTIONS
	 *
	 * <var>...
	 * : Query var name(s) to disallow.
	 *
	 * @param array $args Var names to disallow.
	 * @return void
	 */
	public function remove( $args ) {
		$this->mutate( $args, false );
	}

	/**
	 * Replace the whitelist option with the given vars.
	 *
	 * ## OPTIONS
	 *
	 * <var>...
	 * : The complete list of query var names to allow.
	 *
	 * @subcommand set
	 * @param array $args The complete list of var names to allow.
	 * @return void
	 */
	public function set_( $args ) {
		$clean = rest_filter_sanitize_var_names( $args );
		if ( empty( $clean ) ) {
			WP_CLI::error( 'Provide at least one valid var name (letters, digits, "_", "-").' );
		}
		update_option( REST_FILTER_OPTION, $clean, false );
		WP_CLI::success( 'Whitelist set: ' . implode( ', ', $clean ) );
	}

	/**
	 * Delete the option so the plugin falls back to the default whitelist.
	 *
	 * @return void
	 */
	public function reset() {
		delete_option( REST_FILTER_OPTION );
		WP_CLI::success( 'Whitelist reset to defaults.' );
	}

	/**
	 * Add vars to or remove vars from the whitelist option.
	 *
	 * @param array $vars Var names to add or remove.
	 * @param bool  $add  True to add, false to remove.
	 * @return void
	 */
	private function mutate( $vars, $add ) {
		$clean_in = rest_filter_sanitize_var_names( $vars );
		if ( empty( $clean_in ) ) {
			WP_CLI::error( 'No valid var names given.' );
		}
		$current = rest_filter_allowed_vars();
		$next    = $add
			? array_values( array_unique( array_merge( $current, $clean_in ) ) )
			: array_values( array_diff( $current, $clean_in ) );
		if ( empty( $next ) ) {
			WP_CLI::error( 'Refusing to save an empty whitelist.' );
		}
		update_option( REST_FILTER_OPTION, $next, false );
		WP_CLI::success( ( $add ? 'Added' : 'Removed' ) . ': ' . implode( ', ', $clean_in ) );
	}
}

WP_CLI::add_command( 'rest-filter vars', 'Rest_Filter_Vars_Command' );
