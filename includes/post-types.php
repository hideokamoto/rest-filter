<?php
/**
 * Post-type scope: which post types the `filter` parameter applies to.
 *
 * Option `rest_filter_post_types` semantics:
 *   - not set        -> every `show_in_rest` post type (upstream-compatible default)
 *   - array of names -> only those (intersected with registered REST types)
 *   - empty array    -> `filter` disabled on every post type
 *
 * The `rest_filter_post_types` filter always runs last, so code can extend or
 * restore the list when the option is misconfigured.
 *
 * @package RestFilter
 */

/**
 * Option name storing the post-type scope.
 */
const REST_FILTER_POST_TYPES_OPTION = 'rest_filter_post_types';

/**
 * Post types eligible for `filter`: every type with `show_in_rest => true`.
 *
 * @return string[]
 */
function rest_filter_default_post_types() {
	$types = array();
	foreach ( get_post_types( array( 'show_in_rest' => true ), 'objects' ) as $type ) {
		$types[] = is_object( $type ) ? $type->name : (string) $type;
	}
	return array_values( array_unique( $types ) );
}

/**
 * Effective post-type scope.
 *
 * A saved option is intersected with the eligible types, so names of types
 * that do not exist (or are not REST-enabled) are inert. An explicitly saved
 * empty array means `filter` is disabled everywhere.
 *
 * @return string[]
 */
function rest_filter_post_types() {
	$saved = get_option( REST_FILTER_POST_TYPES_OPTION, null );
	$types = is_array( $saved )
		? array_values( array_intersect( rest_filter_sanitize_var_names( $saved ), rest_filter_default_post_types() ) )
		: rest_filter_default_post_types();
	return array_values( array_unique( apply_filters( 'rest_filter_post_types', $types ) ) );
}
