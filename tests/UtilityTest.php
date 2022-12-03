<?php

namespace BlockCatalog\Utility;

class UtilityTests extends \WP_UnitTestCase {

	function test_it_excludes_builtin_post_types() {
		$actual = get_supported_post_types();

		$this->assertNotContains( 'attachment', $actual );
		$this->assertNotContains( 'wp_block', $actual );
		$this->assertNotContains( 'wp_template', $actual );
		$this->assertNotContains( 'wp_template_part', $actual );
		$this->assertNotContains( 'wp_navigation', $actual );
	}

	function test_it_excludes_known_internal_post_types() {
		$actual = get_supported_post_types();

		$this->assertNotContains( 'feedback', $actual );
		$this->assertNotContains( 'jp_pay_order', $actual );
		$this->assertNotContains( 'jp_pay_product', $actual );
		$this->assertNotContains( 'dt_subscription', $actual );
	}

	function test_it_includes_core_default_post_types() {
		$actual = get_supported_post_types();

		$this->assertContains( 'post', $actual );
		$this->assertContains( 'page', $actual );
	}

	function test_it_does_not_include_classic_post_types_by_default() {
		if ( ! post_type_exists( 'foo1' ) ) {
			register_post_type( 'foo1', [ 'name' => 'Foo1' ] );
		}

		$actual = get_supported_post_types();
		$this->assertNotContains( 'foo1', $actual );
	}

	function test_it_includes_gutenberg_post_types_by_default() {
		if ( ! post_type_exists( 'foo2' ) ) {
			register_post_type( 'foo2', [ 'name' => 'Foo2', 'show_in_rest' => true ] );
		}

		$actual = get_supported_post_types();
		$this->assertContains( 'foo2', $actual );
	}

	function test_it_can_be_extended_to_curated_post_types_with_hook() {
		add_filter( 'block_catalog_post_types', function( $post_types ) {
			return [
				'foo1',
				'foo2',
			];
		} );

		$this->assertEquals( [ 'foo1', 'foo2' ], get_supported_post_types() );
	}

	function test_it_has_default_capability() {
		$this->assertEquals( 'edit_posts', get_required_capability() );
	}

	function test_it_can_override_default_capability() {
		add_filter( 'block_catalog_capability', function( $cap ) {
			return 'manage_options';
		} );

		$this->assertEquals( 'manage_options', get_required_capability() );
	}
}
