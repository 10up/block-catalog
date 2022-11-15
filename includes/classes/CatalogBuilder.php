<?php
/**
 * CatalogBuilder
 *
 * @package BlockCatalog
 */

namespace BlockCatalog;

/**
 * CatalogBuilder generates a list of Block terms for a post.
 */
class CatalogBuilder {

	/**
	 * Catalog's a post based on it's current content.
	 *
	 * @param int   $post_id The post id.
	 * @param array $opts Optional args
	 * @return array|WP_Error
	 */
	public function catalog( $post_id, $opts = [] ) {
		if ( empty( $post_id ) ) {
			return [];
		}

		$terms = $this->get_post_block_terms( $post_id, $opts );

		if ( empty( $terms ) ) {
			return wp_set_object_terms( $post_id, [], BLOCK_CATALOG_TAXONOMY );
		}

		$result = $this->set_post_block_terms( $post_id, $terms );

		return $result;
	}

	/**
	 * Sets the blocks terms of the post. Creates the terms if absent.
	 *
	 * @param int   $post_id The post id
	 * @param array $terms The block terms
	 * @return array|WP_Error
	 */
	public function set_post_block_terms( $post_id, $terms ) {
		$term_ids = [];

		foreach ( $terms as $slug => $label ) {
			if ( ! term_exists( $slug, BLOCK_CATALOG_TAXONOMY ) ) {
				$term_args = [
					'slug' => $slug,
				];

				$result = wp_insert_term( $label, BLOCK_CATALOG_TAXONOMY, $term_args );

				if ( ! is_wp_error( $result ) ) {
					$term_ids[] = intval( $result['term_id'] );
				}
			} else {
				$result = get_term_by( 'slug', $slug, BLOCK_CATALOG_TAXONOMY );

				if ( ! empty( $result ) ) {
					$term_ids[] = intval( $result->term_id );
				}
			}
		}

		$term_ids = array_filter( $term_ids );
		$term_ids = array_unique( $term_ids );

		return wp_set_object_terms( $post_id, $term_ids, BLOCK_CATALOG_TAXONOMY, false );
	}

	/**
	 * Builds a list of Block Term names for a given post.
	 *
	 * @param int   $post_id The post id.
	 * @param array $opts Optional args
	 * @return array
	 */
	public function get_post_block_terms( $post_id, $opts = [] ) {
		if ( empty( $post_id ) ) {
			return [];
		}

		$content = get_post_field( 'post_content', $post_id );

		if ( empty( $content ) ) {
			return [];
		}

		$blocks = parse_blocks( $content );

		if ( empty( $blocks ) ) {
			return [];
		}

		$blocks = $this->to_block_list( $blocks );

		if ( empty( $blocks ) ) {
			return [];
		}

		$terms = [];

		foreach ( $blocks as $block ) {
			$block_terms = $this->block_to_terms( $block );

			if ( ! empty( $block_terms ) ) {
				$terms = array_replace( $terms, $block_terms );
			}
		}

		$terms = array_filter( $terms );
		$terms = array_unique( $terms );

		/**
		 * Allows plugins/themes to filter the computed list of block terms for a post
		 *
		 * @param array $terms The computed block terms list
		 * @param int   $post_id The post id
		 */
		$terms = apply_filters( 'block_catalog_post_block_terms', $terms, $post_id );

		return $terms;
	}

	/**
	 * Flattens the list of blocks into a single array.
	 *
	 * @param array $blocks The list of blocks
	 * @return array
	 */
	public function to_block_list( $blocks ) {
		if ( empty( $blocks ) ) {
			return [];
		}

		$output = [];

		foreach ( $blocks as $block ) {
			// add current block to output
			$output[] = $block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				// recursively add all inner blocks to output
				$output = array_merge( $output, $this->to_block_list( $block['innerBlocks'] ) );
			}
		}

		return $output;
	}

	/**
	 * Converts a block to a list of term names.
	 *
	 * @param array $block The block.
	 * @param array $opts Optional opts
	 * @return array
	 */
	public function block_to_terms( $block, $opts = [] ) {
		if ( empty( $block ) || empty( $block['blockName'] ) ) {
			return [];
		}

		$terms = [];
		$label = $this->get_block_label( $block );

		/**
		 * Allows plugins/themes to change the term label corresponding to the
		 * block in the catalog.
		 *
		 * @param array $terms The term names corresponding to the block in the catalog
		 * @param array $block The block data
		 */
		$label = apply_filters( 'block_catalog_block_term_label', $label, $block );

		$terms[ $block['blockName'] ] = $label;

		if ( ! empty( $block['attrs']['ref'] ) ) {
			$reusable_slug           = 're-' . intval( $block['attrs']['ref'] );
			$terms[ $reusable_slug ] = get_the_title( $block['attrs']['ref'] ) . ' - Reusable';
		}

		/**
		 * Allows plugins/themes to change the term labels corresponding to the
		 * block in the catalog. This is useful to build multiple terms from a
		 * single block. eg:- embed & special-type-of-embed
		 *
		 * @param array $terms The term names corresponding to the block in the catalog
		 * @param array $block The block data
		 */
		$terms = apply_filters( 'block_catalog_block_terms', $terms, $block );

		return $terms;
	}

	/**
	 * Finds the label of the block term from its blockName.
	 *
	 * @param string $block The block data
	 * @return string
	 */
	public function get_block_label( $block ) {
		$name       = $block['blockName'] ?? '';
		$registered = \WP_Block_Type_Registry::get_instance()->get_registered( $name );
		$title      = ! empty( $registered->title ) ? $registered->title : $block['blockName'];

		/**
		 * Allows plugins/themes to change the block title for the specified block
		 *
		 * @param string $title The block title
		 * @param string $name The block name
		 * @param array  $block The block data
		 */
		return apply_filters( 'block_catalog_block_title', $title, $name, $block );
	}

}
