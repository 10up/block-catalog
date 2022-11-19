<?php
/**
 * CatalogCommand
 *
 * @package BlockCatalog
 */

namespace BlockCatalog;

/**
 * The Block Catalog WP CLI allows you to index & find blocks used across a site.
 */
class CatalogCommand extends \WP_CLI_Command {

	/**
	 * Iterates through all posts and catalogs them one at a time.
	 *
	 * ## OPTIONS
	 *
	 * [--only=<only>]
	 * : Limits the command to the specified comma delimited post ids
	 *
	 * [--dry-run]
	 * : Runs catalog without saving changes to the DB.
	 *
	 * @param array $args Command args
	 * @param array $opts Command opts
	 */
	public function index( $args = [], $opts = [] ) {
		\BlockCatalog\Utility\start_bulk_operation();

		$dry_run = ! empty( $opts['dry-run'] );

		if ( $dry_run ) {
			\WP_CLI::warning( __( 'Running in Dry Run Mode, changes will not be saved ...', 'block-catalog' ) );
		}

		$post_ids = $this->get_posts_to_catalog( $opts );

		$total = count( $post_ids );

		// translators: %d is number of posts found
		$message      = sprintf( __( 'Cataloging %d Posts ...', 'block-catalog' ), $total );
		$progress_bar = \WP_CLI\Utils\make_progress_bar( $message, $total );
		$updated      = 0;
		$errors       = 0;

		$builder = new CatalogBuilder();

		foreach ( $post_ids as $post_id ) {
			$progress_bar->tick();

			if ( ! $dry_run ) {
				$result = $builder->catalog( $post_id, $opts );

				\BlockCatalog\Utility\clear_caches();
			} else {
				$result = $builder->get_post_block_terms( $post_id );
			}

			if ( is_wp_error( $result ) ) {
				$errors++;
			} else {
				$updated += count( $result );
			}
		}

		$progress_bar->finish();

		if ( ! empty( $updated ) ) {
			// translators: %1$d is the number of blocks updated, %2$d is the total posts
			\WP_CLI::success( sprintf( __( 'Block Catalog updated for %1$d block(s) across %2$d post(s).', 'block-catalog' ), $updated, $total ) );
		} else {
			// translators: %d is the total posts
			\WP_CLI::warning( sprintf( __( 'No updates were made across %d post(s).', 'block-catalog' ), $total ) );
		}

		if ( ! empty( $errors ) ) {
			// translators: %d is the total posts
			\WP_CLI::warning( sprintf( __( 'Failed to catalog %d post(s).', 'block-catalog' ), $errors ) );
		}

		\BlockCatalog\Utility\stop_bulk_operation();
	}

	/**
	 * Resets the Block Catalog by removing all catalog terms.
	 *
	 * ## OPTIONS
	 *
	 * @subcommand delete-index
	 * @param array $args Command args
	 * @param array $opts Command opts
	 */
	public function deleteIndex( $args = [], $opts = [] ) {
		$builder = new CatalogBuilder();
		$builder->delete_index( $opts );
	}

