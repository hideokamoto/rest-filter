<?php
/**
 * WP-API surface: manage the whitelist over REST.
 *
 *   GET   /wp-json/rest-filter/v1/vars            -> { vars, source }
 *   PATCH /wp-json/rest-filter/v1/vars {vars:[..]} -> { vars, source }
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
