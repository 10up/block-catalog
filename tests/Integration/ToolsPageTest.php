<?php

namespace BlockCatalog;

class ToolsPageTest extends \WP_UnitTestCase {

	public $page;

	function setUp():void {
		parent::setUp();

		$this->page = new ToolsPage();
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

}
