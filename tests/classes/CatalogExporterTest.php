<?php

namespace BlockCatalog;

class CatalogExporterTest extends \WP_UnitTestCase {

	public $exporter;

	function setUp() {
		parent::setUp();

		$this->exporter = new CatalogExporter();
	}

	function test_it_will_not_export_if_output_path_is_not_writeable() {
		$opts = array(
			'post_type' => 'post',
		);

		$result = $this->exporter->export( '/foo/bar/baz.csv', $opts );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'output_not_writable', $result->get_error_code() );
	}

	function test_it_will_not_export_if_no_block_catalog_terms() {
		$opts = array(
			'post_type' => 'post',
		);

		$result = $this->exporter->export( '/tmp/foo.csv', $opts );

		$this->assertFalse( $result['success'] );
		$this->assertEquals( 'No terms found', $result['message'] );
	}

	function test_it_will_not_export_if_no_taxonomy() {
		$opts = array(
			'post_type' => 'post',
		);

		$this->factory->post->create_many( 3 );

		$result = $this->exporter->export( '/tmp/foo.csv', $opts );

		$this->assertFalse( $result['success'] );
		$this->assertEquals( 'No terms found', $result['message'] );
	}

	function test_it_can_export_csv_if_catalog_exists() {
		$taxonomy = new BlockCatalogTaxonomy();
		$taxonomy->register();

		$opts = array(
			'post_type' => 'post',
		);

		$post_ids = $this->factory->post->create_many( 3, array(
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/paragraph -->Hello<!-- /wp:core/paragraph -->',
		) );

		$builder = new CatalogBuilder();
		foreach ( $post_ids as $post_id ) {
			$builder->catalog( $post_id );
		}

		// get temporary writable file using WP api
		$tmp_file = wp_tempnam( 'foo', 'csv' );

		$result = $this->exporter->export( $tmp_file, $opts );

		$this->assertTrue( $result['success'] );
		$this->assertEquals( 'Exported successfully', $result['message'] );

		$csv = file_get_contents( $tmp_file );
		$this->assertContains( 'block_name,block_slug,post_id,post_type,post_title,permalink,status', $csv );
	}

}
