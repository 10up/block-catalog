<?php

namespace BlockCatalog;

class PostFinderTest extends \WP_UnitTestCase {

	public $finder;
	public $builder;

	function setUp():void {
		parent::setUp();

		$this->finder  = new PostFinder();
		$this->builder = new CatalogBuilder();
	}

	function test_it_can_be_instantiated() {
		$this->assertInstanceOf( PostFinder::class, $this->finder );
	}

	function test_it_will_exclude_search_terms_that_dont_have_terms() {
		// create the core/block term
		$this->factory->term->create( [
			'taxonomy' => BLOCK_CATALOG_TAXONOMY,
			'slug'     => 'core-block',
		] );

		$actual = $this->finder->get_tax_query_terms( [ 'core/block', 'xyz/no-such-block' ] );

		$this->assertEquals( [ 'core-block' ], $actual );
	}

	function test_it_can_build_list_of_slugs_from_block_queries() {
		$this->factory->term->create( [
			'taxonomy' => BLOCK_CATALOG_TAXONOMY,
			'slug'     => 'core-block',
		] );

		$this->factory->term->create( [
			'taxonomy' => BLOCK_CATALOG_TAXONOMY,
			'slug'     => 'core-paragraph',
		] );

		$query  = [ 'core/block', 'core/paragraph' ];
		$actual = $this->finder->get_tax_query_terms( $query );

		$this->assertEquals( [ 'core-block', 'core-paragraph' ], $actual );
	}

	function test_it_can_count_posts_with_a_specific_block_on_a_site() {
		$this->factory->post->create_many( 3, [
			'post_type' => 'post',
			'post_status' => 'publish',
		] );

		$post_ids = $this->factory->post->create_many( 5, [
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/paragraph -->',
		] );

		$builder = new CatalogBuilder();

		foreach ( $post_ids as $post_id ) {
			$builder->catalog( $post_id );
		}

		$actual = $this->finder->count( [ 'core/paragraph' ], [
			'post_type' => 'post',
			'post_status' => 'publish',
		] );

		$this->assertEquals( 5, $actual );
	}

	function test_it_can_count_posts_with_a_specific_block_on_a_site_across_post_types() {
		$post_ids = $this->factory->post->create_many( 3, [
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/paragraph -->',
		] );

		$post_ids = array_merge( $post_ids, $this->factory->post->create_many( 5, [
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/paragraph -->',
		] ) );

		$builder = new CatalogBuilder();

		foreach ( $post_ids as $post_id ) {
			$builder->catalog( $post_id );
		}

		$actual = $this->finder->count( [ 'core/paragraph' ], [
			'post_type' => [ 'post', 'page' ],
			'post_status' => 'publish',
		] );

		$this->assertEquals( 8, $actual );
	}

	function test_it_can_count_posts_on_site_using_and_operator() {
		$post_ids = $this->factory->post->create_many( 2, [
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/button --><!-- /wp:core/button --><!-- wp:core/paragraph --><!-- /wp:core/paragraph -->',
		] );

		$post_ids = array_merge( $post_ids, $this->factory->post->create_many( 4, [
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/paragraph -->',
		] ) );

		$builder = new CatalogBuilder();

		foreach ( $post_ids as $post_id ) {
			$builder->catalog( $post_id );
		}

		$actual = $this->finder->count( [ 'core/button', 'core/paragraph' ], [
			'post_type' => [ 'post', 'page' ],
			'post_status' => 'publish',
			'operator' => 'AND',
		] );

		$this->assertEquals( 2, $actual );
	}

	function test_it_can_count_posts_on_site_using_or_operator() {
		$post_ids = $this->factory->post->create_many( 4, [
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/button --><!-- /wp:core/button --><!-- wp:core/paragraph --><!-- /wp:core/paragraph -->',
		] );

		$post_ids = array_merge( $post_ids, $this->factory->post->create_many( 7, [
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/paragraph -->',
		] ) );

		$builder = new CatalogBuilder();

		foreach ( $post_ids as $post_id ) {
			$builder->catalog( $post_id );
		}

		$actual = $this->finder->count( [ 'core/button', 'core/paragraph' ], [
			'post_type' => [ 'post', 'page' ],
			'post_status' => 'publish',
			'operator' => 'OR',
		] );

		$this->assertEquals( 11, $actual );
	}