	/**
	 * Finds the list of posts having the specified block(s)
	 *
	 * ## OPTIONS
	 *
	 * <blocks>...
	 * : The block names to search for, eg:- core/embed
	 *
	 * [--index]
	 * : Where to re-index before searching.
	 *
	 * [--fields]
	 * : List of post fields to display
	 *
	 * [--format]
	 * : Output format, default table
	 *
	 * [--post_type]
	 * : Limit search to specified post types
	 *
	 * [--posts_per_page]
	 * : Number of posts to find per page, default 20
	 *
	 * [--post_status]
	 * : Post status of posts to search, default 'publish'
	 *
	 * [--count=<count>]
	 * : Prints total found posts, default true
	 *
	 * [--operator=<operator>]
	 * : The query operator to be used in the search clause. Default IN.
	 *
	 * @param array $args Command args
	 * @param array $opts Command opts
	 */
	public function find( $args = [], $opts = [] ) {
		if ( empty( $args ) ) {
			\WP_CLI::error( __( 'Please enter atleast one block name.', 'block-catalog' ) );
		}

		$catalog_terms = wp_count_terms(
			[
				'taxonomy'   => BLOCK_CATALOG_TAXONOMY,
				'hide_empty' => false,
			]
		);

		if ( empty( $catalog_terms ) && empty( $opts['index'] ) ) {
			\WP_CLI::error( __( 'Block Catalog index is empty, please run with --index.', 'block-catalog' ) );
		}

		if ( empty( $opts['fields'] ) ) {
			$opts['fields'] = [ 'ID', 'post_type', 'post_title' ];
		} else {
			$opts['fields'] = explode( ',', $opts['fields'] );
		}

		if ( empty( $opts['format'] ) ) {
			$opts['format'] = 'table';
		}

		if ( empty( $opts['posts_per_page'] ) ) {
			$opts['posts_per_page'] = 20;
		}

		if ( ! empty( $opts['post_type'] ) ) {
			$opts['post_type'] = explode( ',', $opts['post_type'] );
		}

		if ( ! isset( $opts['count'] ) ) {
			$opts['count'] = true;
		} else {
			$opts['count'] = filter_var( $opts['count'], FILTER_VALIDATE_BOOLEAN );
		}

		$post_id = intval( $args[0] );

		$builder = new CatalogBuilder();

		if ( ! empty( $opts['index'] ) ) {
			$this->index();
		}

		$slugs = array_map( 'sanitize_title', $args );

		foreach ( $slugs as $index => $slug ) {
			$slug_term = get_term_by( 'slug', $slug, BLOCK_CATALOG_TAXONOMY );

			if ( false === $slug_term ) {
				unset( $slugs[ $index ] );
			}
		}

		$slugs = array_values( $slugs );

		if ( empty( $slugs ) ) {
			\WP_CLI::error( __( 'No posts found.', 'block-catalog' ) );
		}

		$taxonomy = new BlockCatalogTaxonomy();
		$operator = ! empty( $opts['operator'] ) ? $opts['operator'] : 'IN';

		$query_params = [
			'post_type'      => ! empty( $opts['post_type'] ) ? $opts['post_type'] : \BlockCatalog\Utility\get_supported_post_types(),
			'post_status'    => ! empty( $opts['post_status'] ) ? $opts['post_status'] : 'any',
			'posts_per_page' => intval( $opts['posts_per_page'] ), // phpcs:ignore
			'tax_query'      => [
				[
					'taxonomy' => BLOCK_CATALOG_TAXONOMY,
					'field'    => 'slug',
					'terms'    => $slugs,
					'operator' => $operator,
				],
			],
		];

		$query = new \WP_Query( $query_params );
		$posts = $query->posts;

		if ( ! empty( $opts['count'] ) ) {
			// translators: %d is number of found posts
			\WP_CLI::success( sprintf( __( 'Found %d post(s)', 'block-catalog' ), $query->found_posts ) );
		}

		if ( empty( $posts ) ) {
			\WP_CLI::error( __( 'No posts found.', 'block-catalog' ) );
		}

		\WP_CLI\Utils\format_items( $opts['format'], $posts, $opts['fields'] );
	}

	/**
	 * Prints the list of blocks in the specified post.
	 *
	 * ## OPTIONS
	 *
	 * <post-id>
	 * : The post id to lookup blocks for.
	 *
	 * [--index]
	 * : Where to re-catalog the post before printing.
	 *
	 * @subcommand post-blocks
	 * @param array $args Command args
	 * @param array $opts Command opts
	 */
	public function list_post_blocks( $args = [], $opts = [] ) {
		if ( empty( $args ) ) {
			\WP_CLI::error( __( 'Please enter a valid post_id', 'block-catalog' ) );
		}

		$post_id = intval( $args[0] );

		$builder = new CatalogBuilder();

		if ( ! empty( $opts['index'] ) ) {
			$builder->catalog( $post_id );
		}

		$blocks = $builder->get_post_block_terms( $post_id, $opts );

		if ( empty( $blocks ) ) {
			\WP_CLI::error( __( 'No blocks found.', 'block-catalog' ) );
		}

		$block_items = array_map(
			function( $block_name ) {
				return [ 'Block' => $block_name ];
			},
			$blocks
		);

		\WP_CLI\Utils\format_items( 'table', $block_items, [ 'Block' ] );
	}

	/**
	 * Returns the list of post ids to migrate.
	 *
	 * @param array $opts Optional opts
	 * @return array
	 */
	private function get_posts_to_catalog( $opts = [] ) {
		if ( isset( $opts['only'] ) ) {
			$only = explode( ',', $opts['only'] );
			$only = array_map( 'intval', $only );
			$only = array_filter( $only );

			return $only;
		}

		$query_params = [
			'post_type'      => \BlockCatalog\Utility\get_supported_post_types(),
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => -1,
		];

		$query = new \WP_Query( $query_params );
		$posts = $query->posts;

		if ( empty( $posts ) ) {
			\WP_CLI::warning( __( 'No posts to catalog.', 'block-catalog' ) );
		}

		return $posts;
	}

}
