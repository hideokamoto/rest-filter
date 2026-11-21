<?php

use PHPUnit\Framework\TestCase;

class VarsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_rest_options']      = array();
		$GLOBALS['_settings_errors']   = array();
		$GLOBALS['_rest_filter_hooks'] = array();
	}

	public function test_default_is_public_vars_plus_meta_vars() {
		$vars = rest_filter_allowed_vars();
		$this->assertContains( 'order', $vars );
		$this->assertContains( 'lang', $vars );
		$this->assertContains( 'meta_query', $vars );
		$this->assertSame( $vars, array_unique( $vars ) );
	}

	public function test_option_replaces_default_set() {
		update_option( REST_FILTER_OPTION, array( 'lang', 'order' ), false );
		$vars = rest_filter_allowed_vars();
		$this->assertSame( array( 'lang', 'order' ), $vars );
		$this->assertNotContains( 'post_status', $vars );
	}

	public function test_rest_query_vars_filter_applies_on_top_of_option() {
		update_option( REST_FILTER_OPTION, array( 'order' ), false );
		add_filter(
			'rest_query_vars',
			function ( $vars ) {
				$vars[] = 'extra_var';
				return $vars;
			}
		);
		$this->assertContains( 'extra_var', rest_filter_allowed_vars() );
	}

	public function test_sanitize_var_names_drops_invalid_and_dedupes() {
		$this->assertSame(
			array( 'lang', 'order', 'x-y_z' ),
			rest_filter_sanitize_var_names( array( ' lang ', 'order', 'lang', 'bad;var', 'a b', '', 42, 'x-y_z' ) )
		);
		$this->assertSame(
			array( 'lang', 'order' ),
			rest_filter_sanitize_var_names( "lang\norder\n" )
		);
	}

	public function test_sanitize_option_input_keeps_previous_on_empty() {
		update_option( REST_FILTER_OPTION, array( 'order' ), false );
		$result = rest_filter_sanitize_option_input( "" );
		$this->assertSame( array( 'order' ), $result );
		$this->assertNotEmpty( $GLOBALS['_settings_errors'] );
	}

	public function test_sanitize_option_input_accepts_textarea_lines() {
		$result = rest_filter_sanitize_option_input( "lang\norder\n" );
		$this->assertSame( array( 'lang', 'order' ), $result );
	}
}
