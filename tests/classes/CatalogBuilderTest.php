<?php

namespace BlockCatalog;

class CatalogBuilderTest extends \WP_UnitTestCase {

	public $builder;
	public $term_factory;

	function setUp():void {
		parent::setUp();

		$this->builder      = new CatalogBuilder();
		$this->term_factory = new \WP_UnitTest_Factory_For_Term( $this, BLOCK_CATALOG_TAXONOMY );
	}

	function get_block_type( $name ) {
		return \WP_Block_Type_Registry::get_instance()->get_registered( $name );
	}

	function test_it_can_build_title_of_block_from_block_name() {
		$this->assertEquals( 'Foo Bar', $this->builder->get_display_title( 'Foo Bar' ) );
		$this->assertEquals( 'Foo Bar', $this->builder->get_display_title( 'foo_bar' ) );
		$this->assertEquals( 'Foo Bar', $this->builder->get_display_title( 'foo-bar' ) );
		$this->assertEquals( 'Boxes With Lines', $this->builder->get_display_title( 'boxes-with-lines' ) );
	}

	function test_it_will_use_reusable_block_name_as_parent_if_block_is_reusable() {
		$actual = $this->builder->get_block_parent_name( 're-555' );
		$this->assertEquals( 'Reusable block', $actual );
	}

	function test_it_will_use_core_namespace_as_parent_for_core_blocks() {
		$actual = $this->builder->get_block_parent_name( 'core/embed' );
		$this->assertEquals( 'core', $actual );
	}

	function test_it_will_use_custom_namespace_as_parent_for_custom_blocks() {
		$actual = $this->builder->get_block_parent_name( 'lorem-ipsum/dolor' );
		$this->assertEquals( 'lorem-ipsum', $actual );
	}

	function test_it_will_not_fail_if_invalid_block_name() {
		$actual = $this->builder->get_block_parent_name( 'lorem-ipsum-dolor' );
		$this->assertEquals( '', $actual );
	}

	function test_it_can_customize_parent_label_with_hook() {
		add_filter( 'block_catalog_namespace_label', function( $label, $name ) {
			if ( 'foo/bar' === $name ) {
				return 'Foo Label';
			}

			return $label;
		}, 10, 2 );

		$actual = $this->builder->get_block_parent_name( 'foo/bar' );
		$this->assertEquals( 'Foo Label', $actual );
	}

	function test_it_knows_empty_block_name_has_no_parent_term() {
		$this->assertFalse( $this->builder->get_block_parent_term( '' ) );
	}

	function test_it_can_find_parent_id_for_existing_parent_term() {
		$term = $this->term_factory->create( [ 'name' => 'foo' ] );

		$actual = $this->builder->get_block_parent_term( 'foo/bar' );
		$this->assertEquals( $term, $actual );
	}

	function test_it_will_create_parent_term_from_block_name_if_absent() {
		$actual = $this->builder->get_block_parent_term( 'foo1/bar' );
		$this->assertNotEmpty( $actual );
	}

	function test_it_will_use_registered_block_title_as_label_if_present() {
		register_block_type( 'ns/foo1', [ 'title' => 'Registered Title' ] );

		$actual = $this->builder->get_block_label( [ 'blockName' => 'ns/foo1' ] );
		$this->assertEquals( 'Registered Title', $actual );
	}

	function test_it_will_use_fallback_title_as_label_if_title_is_absent() {
		register_block_type( 'ns/my-custom-block', [] );

		$actual = $this->builder->get_block_label( [ 'blockName' => 'ns/my-custom-block' ] );
		$this->assertEquals( 'My Custom Block', $actual );
	}

	function test_it_can_override_block_title_via_hook() {
		add_filter( 'block_catalog_block_title', function( $title, $name, $block ) {
			if ( 'ns/foo2' === $name ) {
				return 'My Filtered Title';
			}

			return $title;
		}, 10, 3 );

		register_block_type( 'ns/foo2', [ 'title' => 'Registered Title' ] );

		$actual = $this->builder->get_block_label( [ 'blockName' => 'ns/foo2' ] );
		$this->assertEquals( 'My Filtered Title', $actual );
	}

