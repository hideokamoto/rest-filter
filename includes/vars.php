<?php
/**
 * Allowed query-var whitelist: computation, option storage, sanitization.
 *
 * @package RestFilter
 */

/**
 * Option name storing the whitelist override.
 */
const REST_FILTER_OPTION = 'rest_filter_allowed_vars';

/**
 * Meta query vars allowed on top of public query vars.
 */
const REST_FILTER_META_VARS = array( 'meta_query', 'meta_key', 'meta_value', 'meta_compare' );

/**
 * Vars that work but are worth a warning: they can expose non-public data or
 * drive expensive queries for anonymous API callers.
 */
const REST_FILTER_RISKY_VARS = array(
	'post_status',
	'perm',
	'has_password',
	'meta_query',
	'meta_key',
	'meta_value',
	'meta_compare',
);

/**
 * Default whitelist (used when the option is not set): every public WP_Query
 * var — including the ones plugins register through the core `query_vars`
 * filter (e.g. bogo's `lang`), which WP only applies in `WP::parse_request()`
 * — plus `posts_per_page` (a private query var the original plugin has always
 * exposed with clamping) and the meta query vars.
 *
 * @return string[]
 */
function rest_filter_default_allowed_vars() {
	global $wp;
	$public = ( isset( $wp->public_query_vars ) && is_array( $wp->public_query_vars ) )
		? $wp->public_query_vars
		: array();
	$vars   = apply_filters( 'query_vars', $public );
	return array_values( array_unique( array_merge( $vars, array( 'posts_per_page' ), REST_FILTER_META_VARS ) ) );
}

/**
 * Sanitize a raw list of var names: strings only, trimmed, charset-checked,
 * deduplicated.
 *
 * @param  mixed $raw Input value (array, or a string split on commas/whitespace).
 * @return string[]
 */
function rest_filter_sanitize_var_names( $raw ) {
	if ( is_string( $raw ) ) {
		$raw = preg_split( '/[\s,]+/', $raw );
	}
	if ( ! is_array( $raw ) ) {
		return array();
	}
	$out = array();
	foreach ( $raw as $v ) {
		if ( ! is_string( $v ) ) {
			continue;
		}
		$v = trim( $v );
		if ( '' === $v || ! preg_match( '/^[a-zA-Z0-9_-]+$/', $v ) ) {
			continue;
		}
		$out[] = $v;
	}
	return array_values( array_unique( $out ) );
}

/**
 * Effective whitelist.
 *
 * When the option holds an array it replaces the default set entirely
 * (site-owner policy). The `rest_query_vars` filter always runs last so code
 * can still extend/override the list — that's the recovery hatch if the
 * option is ever misconfigured.
 *
 * @return string[]
 */
function rest_filter_allowed_vars() {
	$saved = get_option( REST_FILTER_OPTION, null );
	$vars  = ( is_array( $saved ) && ! empty( $saved ) )
		? rest_filter_sanitize_var_names( $saved )
		: rest_filter_default_allowed_vars();
	return array_values( array_unique( apply_filters( 'rest_query_vars', $vars ) ) );
}

/**
 * Sanitize callback for the wp-admin settings field (and reusable elsewhere).
 * Accepts a string (one var per line / comma separated) or an array. An input
 * that yields an empty list is rejected: the previous option value is kept so
 * the whitelist can never be emptied by accident.
 *
 * @param  mixed $input Raw form value.
 * @return array|string[]|false The sanitized list, or the previous option value.
 */
function rest_filter_sanitize_option_input( $input ) {
	$clean = rest_filter_sanitize_var_names( $input );
	if ( empty( $clean ) ) {
		add_settings_error(
			REST_FILTER_OPTION,
			'rest_filter_empty',
			'Allowed vars must contain at least one valid name; previous value kept.'
		);
		return get_option( REST_FILTER_OPTION, false );
	}
	return $clean;
}
