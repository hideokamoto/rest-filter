<?php
/**
 * Plugin Name: WP API REST Filter
 * Plugin URI:  https://github.com/hideokamoto/wp-api-rest-filter
 * Description: Adds a "filter" query parameter to REST API post collections to filter returned results based on a whitelist of public WP_Query parameters, restoring the "filter" parameter that was removed from the API when it was merged into WordPress core. Fork of WP-API/rest-filter (unmaintained upstream).
 * Author:      WP REST API Team, Hidetaka Okamoto
 * Author URI:  https://github.com/hideokamoto
 * Version:     1.2.0
 * Requires at least: 4.7
 * Requires PHP:      7.4
 * License:     GPL2+
 * Update URI:  false
 *
 * @package RestFilter
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/vars.php';
require_once __DIR__ . '/includes/rest-api.php';
require_once __DIR__ . '/includes/class-rest-filter-vars-command.php';
require_once __DIR__ . '/includes/admin.php';

add_action( 'rest_api_init', 'rest_api_filter_add_filters' );

/**
 * Merge whitelisted filter values into WP_Query args.
 *
 * @param array $args         The query arguments built by the REST controller.
 * @param array $filter       The raw `filter` parameter (arbitrary WP_Query vars).
 * @param array $allowed_vars List of query var names that may be overridden.
 * @return array The merged query arguments.
 */
function rest_api_filter_merge_query_vars( $args, $filter, $allowed_vars ) {
	if (
		in_array( 'posts_per_page', $allowed_vars, true )
		&& isset( $filter['posts_per_page'] )
		&& ( (int) $filter['posts_per_page'] >= 1 && (int) $filter['posts_per_page'] <= 100 )
	) {
		$args['posts_per_page'] = (int) $filter['posts_per_page'];
	}

	foreach ( $allowed_vars as $var ) {
		if ( 'posts_per_page' === $var ) {
			continue; // Handled above with clamping.
		}
		if ( isset( $filter[ $var ] ) ) {
			$args[ $var ] = $filter[ $var ];
		}
	}
	return $args;
}

/**
 * Add the necessary filter to each post type.
 **/
function rest_api_filter_add_filters() {
	foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $post_type ) {
		add_filter( 'rest_' . $post_type->name . '_query', 'rest_api_filter_add_filter_param', 10, 2 );
	}
}

/**
 * Add the filter parameter.
 *
 * @param  array           $args    The query arguments.
 * @param  WP_REST_Request $request Full details about the request.
 * @return array $args.
 **/
function rest_api_filter_add_filter_param( $args, $request ) {
	// Bail out if no filter parameter is set.
	if ( empty( $request['filter'] ) || ! is_array( $request['filter'] ) ) {
		return $args;
	}

	return rest_api_filter_merge_query_vars( $args, $request['filter'], rest_filter_allowed_vars() );
}