	// Deprecated: For WP < 5.0.0
	function _test_it_will_use_full_title_if_block_has_no_namespace() {
		register_block_type( 'my-custom-block', [] );

		$actual = $this->builder->get_block_label( [ 'blockName' => 'my-custom-block' ] );
		$this->assertEquals( 'My Custom Block', $actual );
	}

	function test_it_knows_empty_block_has_no_terms() {
		$this->assertEmpty( $this->builder->block_to_terms( '' )['terms'] );
	}

	function test_it_knows_empty_block_name_has_no_terms() {
		$this->assertEmpty( $this->builder->block_to_terms( [] )['terms'] );
	}

	function test_it_uses_title_of_reusable_block_as_term() {
		$post_id = $this->factory->post->create( [ 'post_type' => 'wp_block', 'post_title' => 'My Reusable1' ] );

		$block  = [
			'blockName' => 'core/block',
			'attrs' => [
				'ref' => $post_id,
			]
		];

		$actual = $this->builder->block_to_terms( $block );
		$this->assertEquals( [ "re-$post_id" => 'My Reusable1' ], $actual['terms'] );
	}

	function test_it_uses_block_label_as_term_for_non_reusable_blocks() {
		register_block_type( 'ns/foo11', [ 'title' => 'Registered Title' ] );

		$actual = $this->builder->block_to_terms( [ 'blockName' => 'ns/foo11' ] );

		$this->assertEquals( [ 'ns/foo11' => 'Registered Title' ], $actual['terms'] );
	}

	function test_it_can_customize_block_terms_with_hook() {
		register_block_type( 'ns/foo31', [ 'title' => 'Registered Title' ] );

		add_filter( 'block_catalog_block_terms', function( $terms, $block ) {
			if ( 'ns/foo31' === $block['blockName'] ) {
				$terms['ns/foo31-style1'] = 'Style 1';
				$terms['ns/foo31-style2'] = 'Style 2';
			}

			return $terms;
		}, 10, 2 );

		$actual = $this->builder->block_to_terms( [ 'blockName' => 'ns/foo31' ] );

		$expected = [
			'ns/foo31'        => 'Registered Title',
			'ns/foo31-style1' => 'Style 1',
			'ns/foo31-style2' => 'Style 2',
		];

		$this->assertEquals( $expected, $actual['terms'] );
	}

	function test_it_can_blocks_to_list() {
		$content = file_get_contents( FIXTURES_DIR . '/all-headings.html' );
		$blocks  = parse_blocks( $content );

		$actual = $this->builder->to_block_list( $blocks );

		$this->assertNotEmpty( $actual );

		foreach ( $actual as $block ) {
			$this->assertNotEmpty( $block['blockName'] );
			$this->assertEquals( 'core/heading', $block['blockName'] );
		}
	}

	function test_it_can_flatten_nested_blocks_to_list() {
		$content = file_get_contents( FIXTURES_DIR . '/nested-blocks.html' );
		$blocks  = parse_blocks( $content );

		$actual = $this->builder->to_block_list( $blocks );

		$this->assertNotEmpty( $actual );

		foreach ( $actual as $block ) {
			$this->assertNotEmpty( $block['blockName'] );
		}
	}

	function test_it_can_build_post_block_terms() {
		$content = file_get_contents( FIXTURES_DIR . '/all-headings.html' );
		$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

		$actual = $this->builder->get_post_block_terms( $post_id );
		$expected = [
			'core/heading' => 'Heading',
		];

		$this->assertEquals( $expected, $actual['terms'] );
	}

	function test_it_can_build_nested_post_block_terms() {
		$content = file_get_contents( FIXTURES_DIR . '/nested-blocks.html' );
		$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

		$actual = $this->builder->get_post_block_terms( $post_id );
		$expected = [
			'core/column'    => 'Column',
			'core/columns'   => 'Columns',
			'core/list'      => 'List',
			'core/list-item' => 'List item',
			'core/paragraph' => 'Paragraph',
			'core/quote'     => 'Quote',
		];

		$this->assertEquals( $expected, $actual['terms'] );
	}

