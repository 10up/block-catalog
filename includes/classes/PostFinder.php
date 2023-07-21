<?php
/**
 * PostFinder
 *
 * @package BlockCatalog
 */

namespace BlockCatalog;

/**
 * PostFinder searches for posts that have a specific block.
 */
class PostFinder {

	/**
	 * Find posts that have a specific block.
	 *
	 * opts can contain:
	 * - post_type: post type to search for.
	 * - post_status: post status to search for.
	 * - posts_per_page: maximum number of posts to return.
	 * - operator: query operator to use in the search.
	 *
	 * @param array $opts Options for the search.
	 * @return array
	 */
	public function find( $blocks, $opts = [] ) {
		if ( ! $this->is_indexed() ) {
			return new \WP_Error(
				'not-indexed',
				__( 'Block Catalog index is empty, please index the site first.', 'block-catalog' )
			);
		}

		$slugs = $this->get_tax_query_terms( $blocks );

		if ( empty( $slugs ) ) {
			return [];
		}

		$query_params = [
			'post_type'      => ! empty( $opts['post_type'] ) ? $opts['post_type'] : \BlockCatalog\Utility\get_supported_post_types(),
			'post_status'    => ! empty( $opts['post_status'] ) ? $opts['post_status'] : 'any',
			'posts_per_page' => ! empty( $opts['posts_per_page'] ) ? $opts['posts_per_page'] : 10,
			'tax_query'      => [
				[
					'taxonomy' => BLOCK_CATALOG_TAXONOMY,
					'field'    => 'slug',
					'terms'    => $slugs,
					'operator' => ! empty( $opts['operator'] ) ? $opts['operator'] : 'IN',
				],
			],
		];

		$query = new \WP_Query( $query_params );
		$posts = $query->posts;

		return $posts;
	}

	/**
	 * Find posts that have a specific block on a multisite network.
	 *
	 * @param array $sites Sites to search.
	 * @param array $opts Options for the search.
	 * @return array
	 */
	public function find_on_network( $sites = [], $blocks = [], $opts = [] ) {
		$found_posts = [];

		foreach ( $sites as $blog_id ) {
			switch_to_blog( $blog_id );

			$found_on_site = $this->find( $blocks, $opts );

			$result = [
				'blog_id'  => $blog_id,
				'blog_url' => get_site_url( $blog_id ),
				'posts'    => ! is_wp_error( $found_on_site ) ? $found_on_site : [],
			];

			if ( is_wp_error( $found_on_site ) ) {
				$result['error'] = $found_on_site;
			}

			$found_posts[] = $result;

			restore_current_blog();
		}

		return $found_posts;
	}

	/**
	 * Count posts that have a specific block.
	 *
	 * @param array $opts Options for the search.
	 * @return int Total number of posts.
	 */
	public function count( $blocks = [], $opts = [] ) {
		$slugs = $this->get_tax_query_terms( $blocks );

		if ( empty( $slugs ) ) {
			return 0;
		}

		$query_params = [
			'post_type'      => ! empty( $opts['post_type'] ) ? $opts['post_type'] : \BlockCatalog\Utility\get_supported_post_types(),
			'post_status'    => ! empty( $opts['post_status'] ) ? $opts['post_status'] : 'any',
			'tax_query'      => [
				[
					'taxonomy' => BLOCK_CATALOG_TAXONOMY,
					'field'    => 'slug',
					'terms'    => $slugs,
					'operator' => ! empty( $opts['operator'] ) ? $opts['operator'] : 'IN',
				],
			],
		];

		$query = new \WP_Query( $query_params );

		return $query->found_posts;
	}

	/**
	 * Count posts that have a specific block on a multisite network.
	 *
	 * @param array $sites Sites to search.
	 * @param array $opts Options for the search.
	 * @return int Total number of posts.
	 */
	public function count_on_network( $sites = [], $blocks = [], $opts = [] ) {
		$found_posts = [];

		foreach ( $sites as $blog_id ) {
			switch_to_blog( $blog_id );

			$found_on_site = $this->count( $blocks, $opts );
			$found_posts[] = [
				'blog_id'  => $blog_id,
				'blog_url' => get_site_url( $blog_id ),
				'count'    => $found_on_site,
			];

			restore_current_blog();
		}

		return $found_posts;
	}

	/**
	 * Converts the search query terms like 'core/block' into slugs like 'core-block'.
	 *
	 * @param array $args Query terms.
	 * @return array
	 */
	public function get_tax_query_terms( $args = [] ) {
		$slugs = [];

		foreach ( $args as $index => $arg ) {
			$slug      = sanitize_title( $arg );
			$slug_term = get_term_by( 'slug', $slug, BLOCK_CATALOG_TAXONOMY );

			/**
			 * Filters the slug term for a block query.
			 *
			 * @param string|false $slug      The slug for the block.
			 * @param string       $arg       The original argument.
			 * @param WP_Term|false $slug_term The term for the slug.
			 * @return string|false
			 */
			$slug_term = apply_filters( 'block_catalog_block_query_slug', $slug_term, $arg );

			if ( false !== $slug_term ) {
				$slugs[] = $slug;
			}
		}

		$slugs = array_values( $slugs );
		$slugs = array_unique( $slugs );

		return $slugs;
	}

	/**
	 * Checks if the Block Catalog taxonomy is indexed.
	 *
	 * @return bool
	 */
	public function is_indexed() {
		$catalog_terms = wp_count_terms(
			[
				'taxonomy'   => BLOCK_CATALOG_TAXONOMY,
				'hide_empty' => false,
			]
		);

		return ! empty( $catalog_terms );
	}

}