	function test_it_can_count_posts_on_site_using_not_in_operator() {
		$post_ids = $this->factory->post->create_many( 4, [
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/button --><!-- /wp:core/button -->',
		] );

		$post_ids = array_merge( $post_ids, $this->factory->post->create_many( 6, [
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/paragraph -->',
		] ) );

		$builder = new CatalogBuilder();

		foreach ( $post_ids as $post_id ) {
			$builder->catalog( $post_id );
		}

		$actual = $this->finder->count( [ 'core/paragraph' ], [
			'post_type' => [ 'post', 'page' ],
			'post_status' => 'publish',
			'operator' => 'NOT IN',
		] );

		$this->assertEquals( 4, $actual );
	}

	function test_it_can_count_posts_across_network() {
		// first create a few blogs on the network
		$blog_ids = $this->factory->blog->create_many( 3 );

		// create a few posts on each blog
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );

			$this->factory->post->create_many( 3, [
				'post_type' => 'post',
				'post_status' => 'publish',
				'post_content' => '<!-- wp:core/paragraph -->',
			] );

			restore_current_blog();
		}

		// now count on the network
		$actual = $this->finder->count_on_network( $blog_ids, [ 'core/paragraph' ], [
			'post_type' => 'post',
			'post_status' => 'publish',
		] );

		$this->assertEquals( 3, $actual[0]['count'] );
		$this->assertEquals( 3, $actual[1]['count'] );
		$this->assertEquals( 3, $actual[2]['count'] );
	}

	function test_it_can_count_posts_across_post_types_across_network() {
		// first create a few blogs on the network
		$blog_ids = $this->factory->blog->create_many( 3 );

		// create a few posts on each blog
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );

			$this->factory->post->create_many( 2, [
				'post_type' => 'post',
				'post_status' => 'publish',
				'post_content' => '<!-- wp:core/paragraph -->',
			] );

			$this->factory->post->create_many( 5, [
				'post_type' => 'page',
				'post_status' => 'publish',
				'post_content' => '<!-- wp:core/paragraph -->',
			] );

			restore_current_blog();
		}

		// now count on the network
		$actual = $this->finder->count_on_network( $blog_ids, [ 'core/paragraph' ], [
			'post_type' => [ 'post', 'page' ],
			'post_status' => 'publish',
		] );

		$this->assertEquals( 7, $actual[0]['count'] );
		$this->assertEquals( 7, $actual[1]['count'] );
		$this->assertEquals( 7, $actual[2]['count'] );
	}

	function test_it_can_find_posts_with_mixed_blocks_across_network() {
		// first create a few blogs on the network
		$blog_ids = $this->factory->blog->create_many( 3 );

		// create a few posts on each blog
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );

			$this->factory->post->create_many( 2, [
				'post_type' => 'post',
				'post_status' => 'publish',
				'post_content' => '<!-- wp:core/paragraph --><!-- /wp:core/paragraph --><!-- wp:core/button --><!-- /wp:core/button -->',
			] );

			$this->factory->post->create_many( 5, [
				'post_type' => 'page',
				'post_status' => 'publish',
				'post_content' => '<!-- wp:core/paragraph --><!-- /wp:core/paragraph -->',
			] );

			restore_current_blog();
		}

		// now count on the network
		$actual = $this->finder->count_on_network( $blog_ids, [ 'core/paragraph', 'core/button' ], [
			'post_type' => [ 'post', 'page' ],
			'post_status' => 'publish',
			'operator' => 'AND',
		] );

		$this->assertEquals( 2, $actual[0]['count'] );
		$this->assertEquals( 2, $actual[1]['count'] );
		$this->assertEquals( 2, $actual[2]['count'] );
	}

	function test_it_knows_if_no_block_matches_across_the_network() {
		// first create a few blogs on the network
		$blog_ids = $this->factory->blog->create_many( 3 );

		// create a few posts on each blog
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );

			$this->factory->post->create_many( 2, [
				'post_type' => 'post',
				'post_status' => 'publish',
				'post_content' => '<!-- wp:core/paragraph --><!-- /wp:core/paragraph --><!-- wp:core/button --><!-- /wp:core/button -->',
			] );

			$this->factory->post->create_many( 5, [
				'post_type' => 'page',
				'post_status' => 'publish',
				'post_content' => '<!-- wp:core/paragraph --><!-- /wp:core/paragraph -->',
			] );

			restore_current_blog();
		}

		// now count on the network
		$actual = $this->finder->count_on_network( $blog_ids, [ 'core/heading' ], [
			'post_type' => [ 'post', 'page' ],
			'post_status' => 'publish',
			'operator' => 'AND',
		] );

		$this->assertEquals( 0, $actual[0]['count'] );
		$this->assertEquals( 0, $actual[1]['count'] );
		$this->assertEquals( 0, $actual[2]['count'] );
	}

	function test_it_can_find_blocks_on_single_site() {
		// create a few posts on the blog
		$post_ids = $this->factory->post->create_many( 2, [
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/paragraph --><!-- /wp:core/paragraph -->',
		] );

		$actual = $this->finder->find( [ 'core/paragraph' ], [
			'post_type' => 'post',
			'post_status' => 'publish',
		] );

		$actual = array_map( function( $post ) {
			return $post->ID;
		}, $actual );

		$this->assertContains( $post_ids[0], $actual );
		$this->assertContains( $post_ids[1], $actual );
	}

	function test_it_can_find_blocks_with_operators_on_single_site() {
		// create posts with core/paragraphs only
		$this->factory->post->create_many( 2, [
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/paragraph --><!-- /wp:core/paragraph -->',
		] );

		// create posts with core/paragraphs and core/buttons
		$post_ids = $this->factory->post->create_many( 2, [
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/paragraph --><!-- /wp:core/paragraph --><!-- wp:core/button --><!-- /wp:core/button -->',
		] );

		$actual = $this->finder->find( [ 'core/paragraph', 'core/button' ], [
			'post_type' => 'post',
			'post_status' => 'publish',
			'operator' => 'AND',
		] );

		$actual = array_map( function( $post ) {
			return $post->ID;
		}, $actual );

		// normalize the 2 arrays
		sort( $post_ids );
		sort( $actual );

		$this->assertEquals( $post_ids, $actual );
	}

	function test_it_can_find_posts_across_post_types_with_operators_across_network() {
		// first create a few blogs on the network
		$blog_ids = $this->factory->blog->create_many( 3 );
		$post_ids = [];

		// create a few posts on each blog
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );

			$post_ids = array_merge( $post_ids, $this->factory->post->create_many( 2, [
				'post_type' => 'post',
				'post_status' => 'publish',
				'post_content' => '<!-- wp:core/paragraph --><!-- /wp:core/paragraph --><!-- wp:core/button --><!-- /wp:core/button -->',
			] ) );

			$this->factory->post->create_many( 5, [
				'post_type' => 'page',
				'post_status' => 'publish',
				'post_content' => '<!-- wp:core/paragraph --><!-- /wp:core/paragraph -->',
			] );

			restore_current_blog();
		}

		// now count on the network
		$actual = $this->finder->find_on_network( $blog_ids, [ 'core/paragraph', 'core/button' ], [
			'post_type' => [ 'post', 'page' ],
			'post_status' => 'publish',
			'operator' => 'AND',
		] );

		$this->assertEquals( 2, count( $actual[0]['posts'] ) );
		$this->assertEquals( 2, count( $actual[1]['posts'] ) );
		$this->assertEquals( 2, count( $actual[2]['posts'] ) );
	}

	function test_it_knows_if_site_is_not_indexed() {
		$this->assertFalse( $this->finder->is_indexed() );
	}

	function test_it_knows_if_site_is_indexed() {
		$this->factory->post->create_many( 2, [
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_content' => '<!-- wp:core/paragraph --><!-- /wp:core/paragraph -->',
		] );

		$this->assertTrue( $this->finder->is_indexed() );
	}

	function test_it_returns_error_if_find_is_called_with_empty_index() {
		$actual = $this->finder->find( [ 'core/paragraph' ], );
		$this->assertEquals( 'not-indexed', $actual->get_error_code() );
	}

	function test_it_returns_list_of_errors_if_finding_fails_across_network() {
		// create a few blogs on the network
		$blog_ids = $this->factory->blog->create_many( 3 );

		$actual = $this->finder->find_on_network( $blog_ids, [ 'core/paragraph' ], [
			'post_type' => 'post',
			'post_status' => 'publish',
		] );

		$this->assertEquals( 'not-indexed', $actual[0]['error']->get_error_code() );
		$this->assertEquals( 'not-indexed', $actual[1]['error']->get_error_code() );
		$this->assertEquals( 'not-indexed', $actual[2]['error']->get_error_code() );
	}

}