	function test_it_can_build_block_variation_terms() {
		add_filter( 'block_catalog_block_variations', function( $variations, $block ) {
			if ( 'core/heading' === $block['blockName'] ) {
				$level = ! empty( $block['attrs']['level'] ) ? 'h' . $block['attrs']['level'] : 'h2';
				return [ $level ];
			}

			return $variations;
		}, 10, 2 );

		$content = file_get_contents( FIXTURES_DIR . '/all-headings.html' );
		$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

		$actual = $this->builder->get_post_block_terms( $post_id );
		$expected = [
			[ 'h1' ],
			[ 'h2' ],
			[ 'h3' ],
			[ 'h4' ],
			[ 'h5' ],
			[ 'h6' ],
		];

		$this->assertEquals( $expected, array_column( $actual['variations'], 'terms' ) );
	}

	function test_it_can_set_post_block_terms() {
		$content = file_get_contents( FIXTURES_DIR . '/all-headings.html' );
		$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

		$terms = $this->builder->get_post_block_terms( $post_id );
		$this->builder->set_post_block_terms( $post_id, $terms );

		$actual = wp_get_object_terms( $post_id, BLOCK_CATALOG_TAXONOMY, [ 'fields' => 'names' ] );

		$this->assertContains( 'Core', $actual );
		$this->assertContains( 'Heading', $actual );
		$this->assertNotContains( 'Paragraph', $actual );
	}

	function test_it_can_set_nested_post_block_terms() {
		$content = file_get_contents( FIXTURES_DIR . '/nested-blocks.html' );
		$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

		$terms = $this->builder->get_post_block_terms( $post_id );
		$this->builder->set_post_block_terms( $post_id, $terms );

		$actual = wp_get_object_terms( $post_id, BLOCK_CATALOG_TAXONOMY, [ 'fields' => 'names' ] );

		$this->assertContains( 'Column', $actual );
		$this->assertContains( 'Columns', $actual );
		$this->assertContains( 'List', $actual );
		$this->assertContains( 'List item', $actual );
		$this->assertContains( 'Paragraph', $actual );
		$this->assertContains( 'Quote', $actual );
		$this->assertContains( 'Core', $actual );
	}

	function test_it_can_set_post_block_terms_with_variations() {
		add_filter( 'block_catalog_block_variations', function( $variations, $block ) {
			if ( 'core/heading' === $block['blockName'] ) {
				$level = ! empty( $block['attrs']['level'] ) ? 'h' . $block['attrs']['level'] : 'h2';
				return [ $level ];
			}

			return $variations;
		}, 10, 2 );

		$content = file_get_contents( FIXTURES_DIR . '/all-headings.html' );
		$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

		$terms = $this->builder->get_post_block_terms( $post_id );
		$this->builder->set_post_block_terms( $post_id, $terms );

		$actual = wp_get_object_terms( $post_id, BLOCK_CATALOG_TAXONOMY, [ 'fields' => 'names' ] );

		$this->assertContains( 'Core', $actual );
		$this->assertContains( 'Heading', $actual );
		$this->assertContains( 'h1', $actual );
		$this->assertContains( 'h2', $actual );
		$this->assertContains( 'h3', $actual );
		$this->assertContains( 'h4', $actual );
		$this->assertContains( 'h5', $actual );
		$this->assertContains( 'h6', $actual );

		$parent = get_term_by( 'slug', 'core', BLOCK_CATALOG_TAXONOMY );
		$child  = get_term_by( 'slug', 'core/heading', BLOCK_CATALOG_TAXONOMY );

		$this->assertEquals( $child->parent, $parent->term_id );

		$parent = get_term_by( 'slug', 'core/heading', BLOCK_CATALOG_TAXONOMY );
		$child  = get_term_by( 'slug', 'core/heading-h1', BLOCK_CATALOG_TAXONOMY );

		$this->assertEquals( $child->parent, $parent->term_id );
	}

