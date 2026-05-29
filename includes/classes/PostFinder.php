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
	 * @param array $blocks Blocks to search for.
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
			'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
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
	 * @param array $blocks Blocks to search for.
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
	 * @param array $blocks Blocks to search for.
	 * @param array $opts Options for the search.
	 * @return int Total number of posts.
	 */
	public function count( $blocks = [], $opts = [] ) {
		$slugs = $this->get_tax_query_terms( $blocks );

		if ( empty( $slugs ) ) {
			return 0;
		}

		$query_params = [
			'post_type'   => ! empty( $opts['post_type'] ) ? $opts['post_type'] : \BlockCatalog\Utility\get_supported_post_types(),
			'post_status' => ! empty( $opts['post_status'] ) ? $opts['post_status'] : 'any',
			'tax_query'   => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
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
	 * @param array $blocks Blocks to search for.
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
	 * Splits a raw --blocks filter value into a list of trimmed, non-empty tokens.
	 *
	 * @param string $value The raw --blocks option value, eg:- 'core/quote, core/*'
	 * @return array List of block tokens.
	 */
	public function parse_block_filter( $value ) {
		$tokens = explode( ',', (string) $value );
		$tokens = array_map( 'trim', $tokens );
		$tokens = array_filter( $tokens );

		return array_values( $tokens );
	}

	/**
	 * Checks if a --blocks token is an explicit namespace fan-out, eg:- 'core/*'.
	 *
	 * Only the exact 'namespace/*' form is supported, so the namespace itself must not
	 * contain a slash. Partial globs and sub-patterns are not treated as fan-outs.
	 *
	 * @param string $token The --blocks token.
	 * @return bool
	 */
	public function is_namespace_pattern( $token ) {
		if ( '/*' !== substr( $token, -2 ) ) {
			return false;
		}

		$namespace = substr( $token, 0, -2 );

		return '' !== $namespace && false === strpos( $namespace, '/' );
	}

	/**
	 * Returns the catalog slugs of all blocks within a namespace.
	 *
	 * Block terms are stored as children of their namespace's parent term (see
	 * CatalogBuilder::get_block_parent_term), so a namespace's blocks are the direct
	 * children of that parent term.
	 *
	 * @param string $ns The block namespace, eg:- 'core'.
	 * @return array List of child term slugs.
	 */
	public function get_namespace_blocks( $ns ) {
		$parent = get_term_by( 'slug', sanitize_title( $ns ), BLOCK_CATALOG_TAXONOMY );

		if ( empty( $parent ) ) {
			return [];
		}

		$slugs = get_terms(
			[
				'taxonomy'   => BLOCK_CATALOG_TAXONOMY,
				'parent'     => $parent->term_id,
				'hide_empty' => false,
				'fields'     => 'slugs',
			]
		);

		if ( is_wp_error( $slugs ) ) {
			return [];
		}

		return $slugs;
	}

	/**
	 * Resolves a raw --blocks filter value into catalog term slugs.
	 *
	 * Each token is either an explicit namespace fan-out (eg:- 'core/*') or a single
	 * block by name (eg:- 'core/quote') or slug (eg:- 'core-quote'). Tokens that don't
	 * match any indexed block are returned in 'unmatched' so the caller can report them.
	 *
	 * @param string $value The raw --blocks option value.
	 * @return array {
	 *     @type array $slugs     The resolved, unique term slugs.
	 *     @type array $unmatched The tokens that didn't match any indexed block.
	 * }
	 */
	public function resolve_block_filter( $value ) {
		$tokens    = $this->parse_block_filter( $value );
		$slugs     = [];
		$unmatched = [];

		foreach ( $tokens as $token ) {
			if ( $this->is_namespace_pattern( $token ) ) {
				$namespace = substr( $token, 0, -2 );
				$resolved  = $this->get_namespace_blocks( $namespace );
			} else {
				$resolved = $this->get_tax_query_terms( [ $token ] );
			}

			if ( empty( $resolved ) ) {
				$unmatched[] = $token;
			} else {
				$slugs = array_merge( $slugs, $resolved );
			}
		}

		return [
			'slugs'     => array_values( array_unique( $slugs ) ),
			'unmatched' => $unmatched,
		];
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
