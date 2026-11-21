<?php

use PHPUnit\Framework\TestCase;

class FilterParamTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_rest_options']      = array();
		$GLOBALS['_rest_filter_hooks'] = array();
	}

	private function request( $filter ) {
		return new ArrayObject( array( 'filter' => $filter ) );
	}

	public function test_registers_hook_for_each_rest_post_type() {
		rest_api_filter_add_filters();
		foreach ( array( 'post', 'page', 'dev-note', 'event', 'product' ) as $name ) {
			$this->assertContains(
				'rest_api_filter_add_filter_param',
				$GLOBALS['_rest_filter_hooks'][ "rest_{$name}_query" ] ?? array()
			);
		}
	}

	public function test_no_filter_param_returns_args_unchanged() {
		$args = array( 'post_type' => 'post' );
		$this->assertSame( $args, rest_api_filter_add_filter_param( $args, new ArrayObject() ) );
	}

	public function test_non_array_filter_returns_args_unchanged() {
		$args = array( 'post_type' => 'post' );
		$this->assertSame( $args, rest_api_filter_add_filter_param( $args, $this->request( 'order=ASC' ) ) );
	}

	public function test_whitelisted_var_is_applied() {
		$args = rest_api_filter_add_filter_param( array(), $this->request( array( 'order' => 'ASC', 'lang' => 'en' ) ) );
		$this->assertSame( 'ASC', $args['order'] );
		$this->assertSame( 'en', $args['lang'] );
	}

	public function test_unknown_var_is_ignored() {
		$args = rest_api_filter_add_filter_param( array(), $this->request( array( 'not_a_query_var' => 'x' ) ) );
		$this->assertArrayNotHasKey( 'not_a_query_var', $args );
	}

	public function test_posts_per_page_is_clamped_to_1_100() {
		$args = rest_api_filter_add_filter_param( array(), $this->request( array( 'posts_per_page' => '50' ) ) );
		$this->assertSame( 50, $args['posts_per_page'] );

		foreach ( array( '0', '101', 'abc' ) as $bad ) {
			$args = rest_api_filter_add_filter_param( array( 'posts_per_page' => 10 ), $this->request( array( 'posts_per_page' => $bad ) ) );
			$this->assertSame( 10, $args['posts_per_page'] );
		}
	}

	public function test_meta_query_vars_are_allowed() {
		$meta = array( array( 'key' => 'color', 'value' => 'blue' ) );
		$args = rest_api_filter_add_filter_param(
			array(),
			$this->request( array( 'meta_query' => $meta, 'meta_key' => 'color', 'meta_compare' => '=' ) )
		);
		$this->assertSame( $meta, $args['meta_query'] );
		$this->assertSame( 'color', $args['meta_key'] );
		$this->assertSame( '=', $args['meta_compare'] );
	}

	public function test_posts_per_page_ignored_when_not_whitelisted() {
		update_option( REST_FILTER_OPTION, array( 'order' ), false );
		$args = rest_api_filter_add_filter_param(
			array( 'posts_per_page' => 10 ),
			$this->request( array( 'posts_per_page' => '50' ) )
		);
		$this->assertSame( 10, $args['posts_per_page'] );
	}

	public function test_rest_query_vars_filter_can_extend_allowed_vars() {
		add_filter(
			'rest_query_vars',
			function ( $vars ) {
				$vars[] = 'my_custom_var';
				return $vars;
			}
		);
		$args = rest_api_filter_add_filter_param( array(), $this->request( array( 'my_custom_var' => 'yes' ) ) );
		$this->assertSame( 'yes', $args['my_custom_var'] );
	}
}
