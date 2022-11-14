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
	 * Registes the /publix/v1/site-settings/ REST endpoint.
	 */
	public function register_endpoint() {
		register_rest_route(
			'block-catalog/v1',
			'/posts/',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_posts' ],
				'permission_callback' => function() {
					return true;//current_user_can( 'edit_posts' );
				},
				'args' => [
					'post_types' => [
						'required'          => false,
						'type'              => 'array',
						'validate_callback' => [ $this, 'validate_post_types' ],
					]
				]
			]
		);

		register_rest_route(
			'block-catalog/v1',
			'/index/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'index' ],
				'permission_callback' => function() {
					return true;//current_user_can( 'edit_posts' );
				},
				'args' => [
					'post_ids' => [
						'required'          => true,
						'type'              => 'array',
						'validate_callback' => [ $this, 'validate_post_ids' ],
					]
				]
			]
		);
	}

	/**
	 * Returns the list of post ids to be indexed.
	 *
	 * @param \WP_REST_Request $request The request object
	 * @return array
	 */
	public function get_posts( $request ) {
		$taxonomy   = new BlockCatalogTaxonomy();
		$post_types = $request->get_param( 'post_types' );

		if ( empty( $post_types ) ) {
			$post_types = $taxonomy->get_post_types();
		}

		$query_params = [
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => -1, // required to build the subsequent pagination
		];

		$query = new \WP_Query( $query_params );
		$posts = $query->posts;

		return [ 'posts' => $posts ];
	}

	/**
	 * Indexes the blocks in the specified posts.
	 *
	 * @param \WP_REST_Request $request The request object
	 * @return bool
	 */
	public function index( $request ) {
		$post_ids = $request->get_param( 'post_ids' );
		$builder  = new CatalogBuilder();

		$updated = 0;
		$errors  = 0;
		$builder = new CatalogBuilder();

		foreach ( $post_ids as $post_id ) {
			$result = $builder->catalog( $post_id, $opts );

			if ( is_wp_error( $result ) ) {
				$errors++;
			} else {
				$updated += count( $result );
			}
		}

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
		$taxonomy  = new BlockCatalogTaxonomy();
		$supported = $taxonomy->get_post_types();

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
