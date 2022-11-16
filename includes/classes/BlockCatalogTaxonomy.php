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
			\BlockCatalog\Utility\get_supported_post_types(),
			$options
		);

		if ( apply_filters( 'block_catalog_filter_enabled', true ) ) {
			add_action( 'restrict_manage_posts', [ $this, 'render_block_catalog_filter' ], 10000 );
		}

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
			'public'            => false,
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
	 * Outputs the Block catalog post listing dropdown
	 */
	public function render_block_catalog_filter() {
		global $typenow;

		if ( is_object_in_taxonomy( $typenow, BLOCK_CATALOG_TAXONOMY ) ) {
			$term_opts = [
				'taxonomy'   => BLOCK_CATALOG_TAXONOMY,
				'hide_empty' => true,
			];

			$terms     = get_terms( $term_opts );
			$selection = isset( $_GET['block-catalog'] ) ? sanitize_text_field( $_GET['block-catalog'] ) : ''; // phpcs:ignore
			$html      = '<select name="block-catalog" id="block-catalog" class="postform">';
			$all_label = __( 'All Blocks', 'block-catalog' );
			$html     .= sprintf( '<option %s value="">%s</option>', selected( $selection, '', false ), esc_attr( $all_label ) );

			foreach ( $terms as $term ) {
				$html .= sprintf(
					'<option %s value="%s">%s</option>',
					selected( $selection, $term->slug, false ),
					esc_attr( $term->slug ),
					esc_attr( $term->name )
				);
			}

			$html .= '</select>';

			echo $html; // phpcs:ignore
		}
	}

}
