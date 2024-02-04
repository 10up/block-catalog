<?php

namespace BlockCatalog;

class RESTSupportTest extends \WP_UnitTestCase {

	public $rest;
	public $builder;
	public $server;

	function setUp():void {
		parent::setUp();


		// Initiating the REST API.
		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_REST_Server();
		do_action('rest_api_init');

		$this->builder = new CatalogBuilder();
		$this->rest = new RESTSupport();
	}

	function tearDown():void {
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;
	}

	function test_it_can_register_endpoints() {
		$this->rest->register_endpoints();

		$endpoints = $this->server->get_routes();
		$this->assertArrayHasKey( '/block-catalog/v1/posts', $endpoints );
		$this->assertArrayHasKey( '/block-catalog/v1/index', $endpoints );
		$this->assertArrayHasKey( '/block-catalog/v1/terms', $endpoints );
		$this->assertArrayHasKey( '/block-catalog/v1/delete-index', $endpoints );
	}

	function test_it_can_load_all_block_catalog_terms() {
		$content = file_get_contents( FIXTURES_DIR . '/nested-blocks.html' );
		$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

		$this->builder->catalog( $post_id );

		$actual = $this->rest->get_terms()['terms'];
		$actual = array_column( $actual, 'name' );

		$this->assertContains( 'Column', $actual );
		$this->assertContains( 'Column', $actual );
		$this->assertContains( 'Columns', $actual );
		$this->assertContains( 'List', $actual );
		$this->assertContains( 'List item', $actual );
		$this->assertContains( 'Paragraph', $actual );
		$this->assertContains( 'Quote', $actual );
		$this->assertContains( 'Core', $actual );
	}

	function test_it_can_get_all_posts_to_be_indexed() {
		$total = 5;
		$post_ids = [];

		for ( $i = 0; $i < $total; $i++ ) {
			$content = file_get_contents( FIXTURES_DIR . '/nested-blocks.html' );
			$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

			$this->builder->catalog( $post_id );

			$post_ids[] = $post_id;
		}

		$request = new \WP_REST_Request( 'POST', '/block-catalog/v1/posts' );
		$actual  = $this->rest->get_posts( $request )['posts'];

		$this->assertEquals( $total, count( $actual ) );
		$this->assertEmpty( array_diff( $post_ids, $actual ) );
	}

	function test_it_can_index_posts_over_rest() {
		$total = 5;
		$post_ids = [];

		for ( $i = 0; $i < $total; $i++ ) {
			$content = file_get_contents( FIXTURES_DIR . '/nested-blocks.html' );
			$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

			$post_ids[] = $post_id;
		}

		$request = new \WP_REST_Request( 'POST', '/block-catalog/v1/index' );
		$request->set_param( 'post_ids', $post_ids );

		$actual = $this->rest->index( $request );

		$this->assertEquals( 10, $actual['updated'] );
		$this->assertEquals( 0, $actual['errors'] );
	}

	function test_it_delete_index_over_rest() {
		$total = 5;
		$post_ids = [];

		for ( $i = 0; $i < $total; $i++ ) {
			$content = file_get_contents( FIXTURES_DIR . '/nested-blocks.html' );
			$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

			$post_ids[] = $post_id;
		}

		$request = new \WP_REST_Request( 'POST', '/block-catalog/v1/index' );
		$request->set_param( 'post_ids', $post_ids );

		$actual = $this->rest->index( $request );

		$term_ids = get_terms( [ 'taxonomy' => BLOCK_CATALOG_TAXONOMY, 'fields' => 'ids' ] );

		$request = new \WP_REST_Request( 'POST', '/block-catalog/v1/delete-index' );
		$request->set_param( 'term_ids', $term_ids );

		$actual = $this->rest->delete_index( $request );

		$this->assertEquals( 7, $actual['removed'] );
		$this->assertEquals( 0, $actual['errors'] );
	}

}
