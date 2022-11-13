<?php
/**
 * BlockCatalogTaxonomy
 *
 * @package BlockCatalog
 */

namespace BlockCatalog;

/**
 * BlockCatalog registers the block-catalog taxonomy with WP.
 */
class BlockCatalogTaxonomy {

	/**
	 * Get the taxonomy name constant.
	 *
	 * @return string
	 */
	public function get_name() {
		return BLOCK_CATALOG_TAXONOMY;
	}

	/**
	 * Get the singular taxonomy label.
	 *
	 * @return string
	 */
	public function get_singular_label() {
		return __( 'Block Catalog', 'block-catalog' );
	}

	/**
	 * Get the plural taxonomy label.
	 *
	 * @return string
	 */
	public function get_plural_label() {
		return __( 'Block Catalog', 'block-catalog' );
	}

	/**
	 * Register hooks and actions.
	 *
	 * To add support for a taxonomy `gw_example` to a theme,
	 * `add_theme_supports( 'taxonomy_gw_example' );`
	 *
	 * @uses $this->get_name() to get the taxonomy's slug.
	 * @return bool
	 */
	public function register() {
		/**
		 * Allow plugins/themes to update options for the block catalog taxonomy.
		 *
		 * @param array  $options  Default taxonomy options.
		 * @param string $name Taxonomy name.
		 */
		$options = apply_filters(
			'block_catalog_taxonomy_options',
			$this->get_options(),
		);

		\register_taxonomy(
			$this->get_name(),
			$this->get_post_types(),
			$options
		);

		return true;
	}

	/**
	 * Get the options for the taxonomy.
	 *
	 * @return array
	 */
	public function get_options() {
		return array(
			'labels'            => $this->get_labels(),
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'      => false,
			'public'            => true,
		);
	}

	/**
	 * Get the labels for the taxonomy.
	 *
	 * @return array
	 */
	public function get_labels() {
		$plural_label   = $this->get_plural_label();
		$singular_label = $this->get_singular_label();

		// phpcs:disable
		$labels = array(
			'name'                       => $plural_label, // Already translated via get_plural_label().
			'singular_name'              => $singular_label, // Already translated via get_singular_label().
			'search_items'               => sprintf( __( 'Search %s', 'block-catalog' ), $plural_label ),
			'popular_items'              => sprintf( __( 'Popular %s', 'block-catalog' ), $plural_label ),
			'all_items'                  => sprintf( __( 'All %s', 'block-catalog' ), $plural_label ),
			'edit_item'                  => sprintf( __( 'Edit %s', 'block-catalog' ), $singular_label ),
			'update_item'                => sprintf( __( 'Update %s', 'block-catalog' ), $singular_label ),
			'add_new_item'               => sprintf( __( 'Add New %s', 'block-catalog' ), $singular_label ),
			'new_item_name'              => sprintf( __( 'New %s Name', 'block-catalog' ), $singular_label ),
			'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'block-catalog' ), strtolower( $plural_label ) ),
			'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'block-catalog' ), strtolower( $plural_label ) ),
			'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'block-catalog' ), strtolower( $plural_label ) ),
			'not_found'                  => sprintf( __( 'No %s found.', 'block-catalog' ), strtolower( $plural_label ) ),
			'not_found_in_trash'         => sprintf( __( 'No %s found in Trash.', 'block-catalog' ), strtolower( $plural_label ) ),
			'view_item'                  => sprintf( __( 'View %s', 'block-catalog' ), $singular_label ),
		);
		// phpcs:enable

		return $labels;
	}

	/**
	 * Setting the post types to null to ensure no post type is registered with
	 * this taxonomy. Post Type classes declare their supported taxonomies.
	 */
	public function get_post_types() {
		$post_types = get_post_types( [ 'show_in_rest' => true, '_builtin' => false ] );
		$post_types = array_merge( $post_types, [ 'post', 'page' ] );

		/**
		 * Allow plugins/themes to update post types for the block catalog taxonomy.
		 *
		 * @param array  $options  Default post types.
		 */
		$options = apply_filters(
			'block_catalog_taxonomy_post_types',
			$post_types,
		);

		return $post_types;
	}
}
