<?php

namespace BlockCatalog;

class BlockCatalogTaxonomyTest extends \WP_UnitTestCase {

	public $taxonomy;

	function setUp():void {
		parent::setUp();

		$this->taxonomy = new BlockCatalogTaxonomy();
	}

	function test_it_is_not_a_public_taxonomy() {
		$this->assertFalse( $this->taxonomy->get_options()['public'] );
	}

	function test_it_is_not_visible_in_gutenberg() {
		$this->assertFalse( $this->taxonomy->get_options()['show_in_rest'] );
	}

	function test_it_has_hook_to_extend_taxonomy_options() {
		add_filter( 'block_catalog_taxonomy_options', function( $options ) {
			$options['foo'] = 'bar';

			return $options;
		} );

		$actual = $this->taxonomy->get_options();
		$this->assertEquals( 'bar', $actual['foo'] );
	}

}
