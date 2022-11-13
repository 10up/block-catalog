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

		$result = wp_set_post_terms( $post_id, $terms, BLOCK_CATALOG_TAXONOMY, false );

		return $result;
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
				$terms = array_merge( $terms, $block_terms );
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
	 * @param array $output The list of blocks found.
	 * @param array $block The list of blocks
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

		/**
		 * Allows plugins/themes to change the term name corresponding to the the
		 * block in the catalog.
		 *
		 * @param array $terms The term names corresponding to the block in the catalog
		 */
		$terms = apply_filters( 'block_catalog_block_terms', [ $block['blockName'] ], $block );

		return $terms;
	}

}
