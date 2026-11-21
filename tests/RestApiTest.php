<?php

use PHPUnit\Framework\TestCase;

class FakeRestRequest {
	private $params;
	public function __construct( $params ) {
		$this->params = $params;
	}
	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}
}

class RestApiTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_rest_options']      = array();
		$GLOBALS['_rest_filter_hooks'] = array();
		$GLOBALS['_current_user_can']  = true;
	}

	public function test_route_is_registered() {
		rest_filter_register_routes();
		$found = false;
		foreach ( $GLOBALS['_rest_routes'] as $r ) {
			if ( 'rest-filter/v1' === $r[0] && '/vars' === $r[1] ) {
				$found = true;
			}
		}
		$this->assertTrue( $found );
	}

	public function test_permission_requires_manage_options() {
		$GLOBALS['_current_user_can'] = false;
		$this->assertFalse( rest_filter_api_permissions() );
		$GLOBALS['_current_user_can'] = true;
		$this->assertTrue( rest_filter_api_permissions() );
	}

	public function test_get_reports_default_source_without_option() {
		$res = rest_filter_api_get_vars();
		$this->assertSame( 'default', $res['source'] );
		$this->assertContains( 'lang', $res['vars'] );
	}

	public function test_update_saves_whitelist() {
		$res = rest_filter_api_update_vars( new FakeRestRequest( array( 'vars' => array( 'lang', 'order', 'bad;var' ) ) ) );
		$this->assertSame( 'option', $res['source'] );
		$this->assertSame( array( 'lang', 'order' ), $res['vars'] );
		$this->assertSame( array( 'lang', 'order' ), get_option( REST_FILTER_OPTION ) );
	}

	public function test_update_rejects_empty_and_non_array() {
		$res = rest_filter_api_update_vars( new FakeRestRequest( array( 'vars' => 'order' ) ) );
		$this->assertInstanceOf( WP_Error::class, $res );

		$res = rest_filter_api_update_vars( new FakeRestRequest( array( 'vars' => array( ';;' ) ) ) );
		$this->assertInstanceOf( WP_Error::class, $res );
		$this->assertFalse( array_key_exists( REST_FILTER_OPTION, $GLOBALS['_rest_options'] ) );
	}
}
