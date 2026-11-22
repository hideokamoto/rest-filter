<?php
/**
 * WP-API surface: manage the whitelist over REST.
 *
 *   GET    /wp-json/rest-filter/v1/vars              -> { vars, source }
 *   PATCH  /wp-json/rest-filter/v1/vars {vars:[..]}   -> { vars, source }
 *   GET    /wp-json/rest-filter/v1/post-types        -> { types, source, available }
 *   PATCH  /wp-json/rest-filter/v1/post-types {types:[..]} -> { types, source, available }
 *   DELETE /wp-json/rest-filter/v1/post-types        -> { types, source, available }
 *
 * Both require the `manage_options` capability — an unauthenticated caller
 * must never be able to widen the whitelist.
 *
 * @package RestFilter
 */

add_action( 'rest_api_init', 'rest_filter_register_routes' );

/**
 * Register the rest-filter/v1/vars route.
 *
 * @return void
 */
function rest_filter_register_routes() {
	register_rest_route(
		'rest-filter/v1',
		'/vars',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'rest_filter_api_get_vars',
				'permission_callback' => 'rest_filter_api_permissions',
			),
			array(
				'methods'             => array( 'POST', 'PUT', 'PATCH' ),
				'callback'            => 'rest_filter_api_update_vars',
				'permission_callback' => 'rest_filter_api_permissions',
				'args'                => array(
					'vars' => array(
						'required' => true,
						'type'     => 'array',
						'items'    => array( 'type' => 'string' ),
					),
				),
			),
		)
	);
	register_rest_route(
		'rest-filter/v1',
		'/post-types',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'rest_filter_api_get_post_types',
				'permission_callback' => 'rest_filter_api_permissions',
			),
			array(
				'methods'             => array( 'POST', 'PUT', 'PATCH' ),
				'callback'            => 'rest_filter_api_update_post_types',
				'permission_callback' => 'rest_filter_api_permissions',
				'args'                => array(
					'types' => array(
						'required' => true,
						'type'     => 'array',
						'items'    => array( 'type' => 'string' ),
					),
				),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => 'rest_filter_api_delete_post_types',
				'permission_callback' => 'rest_filter_api_permissions',
			),
		)
	);
}

/**
 * Permission check: only users who can manage options may read or change
 * the whitelist.
 *
 * @return bool
 */
function rest_filter_api_permissions() {
	return current_user_can( 'manage_options' );
}

/**
 * GET handler: return the effective whitelist and where it came from.
 *
 * @return array{vars: string[], source: string}
 */
function rest_filter_api_get_vars() {
	$saved = get_option( REST_FILTER_OPTION, null );
	return rest_ensure_response(
		array(
			'vars'   => rest_filter_allowed_vars(),
			'source' => ( is_array( $saved ) && ! empty( $saved ) ) ? 'option' : 'default',
		)
	);
}

/**
 * Write handler: replace the whitelist option.
 *
 * @param  WP_REST_Request $request Request carrying a `vars` array param.
 * @return array|WP_Error The new state, or an error when `vars` is invalid.
 */
function rest_filter_api_update_vars( $request ) {
	$vars = $request->get_param( 'vars' );
	if ( ! is_array( $vars ) ) {
		return new WP_Error(
			'rest_filter_invalid',
			'"vars" must be an array of query var names.',
			array( 'status' => 400 )
		);
	}
	$clean = rest_filter_sanitize_var_names( $vars );
	if ( empty( $clean ) ) {
		return new WP_Error(
			'rest_filter_empty',
			'"vars" must contain at least one valid var name.',
			array( 'status' => 400 )
		);
	}
	update_option( REST_FILTER_OPTION, $clean, false );
	return rest_filter_api_get_vars();
}

/**
 * GET handler: the effective post-type scope, its source, and the eligible
 * REST-enabled types.
 *
 * @return array{types: string[], source: string, available: string[]}
 */
function rest_filter_api_get_post_types() {
	$saved = get_option( REST_FILTER_POST_TYPES_OPTION, null );
	return rest_ensure_response(
		array(
			'types'     => rest_filter_post_types(),
			'source'    => is_array( $saved ) ? 'option' : 'default',
			'available' => rest_filter_default_post_types(),
		)
	);
}

/**
 * Write handler: replace the post-type scope. An empty array disables
 * `filter` on every post type; names that are not REST-enabled types are
 * dropped.
 *
 * @param  WP_REST_Request $request Request carrying a `types` array param.
 * @return array|WP_Error The new state, or an error when `types` is invalid.
 */
function rest_filter_api_update_post_types( $request ) {
	$types = $request->get_param( 'types' );
	if ( ! is_array( $types ) ) {
		return new WP_Error(
			'rest_filter_invalid',
			'"types" must be an array of post type names.',
			array( 'status' => 400 )
		);
	}
	$clean = array_values( array_intersect( rest_filter_sanitize_var_names( $types ), rest_filter_default_post_types() ) );
	update_option( REST_FILTER_POST_TYPES_OPTION, $clean, false );
	return rest_filter_api_get_post_types();
}

/**
 * DELETE handler: remove the option so `filter` applies to every
 * REST-enabled post type again.
 *
 * @return array The new state.
 */
function rest_filter_api_delete_post_types() {
	delete_option( REST_FILTER_POST_TYPES_OPTION );
	return rest_filter_api_get_post_types();
}
