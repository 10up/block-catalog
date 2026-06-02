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
		$this->assertStringContainsString( 'block_name,block_slug,post_id,post_type,post_title,permalink,post_status,edit_link,post_author,post_date,post_modified,notes', $csv );

		$this->assertStringContainsString( 'Paragraph', $csv );
		$this->assertStringContainsString( 'core-paragraph', $csv );
	}

	function test_it_can_export_only_the_requested_blocks() {
		$taxonomy = new BlockCatalogTaxonomy();
		$taxonomy->register();

		$quote_post = $this->factory->post->create( array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => 'Quote Post',
			'post_content' => '<!-- wp:core/quote --><!-- /wp:core/quote -->',
		) );

		$heading_post = $this->factory->post->create( array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => 'Heading Post',
			'post_content' => '<!-- wp:core/heading --><!-- /wp:core/heading -->',
		) );

		$builder = new CatalogBuilder();
		$builder->catalog( $quote_post );
		$builder->catalog( $heading_post );

		$opts = array(
			'post_type' => 'post',
			'blocks'    => array( 'core-quote' ),
		);

		$result = $this->exporter->export( $this->tmp_file, $opts );
		$this->assertTrue( $result['success'] );

		$csv = file_get_contents( $this->tmp_file );
		$this->assertStringContainsString( 'core-quote', $csv );
		$this->assertStringNotContainsString( 'core-heading', $csv );
	}

	function test_it_excludes_non_published_posts_by_default() {
		$taxonomy = new BlockCatalogTaxonomy();
		$taxonomy->register();

		$published = $this->factory->post->create( array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => 'Published Quote',
			'post_content' => '<!-- wp:core/quote --><!-- /wp:core/quote -->',
		) );

		$draft = $this->factory->post->create( array(
			'post_type'    => 'post',
			'post_status'  => 'draft',
			'post_title'   => 'Draft Quote',
			'post_content' => '<!-- wp:core/quote --><!-- /wp:core/quote -->',
		) );

		$builder = new CatalogBuilder();
		$builder->catalog( $published );
		$builder->catalog( $draft );

		$result = $this->exporter->export( $this->tmp_file, array( 'post_type' => 'post' ) );
		$this->assertTrue( $result['success'] );

		$csv = file_get_contents( $this->tmp_file );
		$this->assertStringContainsString( 'Published Quote', $csv );
		$this->assertStringNotContainsString( 'Draft Quote', $csv );
	}

	function test_it_includes_non_published_posts_when_requested() {
		$taxonomy = new BlockCatalogTaxonomy();
		$taxonomy->register();

		$draft = $this->factory->post->create( array(
			'post_type'    => 'post',
			'post_status'  => 'draft',
			'post_title'   => 'Draft Quote',
			'post_content' => '<!-- wp:core/quote --><!-- /wp:core/quote -->',
		) );

		$builder = new CatalogBuilder();
		$builder->catalog( $draft );

		$result = $this->exporter->export( $this->tmp_file, array(
			'post_type'   => 'post',
			'post_status' => array( 'draft' ),
		) );
		$this->assertTrue( $result['success'] );

		$csv = file_get_contents( $this->tmp_file );
		$this->assertStringContainsString( 'Draft Quote', $csv );
	}

	function test_it_includes_qa_columns_in_the_header() {
		$taxonomy = new BlockCatalogTaxonomy();
		$taxonomy->register();

		$post_id = $this->factory->post->create( array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_content' => '<!-- wp:core/paragraph -->Hello<!-- /wp:core/paragraph -->',
		) );

		$builder = new CatalogBuilder();
		$builder->catalog( $post_id );

		$result = $this->exporter->export( $this->tmp_file, array( 'post_type' => 'post' ) );
		$this->assertTrue( $result['success'] );

		$csv = file_get_contents( $this->tmp_file );
		$this->assertStringContainsString( 'post_status,edit_link,post_author,post_date,post_modified,notes', $csv );
	}

	function test_it_escapes_csv_formula_injection() {
		$this->assertEquals( "'=1+1", $this->exporter->esc_csv( '=1+1' ) );
		$this->assertEquals( "'-2+3", $this->exporter->esc_csv( '-2+3' ) );
		$this->assertEquals( "'@cmd", $this->exporter->esc_csv( '@cmd' ) );
	}

	function test_it_quotes_csv_values_with_special_chars() {
		$this->assertEquals( '"a,b"', $this->exporter->esc_csv( 'a,b' ) );
		$this->assertEquals( "\"a\nb\"", $this->exporter->esc_csv( "a\nb" ) );
		$this->assertEquals( '"a""b"', $this->exporter->esc_csv( 'a"b' ) );
		$this->assertEquals( 'plain', $this->exporter->esc_csv( 'plain' ) );
	}

	function test_it_returns_all_catalog_terms_by_default() {
		$taxonomy = new BlockCatalogTaxonomy();
		$taxonomy->register();

		$post_id = $this->factory->post->create( array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_content' => '<!-- wp:core/quote --><!-- /wp:core/quote --><!-- wp:core/heading --><!-- /wp:core/heading -->',
		) );
		( new CatalogBuilder() )->catalog( $post_id );

		$slugs = wp_list_pluck( $this->exporter->get_block_catalog_terms(), 'slug' );

		$this->assertContains( 'core-quote', $slugs );
		$this->assertContains( 'core-heading', $slugs );
	}

	function test_it_restricts_catalog_terms_to_requested_blocks() {
		$taxonomy = new BlockCatalogTaxonomy();
		$taxonomy->register();

		$post_id = $this->factory->post->create( array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_content' => '<!-- wp:core/quote --><!-- /wp:core/quote --><!-- wp:core/heading --><!-- /wp:core/heading -->',
		) );
		( new CatalogBuilder() )->catalog( $post_id );

		$slugs = wp_list_pluck( $this->exporter->get_block_catalog_terms( array( 'blocks' => array( 'core-quote' ) ) ), 'slug' );

		$this->assertEquals( array( 'core-quote' ), $slugs );
	}

	function test_it_defaults_query_post_status_to_publish() {
		$args = $this->exporter->get_query_args( 'core-quote', array() );

		$this->assertEquals( 'publish', $args['post_status'] );
		$this->assertEquals( 'core-quote', $args['tax_query'][0]['terms'] );
		$this->assertEquals( BLOCK_CATALOG_TAXONOMY, $args['tax_query'][0]['taxonomy'] );
	}

	function test_it_honors_query_args_options() {
		$args = $this->exporter->get_query_args(
			'core-quote',
			array(
				'post_status'     => array( 'draft', 'publish' ),
				'posts_per_block' => 5,
				'post_type'       => array( 'post' ),
			)
		);

		$this->assertEquals( array( 'draft', 'publish' ), $args['post_status'] );
		$this->assertEquals( 5, $args['posts_per_page'] );
		$this->assertEquals( array( 'post' ), $args['post_type'] );
	}

	function test_it_skips_parent_terms_by_default() {
		$parent = (object) array( 'parent' => 0 );
		$child  = (object) array( 'parent' => 7 );

		$this->assertFalse( $this->exporter->can_export_term( $parent, array() ) );
		$this->assertTrue( $this->exporter->can_export_term( $child, array() ) );
	}

	function test_it_exports_parent_terms_when_ignore_parent_is_false() {
		$parent = (object) array( 'parent' => 0 );

		$this->assertTrue( $this->exporter->can_export_term( $parent, array( 'ignore_parent' => false ) ) );
	}

	function test_it_always_exports_explicitly_requested_blocks() {
		$parent = (object) array( 'parent' => 0 );

		$this->assertTrue( $this->exporter->can_export_term( $parent, array( 'blocks' => array( 'core-quote' ) ) ) );
	}

	function test_it_converts_pattern_names_to_block_slugs() {
		$this->assertEquals( 'pattern-foo-something', $this->exporter->patterns_to_block_slugs( 'foo/something' ) );
		$this->assertEquals( 'pattern-foo-a,pattern-bar-b', $this->exporter->patterns_to_block_slugs( 'foo/a, bar/b' ) );
		$this->assertEquals( '', $this->exporter->patterns_to_block_slugs( '' ) );
	}

}
