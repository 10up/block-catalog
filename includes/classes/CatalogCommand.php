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
	 * The list of sites obtained from the --network option.
	 *
	 * @var array
	 */
	public $network;

	/**
	 * Iterates through all posts and catalogs them one at a time.
	 *
	 * ## OPTIONS
	 *
	 * [--only=<only>]
	 * : Limits the command to the specified comma delimited post ids.
	 *
	 * [--reset]
	 * : Deletes the previous index before indexing. Default false.
	 *
	 * [--network]
	 * : Runs the command for all sites on a multisite install. Defaults to all
	 * public sites. Accepts all, public, archived, mature, spam, deleted, or
	 * limit to comma delimited list of site ids.
	 *
	 * [--dry-run]
	 * : Runs catalog without saving changes to the DB.
	 *
	 * @param array $args Command args
	 * @param array $opts Command opts
	 */
	public function index( $args = [], $opts = [] ) {
		$this->check_network_option( $opts );

		\BlockCatalog\Utility\start_bulk_operation();

		$dry_run = ! empty( $opts['dry-run'] );

		if ( ! is_multisite() ) {
			$opts['show_dry_run_warning'] = true;
			$this->index_site( $args, $opts );
		} else {
			$blog_ids = $this->get_network_option( $opts );
			$opts['show_dry_run_warning'] = false;

			if ( $dry_run ) {
				\WP_CLI::warning( __( 'Running in Dry Run Mode, changes will not be saved ...', 'block-catalog' ) );
			}

			if ( ! empty( $blog_ids ) ) {
				foreach ( $blog_ids as $blog_id ) {
					$site = get_blog_details( $blog_id );
					switch_to_blog( $blog_id );

					\WP_CLI::log( "Indexing Block Catalog for site[{$site->blog_id}]: " . $site->domain . $site->path );
					$this->index_site( $args, $opts );

					restore_current_blog();
					\WP_CLI::line();
				}
			}
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
	public function delete_index( $args = [], $opts = [] ) {
		$builder = new CatalogBuilder();
		$builder->delete_index( $opts );
	}

	/**
	 * Finds the list of posts having the specified block(s).
	 *
	 * ## OPTIONS
	 *
	 * <blocks>...
	 * : The block names to search for, eg:- core/embed
	 *
	 * [--index]
	 * : Whether to re-index before searching.
	 *
	 * [--fields=<fields>]
	 * : List of post fields to display. Comma delimited.
	 *
	 * [--format=<format>]
	 * : Output format, default table.
	 *
	 * [--post_type=<post_type>]
	 * : Limit search to specified post types. Comma delimited.
	 *
	 * [--posts_per_page]
	 * : Number of posts to find per page, default 20.
	 *
	 * [--post_status]
	 * : Post status of posts to search, default 'publish'.
	 *
	 * [--count=<count>]
	 * : Prints total found posts, default true.
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

		if ( ! empty( $opts['count'] ) && ! empty( $posts ) ) {
			// translators: %d is number of found posts
			\WP_CLI::success( sprintf( __( 'Found %d post(s)', 'block-catalog' ), $query->found_posts ) );
		}

		if ( empty( $posts ) ) {
			\WP_CLI::warning( __( 'No posts found.', 'block-catalog' ) );
		}

		if ( ! empty( $posts ) ) {
			\WP_CLI\Utils\format_items( $opts['format'], $posts, $opts['fields'] );
		}
	}

	/**
	 * Prints the list of blocks in the specified post.
	 *
	 * ## OPTIONS
	 *
	 * <post-id>
	 * : The post id to lookup blocks for.
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
		$builder->catalog( $post_id );

		$blocks = wp_get_object_terms( $post_id, BLOCK_CATALOG_TAXONOMY );

		if ( empty( $blocks ) ) {
			\WP_CLI::error( __( 'No blocks found.', 'block-catalog' ) );
		}

		$block_items = array_map(
			function( $term ) {
				return [
					'Block' => $term->name,
					'ID'    => $term->term_id,
				];
			},
			$blocks
		);

		\WP_CLI\Utils\format_items( 'table', $block_items, [ 'ID', 'Block' ] );
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

	/**
	 * Returns the --network option, and a default value if not set.
	 *
	 * @param array $opts Optional opts
	 * @return string|array
	 */
	private function get_network_option( $opts ) {
		if ( ! is_multisite() ) {
			return '';
		}

		if ( ! empty( $this->network ) ) {
			return $this->network;
		}

		$network = \WP_CLI\Utils\get_flag_value( $opts, 'network', 'public' );

		// assume networks with commas are ids
		if ( is_string( $network ) && false !== strpos( $network, ',' ) ) {
			$network = explode( ',', $network );
		}

		$this->network = $this->get_site_ids_from_network( $network );

		return $this->network;
	}

	/**
	 * Validates if the --network option can be used on the current install, and
	 * throws an error if not.
	 *
	 * @param array $opts Optional opts
	 * @return void
	 */
	private function check_network_option( $opts ) {
		if ( ! is_multisite() && isset( $opts['network'] ) ) {
			\WP_CLI::error( __( 'The --network option can only be used on multisite installs.', 'block-catalog' ) );
			return false;
		}

		return true;
	}

	/**
	 * Returns the site ids from the --network option.
	 *
	 * @param string|array $network The network option value.
	 * @return array
	 */
	private function get_site_ids_from_network( $network ) {
		$query_params = [
			'fields' => 'ids',
		];

		$accepted = [ 'public', 'archived', 'spam', 'deleted' ];

		if ( is_string( $network ) && in_array( $network, $accepted, true ) ) {
			$query_params[ $network ] = 1;
		} elseif ( is_array( $network ) && ! empty( $network) && is_numeric( $network[0] ) ) {
			// list of site ids
			$query_params['site__in'] = $network;
		} else {
			$query_params['site__in'] = [];
		}

		$query = new \WP_Site_Query( $query_params );
		$sites = $query->get_sites();

		return $sites;
	}

	private function index_site( $args = [], $opts = [] ) {
		$dry_run = ! empty( $opts['dry-run'] );
		$reset   = ! empty( $opts['reset'] );

		if ( $dry_run && $opts['show_dry_run_warning'] ) {
			\WP_CLI::warning( __( 'Running in Dry Run Mode, changes will not be saved ...', 'block-catalog' ) );
		}

		if ( ! $dry_run && $reset ) {
			$this->delete_index();
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
				$updated += count( $result['terms'] ?? [] );
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
	}

}
