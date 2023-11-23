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
	 * public sites. Also accepts a comma delimited list of site ids.
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
		$network = $this->get_network_option( $opts );

		if ( empty( $network ) ) {
			$opts['show_dry_run_warning'] = true;
			$this->index_site( $args, $opts );
		} else {
			$blog_ids                     = $network;
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
	 * [--network]
	 * : Deletes the catalog for all sites on a multisite install.
	 *
	 * @subcommand delete-index
	 * @param array $args Command args
	 * @param array $opts Command opts
	 */
	public function delete_index( $args = [], $opts = [] ) {
		$this->check_network_option( $opts );

		$builder = new CatalogBuilder();
		$network = $this->get_network_option( $opts );

		if ( ! empty( $network ) ) {
			foreach ( $network as $blog_id ) {
				$site = get_blog_details( $blog_id );
				switch_to_blog( $blog_id );

				\WP_CLI::log( "Deleting Block Catalog for site[{$site->blog_id}]: " . $site->domain . $site->path );
				$builder->delete_index();

				restore_current_blog();
				\WP_CLI::line();
			}
		} else {
			$builder->delete_index( $opts );
		}
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
	 * [--count]
	 * : Prints total found posts, default false.
	 *
	 * [--operator=<operator>]
	 * : The query operator to be used in the search clause. Default IN.
	 *
	 * [--network]
	 * : Runs the command for all sites on a multisite install. Defaults to all
	 * public sites. Also accepts a comma delimited list of site ids.
	 *
	 * @param array $args Command args
	 * @param array $opts Command opts
	 */
	public function find( $args = [], $opts = [] ) {
		$this->check_network_option( $opts );

		if ( empty( $args ) ) {
			\WP_CLI::error( __( 'Please enter atleast one block name.', 'block-catalog' ) );
		}

		$network = $this->get_network_option( $opts );
		$count   = $this->get_count_option( $opts );

		if ( ! empty( $count ) ) {
			if ( ! empty( $network ) ) {
				$this->count_on_network( $network, $args, $opts );
			} else {
				$this->count_on_site( $args, $opts );
			}
		} else {
			if ( ! empty( $network ) ) {
				$this->find_on_network( $network, $args, $opts );
			} else {
				$this->find_on_site( $args, $opts );
			}
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

		if ( ! isset( $opts['network'] ) ) {
			return '';
		}

		if ( ! is_plugin_active_for_network( BLOCK_CATALOG_PLUGIN_FILE ) ) {
			\WP_CLI::error( __( 'The --network option can only be used when the Block Catalog plugin is network activated.', 'block-catalog' ) );
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
	 * @return bool
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
		} elseif ( is_array( $network ) && ! empty( $network ) && is_numeric( $network[0] ) ) {
			// list of site ids
			$query_params['site__in'] = $network;
		} else {
			$query_params['site__in'] = [];
		}

		$query = new \WP_Site_Query( $query_params );
		$sites = $query->get_sites();

		return $sites;
	}

	/**
	 * Indexes the block catalog for the current site.
	 *
	 * @param array $args Command args
	 * @param array $opts Command opts
	 */
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

	/**
	 * Returns a bool depending on if the --count option is set.
	 *
	 * @param array $opts Command opts
	 * @return bool
	 */
	private function get_count_option( $opts ) {
		return isset( $opts['count'] );
	}

	/**
	 * Counts the number of posts across the network that match the queried terms using the
	 * PostFinder object.
	 *
	 * @param array $sites Sites to query.
	 * @param array $args Blocks to query.
	 * @param array $opts Optional arguments.
	 */
	private function count_on_network( $sites = [], $args = [], $opts = [] ) {
		if ( ! empty( $opts['index'] ) ) {
			$this->index( $args, $opts );
		}

		$finder = new PostFinder();

		$result = $finder->count_on_network( $sites, $args, $opts );
		$fields = ! empty( $opts['fields'] ) ? explode( ',', $opts['fields'] ) : [ 'blog_id', 'blog_url', 'count' ];
		$format = ! empty( $opts['format'] ) ? $opts['format'] : 'table';

		\WP_CLI\Utils\format_items( $format, $result, $fields );
	}

	/**
	 * Counts the number of posts that match the queried terms using the
	 * PostFinder object.
	 *
	 * @param array $args Blocks to query.
	 * @param array $opts Optional arguments.
	 */
	private function count_on_site( $args = [], $opts = [] ) {
		if ( ! empty( $opts['index'] ) ) {
			$this->index( $args, $opts );
		}

		$finder = new PostFinder();
		$result = $finder->count( $args, $opts );

		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}

		if ( ! empty( $result ) ) {
			// translators: %d is the number of posts found
			\WP_CLI::success( sprintf( __( 'Found %d post(s)', 'block-catalog' ), $result ) );
		} else {
			\WP_CLI::warning( __( 'No posts found.', 'block-catalog' ) );
		}
	}

	/**
	 * Find posts across the network that match the queried terms using the PostFinder.
	 *
	 * @param array $sites Sites to query.
	 * @param array $args Blocks to query.
	 * @param array $opts Optional arguments.
	 */
	private function find_on_network( $sites = [], $args = [], $opts = [] ) {
		if ( ! empty( $opts['index'] ) ) {
			$this->index( $args, $opts );
		}

		$finder = new PostFinder();

		$result = $finder->find_on_network( $sites, $args, $opts );
		$fields = ! empty( $opts['fields'] ) ? explode( ',', $opts['fields'] ) : [ 'blog_id', 'blog_url', 'ID', 'post_type', 'post_title' ];
		$format = ! empty( $opts['format'] ) ? $opts['format'] : 'table';
		$output = [];

		foreach ( $result as $result_item ) {
			$error    = $result_item['error'] ?? false;
			$blog_id  = $result_item['blog_id'];
			$blog_url = $result_item['blog_url'];

			if ( is_wp_error( $error ) ) {
				$output[] = [
					'blog_id'    => $blog_id,
					'blog_url'   => $blog_url,
					'ID'         => 0,
					'post_type'  => '',
					'post_title' => $error->get_error_message(),
				];
			} else {
				$posts = $result_item['posts'];

				// don't output sites with no posts
				if ( empty( $posts ) ) {
					continue;
				}

				foreach ( $posts as $post ) {
					$row = [
						'blog_id'  => $blog_id,
						'blog_url' => $blog_url,
					];

					foreach ( $fields as $field ) {
						if ( empty( $row[ $field ] ) ) {
							$row[ $field ] = $post->$field;
						}
					}

					$output[] = $row;
				}
			}
		}

		if ( ! empty( $output ) ) {
			\WP_CLI\Utils\format_items( $format, $output, $fields );
		} else {
			\WP_CLI::warning( __( 'No posts found.', 'block-catalog' ) );
		}
	}

	/**
	 * Find posts that match the queried terms using the PostFinder on the current site.
	 *
	 * @param array $args Blocks to query.
	 * @param array $opts Optional arguments.
	 */
	private function find_on_site( $args = [], $opts = [] ) {
		if ( ! empty( $opts['index'] ) ) {
			$this->index( $args, $opts );
		}

		$finder = new PostFinder();
		$result = $finder->find( $args, $opts );
		$fields = ! empty( $opts['fields'] ) ? explode( ',', $opts['fields'] ) : [ 'ID', 'post_type', 'post_title' ];
		$format = ! empty( $opts['format'] ) ? $opts['format'] : 'table';

		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}

		if ( ! empty( $result ) ) {
			\WP_CLI\Utils\format_items( $format, $result, $fields );
		} else {
			\WP_CLI::warning( __( 'No posts found.', 'block-catalog' ) );
		}
	}

}
