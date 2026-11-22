<?php

use PHPUnit\Framework\TestCase;

class PostTypesTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_rest_options']      = array();
		$GLOBALS['_rest_filter_hooks'] = array();
	}

	public function test_default_is_all_rest_enabled_types() {
		$this->assertSame(
			array( 'post', 'page', 'dev-note', 'event', 'product' ),
			rest_filter_post_types()
		);
	}

	public function test_option_restricts_scope() {
		update_option( REST_FILTER_POST_TYPES_OPTION, array( 'post', 'page' ), false );
		$this->assertSame( array( 'post', 'page' ), rest_filter_post_types() );
	}

	public function test_option_drops_unregistered_types() {
		update_option( REST_FILTER_POST_TYPES_OPTION, array( 'post', 'nonexistent', 'bad;type' ), false );
		$this->assertSame( array( 'post' ), rest_filter_post_types() );
	}

	public function test_empty_option_disables_filter_everywhere() {
		update_option( REST_FILTER_POST_TYPES_OPTION, array(), false );
		$this->assertSame( array(), rest_filter_post_types() );
	}

	public function test_filter_hook_runs_last() {
		update_option( REST_FILTER_POST_TYPES_OPTION, array( 'post' ), false );
		add_filter(
			'rest_filter_post_types',
			function ( $types ) {
				$types[] = 'page';
				return $types;
			}
		);
		$this->assertSame( array( 'post', 'page' ), rest_filter_post_types() );
	}

	public function test_add_filters_registers_hooks_only_for_scope() {
		$GLOBALS['_rest_filter_hooks'] = array();
		update_option( REST_FILTER_POST_TYPES_OPTION, array( 'post' ), false );
		rest_api_filter_add_filters();
		$this->assertArrayHasKey( 'rest_post_query', $GLOBALS['_rest_filter_hooks'] );
		$this->assertArrayNotHasKey( 'rest_page_query', $GLOBALS['_rest_filter_hooks'] );
	}

	public function test_add_filters_registers_all_types_by_default() {
		$GLOBALS['_rest_filter_hooks'] = array();
		rest_api_filter_add_filters();
		foreach ( array( 'post', 'page', 'dev-note', 'event', 'product' ) as $type ) {
			$this->assertArrayHasKey( 'rest_' . $type . '_query', $GLOBALS['_rest_filter_hooks'] );
		}
	}

	public function test_api_get_returns_types_source_available() {
		$result = rest_filter_api_get_post_types();
		$this->assertSame( 'default', $result['source'] );
		$this->assertSame( array( 'post', 'page', 'dev-note', 'event', 'product' ), $result['types'] );
		$this->assertSame( array( 'post', 'page', 'dev-note', 'event', 'product' ), $result['available'] );
	}

	public function test_api_update_sanitizes_and_saves() {
		$request = new class() {
			public function get_param( $key ) {
				return array( 'post', 'nonexistent', 'bad;type' );
			}
		};
		$result = rest_filter_api_update_post_types( $request );
		$this->assertSame( array( 'post' ), get_option( REST_FILTER_POST_TYPES_OPTION ) );
		$this->assertSame( 'option', $result['source'] );
		$this->assertSame( array( 'post' ), $result['types'] );
	}

	public function test_api_update_rejects_non_array() {
		$request = new class() {
			public function get_param( $key ) {
				return 'post';
			}
		};
		$result = rest_filter_api_update_post_types( $request );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'rest_filter_invalid', $result->code );
	}

	public function test_api_update_empty_disables() {
		$request = new class() {
			public function get_param( $key ) {
				return array();
			}
		};
		$result = rest_filter_api_update_post_types( $request );
		$this->assertSame( array(), $result['types'] );
		$this->assertSame( 'option', $result['source'] );
	}

	public function test_api_delete_resets_to_default() {
		update_option( REST_FILTER_POST_TYPES_OPTION, array( 'post' ), false );
		$result = rest_filter_api_delete_post_types();
		$this->assertSame( 'default', $result['source'] );
		$this->assertSame( array( 'post', 'page', 'dev-note', 'event', 'product' ), $result['types'] );
	}

	public function test_cli_command_is_registered() {
		$this->assertSame( 'Rest_Filter_Post_Types_Command', $GLOBALS['_wp_cli_commands']['rest-filter post-types'] );
	}
}
