<?php

namespace BlockCatalog;

class CatalogExporterTest extends \WP_UnitTestCase {

	public $exporter;
	public $tmp_file;

	function setUp():void {
		parent::setUp();

		$this->exporter = new CatalogExporter();

		$this->tmp_file = get_temp_dir() . uniqid() . '.csv';
	}

	function tearDown():void {
		parent::tearDown();

		if ( file_exists( $this->tmp_file ) ) {
			unlink( $this->tmp_file );
		}
	}

	function test_it_will_not_export_if_output_path_is_not_writeable() {
		$opts = array(
			'post_type' => 'post',
		);

		$result = $this->exporter->export( '/no/such/temp-csv-path.csv', $opts );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'output_not_writable', $result->get_error_code() );
	}

	function test_it_will_not_export_if_no_block_catalog_terms() {
		$opts = array(
			'post_type' => 'post',
		);

		$result = $this->exporter->export( $this->tmp_file, $opts );

		$this->assertFalse( $result['success'] );
		$this->assertEquals( 'No terms found', $result['message'] );
	}

	function test_it_will_not_export_if_no_taxonomy() {
		$opts = array(
			'post_type' => 'post',
		);

		$this->factory->post->create_many( 3 );

		$result = $this->exporter->export( $this->tmp_file, $opts );

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

		$result = $this->exporter->export( $this->tmp_file, $opts );

		$this->assertTrue( $result['success'] );
		$this->assertEquals( 'Exported successfully', $result['message'] );

		$csv = file_get_contents( $this->tmp_file );
		$this->assertStringContainsString( 'block_name,block_slug,post_id,post_type,post_title,permalink,status', $csv );

		$this->assertStringContainsString( 'Paragraph', $csv );
		$this->assertStringContainsString( 'core-paragraph', $csv );
	}

}
