<?php
/**
 * WP-CLI surface: `wp rest-filter post-types <list|set|add|remove|disable|reset>`.
 *
 * Thin wrapper over the same option the admin page and REST route manage.
 *
 * @package RestFilter
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * `wp rest-filter post-types` subcommands.
 */
class Rest_Filter_Post_Types_Command extends WP_CLI_Command {

	/**
	 * List the post types `filter` applies to, preceded by the source.
	 *
	 * @subcommand list
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Named arguments (unused).
	 * @return void
	 */
	public function list_( $args, $assoc_args ) {
		$saved = get_option( REST_FILTER_POST_TYPES_OPTION, null );
		WP_CLI::line( 'Source: ' . ( is_array( $saved ) ? 'option' : 'default' ) );
		foreach ( rest_filter_post_types() as $type ) {
			WP_CLI::line( $type );
		}
	}

	/**
	 * Replace the post-type scope with the given types.
	 *
	 * ## OPTIONS
	 *
	 * <type>...
	 * : The complete list of post type names `filter` applies to.
	 *
	 * @subcommand set
	 * @param array $args The complete list of post type names.
	 * @return void
	 */
	public function set_( $args ) {
		$clean = $this->sanitize_known( $args );
		if ( empty( $clean ) ) {
			WP_CLI::error( 'Provide at least one valid REST-enabled post type. To disable `filter` everywhere use `wp rest-filter post-types disable`.' );
		}
		update_option( REST_FILTER_POST_TYPES_OPTION, $clean, false );
		WP_CLI::success( 'Post types set: ' . implode( ', ', $clean ) );
	}

	/**
	 * Add one or more post types to the scope.
	 *
	 * ## OPTIONS
	 *
	 * <type>...
	 * : Post type name(s) to enable `filter` on.
	 *
	 * @param array $args Post type names to add.
	 * @return void
	 */
	public function add( $args ) {
		$this->mutate( $args, true );
	}

	/**
	 * Remove one or more post types from the scope.
	 *
	 * ## OPTIONS
	 *
	 * <type>...
	 * : Post type name(s) to disable `filter` on.
	 *
	 * @param array $args Post type names to remove.
	 * @return void
	 */
	public function remove( $args ) {
		$this->mutate( $args, false );
	}

	/**
	 * Disable `filter` on every post type (saves an empty scope).
	 *
	 * @return void
	 */
	public function disable() {
		update_option( REST_FILTER_POST_TYPES_OPTION, array(), false );
		WP_CLI::success( '`filter` disabled on all post types.' );
	}

	/**
	 * Delete the option so `filter` applies to every REST-enabled post type.
	 *
	 * @return void
	 */
	public function reset() {
		delete_option( REST_FILTER_POST_TYPES_OPTION );
		WP_CLI::success( 'Post types reset to all REST-enabled types.' );
	}

	/**
	 * Sanitize type names and drop names that are not REST-enabled types.
	 *
	 * @param array $types Raw type names.
	 * @return string[]
	 */
	private function sanitize_known( $types ) {
		return array_values( array_intersect( rest_filter_sanitize_var_names( $types ), rest_filter_default_post_types() ) );
	}

	/**
	 * Add types to or remove types from the scope option.
	 *
	 * @param array $types Type names to add or remove.
	 * @param bool  $add   True to add, false to remove.
	 * @return void
	 */
	private function mutate( $types, $add ) {
		$clean_in = $this->sanitize_known( $types );
		if ( empty( $clean_in ) ) {
			WP_CLI::error( 'No valid REST-enabled post type names given.' );
		}
		$current = rest_filter_post_types();
		$next    = $add
			? array_values( array_unique( array_merge( $current, $clean_in ) ) )
			: array_values( array_diff( $current, $clean_in ) );
		update_option( REST_FILTER_POST_TYPES_OPTION, $next, false );
		WP_CLI::success( ( $add ? 'Added' : 'Removed' ) . ': ' . implode( ', ', $clean_in ) );
	}
}

WP_CLI::add_command( 'rest-filter post-types', 'Rest_Filter_Post_Types_Command' );
