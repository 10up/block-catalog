<?php

namespace BlockCatalog;

class BlockCatalogTaxonomyTest extends \WP_UnitTestCase {

	public $taxonomy;

	function setUp() {
		$this->taxonomy = new BlockCatalogTaxonomy();
	}

	function test_it_has_a_taxonomy_name() {
		$this->assertEquals( 'block-catalog', $this->taxonomy->get_name() );
	}

	function test_it_has_a_singular_label() {
		$this->assertNotEmpty( $this->taxonomy->get_singular_label() );
	}

	function test_it_has_a_plural_label() {
		$this->assertNotEmpty( $this->taxonomy->get_plural_label() );
	}

	function test_it_is_not_a_public_taxonomy() {
		$this->assertFalse( $this->taxonomy->get_options()['public'] );
	}

	function test_it_is_not_visible_in_gutenberg() {
		$this->assertFalse( $this->taxonomy->get_options()['show_in_rest'] );
	}

	function test_it_is_a_hierarchical_taxonomy() {
		$this->assertTrue( $this->taxonomy->get_options()['hierarchical'] );
	}

	function test_it_has_hook_to_extend_taxonomy_options() {
		add_filter( 'block_catalog_taxonomy_options', function( $options ) {
			$options['foo'] = 'bar';

			return $options;
		} );

		$actual = $this->taxonomy->get_options();
		$this->assertEquals( 'bar', $actual['foo'] );
	}

	function test_it_can_be_registered() {
		if ( taxonomy_exists( BLOCK_CATALOG_TAXONOMY ) ) {
			unregister_taxonomy( BLOCK_CATALOG_TAXONOMY );
		}

		$this->taxonomy->register();

		$this->assertTrue( taxonomy_exists( BLOCK_CATALOG_TAXONOMY ) );
	}

}
