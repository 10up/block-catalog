<?php
/**
 * RESTSupport
 *
 * @package BlockCatalog
 */

namespace BlockCatalog;

/**
 * RESTSupport adds custom endpoints for managing the block-catalog.
 */
class RESTSupport {

	/**
	 * Registers with WordPress REST api.
	 */
	public function register() {
		add_action( 'rest_api_init', [ $this, 'register_endpoint' ] );
	}

	/**
	 * Always register this endpoint since we are behind REST api init.
	 *
	 * @return bool
	 */
	public function can_register() {
		return true;
	}

	/**
	 * Registes the REST endpoint with WP.
	 */
	public function register_endpoint() {
		register_rest_route(
			'block-catalog/v1',
			'/posts/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_posts' ],
				'permission_callback' => function() {
					return current_user_can( \BlockCatalog\Utility\get_required_capability() );
				},
				'args'                => [
					'post_types' => [
						'required'          => false,
						'type'              => 'array',
						'validate_callback' => [ $this, 'validate_post_types' ],
					],
				],
			]
		);

		register_rest_route(
			'block-catalog/v1',
			'/index/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'index' ],
				'permission_callback' => function() {
					return current_user_can( \BlockCatalog\Utility\get_required_capability() );
				},
				'args'                => [
					'post_ids' => [
						'required'          => true,
						'type'              => 'array',
						'validate_callback' => [ $this, 'validate_post_ids' ],
					],
				],
			]
		);

		register_rest_route(
			'block-catalog/v1',
			'/delete-index/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'delete_index' ],
				'permission_callback' => function() {
					return current_user_can( \BlockCatalog\Utility\get_required_capability() );
				},
			]
		);
	}

	/**
	 * Deletes the Block catalog index.
	 *
	 * @return array
	 */
	public function delete_index() {
		$builder = new CatalogBuilder();
		return $builder->delete_index();
	}

	/**
	 * Returns the list of post ids to be indexed.
	 *
	 * @param \WP_REST_Request $request The request object
	 * @return array
	 */
	public function get_posts( $request ) {
		\BlockCatalog\Utility\start_bulk_operation();

		$query_params                   = $this->get_posts_to_index_query( $request );
		$query_params['posts_per_page'] = 1;

		$count_query = new \WP_Query( $query_params );
		$total       = $count_query->found_posts;

		$page_size   = apply_filters( 'block_catalog_posts_to_index_page_size', 500 );
		$total_pages = ceil( $total / $page_size );

		$query_params['posts_per_page'] = $page_size;

		if ( empty( $total ) ) {
			return [ 'posts' => [] ];
		}

		$results = [];

		for ( $i = 0; $i < $total_pages; $i++ ) {
			$query_params['paged'] = $i + 1;

			$query = new \WP_Query( $query_params );
			$posts = $query->posts;

			if ( ! empty( $posts ) ) {
				$results = array_merge( $results, $posts );
			}

			\BlockCatalog\Utility\clear_caches();
		}

		\BlockCatalog\Utility\stop_bulk_operation();

		return [ 'posts' => $results ];
	}

	/**
	 * Returns the WP Query params used to fetch the posts to index.
	 *
	 * @param \WP_REST_Request $request The request object
	 * @return array
	 */
	public function get_posts_to_index_query( $request ) {
		$post_types = $request->get_param( 'post_types' );

		if ( empty( $post_types ) ) {
			$post_types = \BlockCatalog\Utility\get_supported_post_types();
		}

		$query_params = [
			'post_type'   => $post_types,
			'post_status' => 'any',
			'fields'      => 'ids',
		];

		return apply_filters( 'block_catalog_posts_to_index_query_params', $query_params, $request );
	}

	/**
	 * Indexes the blocks in the specified posts.
	 *
	 * @param \WP_REST_Request $request The request object
	 * @return bool
	 */
	public function index( $request ) {
		\BlockCatalog\Utility\start_bulk_operation();

		$post_ids = $request->get_param( 'post_ids' );
		$builder  = new CatalogBuilder();

		$updated = 0;
		$errors  = 0;
		$builder = new CatalogBuilder();

		foreach ( $post_ids as $post_id ) {
			$result = $builder->catalog( $post_id );

			\BlockCatalog\Utility\clear_caches();

			if ( is_wp_error( $result ) ) {
				$errors++;
			} else {
				$updated += count( $result );
			}
		}

		\BlockCatalog\Utility\stop_bulk_operation();

		return [
			'updated' => $updated,
			'errors'  => $errors,
		];
	}

	/**
	 * Verifies the post type argument matches the supported list.
	 *
	 * @param array $post_types The post types list
	 * @return bool
	 */
	public function validate_post_types( $post_types ) {
		$supported = \BlockCatalog\Utility\get_supported_post_types();

		if ( empty( $post_types ) ) {
			return true;
		}

		foreach ( $post_types as $post_type ) {
			if ( ! in_array( $post_type, $supported, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validates the specified post ids.
	 *
	 * @param array $post_ids The post ids to validate
	 * @return bool
	 */
	public function validate_post_ids( $post_ids ) {
		if ( empty( $post_ids ) ) {
			return true;
		}

		$post_ids = array_map( 'intval', $post_ids );
		$post_ids = array_filter( $post_ids );

		return ! empty( $post_ids );
	}

}