	function test_it_can_clear_post_block_terms() {
		$content = file_get_contents( FIXTURES_DIR . '/nested-blocks.html' );
		$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

		$terms = $this->builder->get_post_block_terms( $post_id );
		$this->builder->set_post_block_terms( $post_id, $terms );

		$actual = wp_get_object_terms( $post_id, BLOCK_CATALOG_TAXONOMY, [ 'fields' => 'names' ] );

		$this->assertNotEmpty( $actual );

		wp_update_post( [ 'ID' => $post_id, 'post_content' => ' ' ] );

		$terms = $this->builder->get_post_block_terms( $post_id );
		$this->builder->set_post_block_terms( $post_id, $terms );

		$actual = wp_get_object_terms( $post_id, BLOCK_CATALOG_TAXONOMY, [ 'fields' => 'names' ] );
		$this->assertEmpty( $actual );
	}

	function test_it_can_delete_one_block_catalog_term() {
		$content = file_get_contents( FIXTURES_DIR . '/nested-blocks.html' );
		$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

		$terms = $this->builder->get_post_block_terms( $post_id );
		$this->builder->set_post_block_terms( $post_id, $terms );

		$term = get_term_by( 'slug', 'core/quote', BLOCK_CATALOG_TAXONOMY );
		$this->builder->delete_term_index( $term->term_id );

		$actual = wp_get_object_terms( $post_id, BLOCK_CATALOG_TAXONOMY, [ 'fields' => 'names' ] );

		$this->assertContains( 'Core', $actual );
		$this->assertNotContains( 'Quote', $actual );
	}

	function test_it_can_catalog_post_without_blocks() {
		$total = 5;

		for ( $i = 0; $i < $total; $i++ ) {
			$post_id = $this->factory->post->create( [ 'post_content' => '' ] );
			$this->builder->catalog( $post_id );
		}

		$actual = wp_get_object_terms( $post_id, BLOCK_CATALOG_TAXONOMY, [ 'fields' => 'names' ] );
		$this->assertEmpty( $actual );
	}

	function test_it_can_catalog_post_with_blocks() {
		$total = 5;

		for ( $i = 0; $i < $total; $i++ ) {
			$content = file_get_contents( FIXTURES_DIR . '/all-headings.html' );
			$post_id = $this->factory->post->create( [ 'post_content' => $content ] );
			$this->builder->catalog( $post_id );
		}

		$actual = get_term_by( 'slug', 'core', BLOCK_CATALOG_TAXONOMY );
		$this->assertEquals( 5, $actual->count );
	}

	function test_it_can_catalog_post_with_nested_blocks() {
		$total = 5;

		for ( $i = 0; $i < $total; $i++ ) {
			$content = file_get_contents( FIXTURES_DIR . '/nested-blocks.html' );
			$post_id = $this->factory->post->create( [ 'post_content' => $content ] );
			$this->builder->catalog( $post_id );
		}

		$actual = get_term_by( 'slug', 'core', BLOCK_CATALOG_TAXONOMY );
		$this->assertEquals( 5, $actual->count );

		$actual = get_term_by( 'slug', 'core/columns', BLOCK_CATALOG_TAXONOMY );
		$this->assertEquals( 5, $actual->count );
	}

	function test_it_can_delete_block_catalog_index() {
		$total = 5;

		for ( $i = 0; $i < $total; $i++ ) {
			$content = file_get_contents( FIXTURES_DIR . '/nested-blocks.html' );
			$post_id = $this->factory->post->create( [ 'post_content' => $content ] );

			$this->builder->catalog( $post_id );
		}

		$result = $this->builder->delete_index();

		$actual = get_terms( [ 'taxonomy' => BLOCK_CATALOG_TAXONOMY ] );
		$this->assertEmpty( $actual );
	}

}
