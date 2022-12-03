<?php

namespace BlockCatalog;

class ToolsPageTest extends \WP_UnitTestCase {

	public $page;

	function setUp() {
		parent::setUp();

		$this->page = new ToolsPage();
	}

	function test_it_has_plugin_settings_data() {
		$this->assertNotEmpty( $this->page->get_settings() );
	}

	function test_it_has_default_index_batch_size() {
		$actual = $this->page->get_settings()['settings'];
		$this->assertNotEmpty( $actual['index_batch_size'] );
	}

	function test_it_has_default_delete_batch_size() {
		$actual = $this->page->get_settings()['settings'];
		$this->assertNotEmpty( $actual['index_batch_size'] );
	}

	function test_it_can_override_index_batch_size() {
		add_filter( 'block_catalog_index_batch_size', function() { return 2000; } );

		$actual = $this->page->get_settings()['settings'];
		$this->assertEquals( 2000, $actual['index_batch_size'] );
	}

	function test_it_can_override_delete_batch_size() {
		add_filter( 'block_catalog_delete_index_batch_size', function() { return 200; } );

		$actual = $this->page->get_settings()['settings'];
		$this->assertEquals( 200, $actual['delete_index_batch_size'] );
	}

	function test_it_has_rest_api_endpoints() {
		$actual = $this->page->get_settings()['settings'];

		$this->assertContains( 'block-catalog/v1/posts', $actual['posts_endpoint'] );
		$this->assertContains( 'block-catalog/v1/index', $actual['index_endpoint'] );
		$this->assertContains( 'block-catalog/v1/terms', $actual['terms_endpoint'] );
		$this->assertContains( 'block-catalog/v1/delete-index', $actual['delete_index_endpoint'] );
	}

	function test_it_can_be_rendered_with_errors() {
		ob_start();
		$this->page->render();
		$output = ob_get_clean();

		$this->assertContains( 'index-settings', $output );
	}

	function test_it_enqueues_script_on_render() {
		ob_start();
		$this->page->render();
		$output = ob_get_clean();

		$this->assertTrue( wp_script_is( 'block_catalog_plugin_tools', 'enqueued' ) );
	}

}
