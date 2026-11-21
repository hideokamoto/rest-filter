<?php

use PHPUnit\Framework\TestCase;

class CliTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_rest_options']      = array();
		$GLOBALS['_rest_filter_hooks'] = array();
		$GLOBALS['_wp_cli_lines']      = array();
	}

	private function cmd() {
		return new Rest_Filter_Vars_Command();
	}

	public function test_reserved_subcommands_have_subcommand_tags() {
		// WP-CLI maps method names literally; `list_`/`set_` become `list`/`set`
		// only via the @subcommand docblock tag.
		foreach ( array( 'list_' => 'list', 'set_' => 'set' ) as $method => $subcommand ) {
			$doc = ( new ReflectionMethod( 'Rest_Filter_Vars_Command', $method ) )->getDocComment();
			$this->assertStringContainsString( "@subcommand {$subcommand}", $doc );
		}
	}

	public function test_command_is_registered() {
		$this->assertArrayHasKey( 'rest-filter vars', $GLOBALS['_wp_cli_commands'] );
	}

	public function test_list_reports_default_source() {
		$this->cmd()->list_( array(), array() );
		$this->assertSame( 'Source: default', $GLOBALS['_wp_cli_lines'][0] );
		$this->assertContains( 'lang', $GLOBALS['_wp_cli_lines'] );
	}

	public function test_add_writes_option() {
		update_option( REST_FILTER_OPTION, array( 'order' ), false );
		$this->cmd()->add( array( 'lang' ) );
		$this->assertSame( array( 'order', 'lang' ), get_option( REST_FILTER_OPTION ) );
	}

	public function test_remove_updates_option() {
		update_option( REST_FILTER_OPTION, array( 'order', 'lang' ), false );
		$this->cmd()->remove( array( 'lang' ) );
		$this->assertSame( array( 'order' ), get_option( REST_FILTER_OPTION ) );
	}

	public function test_remove_refuses_empty_result() {
		update_option( REST_FILTER_OPTION, array( 'order' ), false );
		$this->expectException( WP_CLI_Error_Exception::class );
		$this->cmd()->remove( array( 'order' ) );
	}

	public function test_set_replaces_option() {
		$this->cmd()->set_( array( 'lang', 'posts_per_page' ) );
		$this->assertSame( array( 'lang', 'posts_per_page' ), get_option( REST_FILTER_OPTION ) );
	}

	public function test_reset_deletes_option() {
		update_option( REST_FILTER_OPTION, array( 'order' ), false );
		$this->cmd()->reset();
		$this->assertArrayNotHasKey( REST_FILTER_OPTION, $GLOBALS['_rest_options'] );
	}
}
