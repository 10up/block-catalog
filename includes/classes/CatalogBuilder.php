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
		try {
			update_option( 'block_catalog_indexed', 1 );

			if ( empty( $post_id ) ) {
				return [];
			}

			$output = $this->get_post_block_terms( $post_id, $opts );
			$result = $this->set_post_block_terms( $post_id, $output );

			return $output;
		} catch ( Exception $e ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				// translators: %1$d is post_id, %2$s is error message
				\WP_CLI::warning( sprintf( __( 'Failed to catalog %1$d - %2$s', 'block-catalog' ), $post_id, $e->getMessage() ) );
			}

			return new \WP_Error( 'catalog_failed', $e->getMessage() );
		}
	}

	/**
	 * Resets the Block Catalog by removing all catalog terms.
	 *
	 * @param array $opts Optional opts
	 */
	public function delete_index( $opts = [] ) {
		\BlockCatalog\Utility\start_bulk_operation();

		$term_opts = [
			'taxonomy'   => BLOCK_CATALOG_TAXONOMY,
			'fields'     => 'ids',
			'hide_empty' => false,
		];

		$terms   = get_terms( $term_opts );
		$total   = count( $terms );
		$removed = 0;
		$errors  = 0;
		$is_cli  = defined( 'WP_CLI' ) && WP_CLI;

		// translators: %d is number of block catalog terms
		$message = sprintf( __( 'Removing %d block catalog terms ...', 'block-catalog' ), $total );

		if ( $is_cli ) {
			$progress_bar = \WP_CLI\Utils\make_progress_bar( $message, $total );
		}

		foreach ( $terms as $term_id ) {
			if ( $is_cli ) {
				$progress_bar->tick();
			}

			$result = $this->delete_term_index( $term_id );

			\BlockCatalog\Utility\clear_caches();

			if ( ! is_wp_error( $result ) ) {
				$removed++;
			} else {
				$errors++;
			}
		}

		if ( $is_cli ) {
			$progress_bar->finish();
		}

		if ( $is_cli ) {
			if ( ! empty( $removed ) ) {
				/* translators: %d is number of catalog terms removed */
				\WP_CLI::success( sprintf( __( 'Removed %d block catalog term(s).', 'block-catalog' ), $removed ) );
			} else {
				\WP_CLI::warning( __( 'No block catalog terms to remove.', 'block-catalog' ) );
			}

			if ( ! empty( $errors ) ) {
				\WP_CLI::warning( sprintf( 'Failed to remove %d block catalog terms(s).', 'block-catalog' ), $errors );
			}
		}

		\BlockCatalog\Utility\stop_bulk_operation();

		return [
			'removed' => $removed,
			'errors'  => $errors,
		];
	}

	/**
	 * Deletes the specified term id and its associations.
	 *
	 * @param int   $term_id The term id to delete.
	 * @param array $opts Optional opts
	 */
	public function delete_term_index( $term_id, $opts = [] ) {
		return wp_delete_term( $term_id, BLOCK_CATALOG_TAXONOMY );
	}

	/**
	 * Sets the blocks terms of the post. Creates the terms if absent.
	 *
	 * @param int   $post_id The post id
	 * @param array $output The block terms & variations
	 * @return array|WP_Error
	 */
	public function set_post_block_terms( $post_id, $output ) {
		if ( empty( $output['terms'] ) ) {
			return wp_set_object_terms( $post_id, [], BLOCK_CATALOG_TAXONOMY );
		}

		$term_ids = [];

		foreach ( $output['terms'] ?? [] as $slug => $label ) {
			if ( ! term_exists( $slug, BLOCK_CATALOG_TAXONOMY ) ) {
				$term_args = [
					'slug' => $slug,
				];

				$parent_id = $this->get_block_parent_term( $slug );

				if ( ! empty( $parent_id ) ) {
					$term_args['parent'] = $parent_id;
					$term_ids[]          = $parent_id;
				}

				$result = wp_insert_term( $label, BLOCK_CATALOG_TAXONOMY, $term_args );

				if ( ! is_wp_error( $result ) ) {
					$term_ids[] = intval( $result['term_id'] );
				}
			} else {
				$result = get_term_by( 'slug', $slug, BLOCK_CATALOG_TAXONOMY );

				if ( ! empty( $result ) ) {
					$term_ids[] = intval( $result->term_id );
				}

				if ( ! empty( $result->parent ) ) {
					$term_ids[] = $result->parent;
				}
			}
		}

		foreach ( $output['variations'] ?? [] as $variation ) {
			$blockName = $variation['blockName'];
			$terms     = $variation['terms'] ?? [];

			if ( empty( $blockName ) || empty( $terms ) ) {
				continue;
			}

			foreach ( $terms as $label ) {
				$slug = $blockName . '-' . $label;

				if ( ! term_exists( $slug, BLOCK_CATALOG_TAXONOMY ) ) {
					$term_args = [
						'slug' => $slug,
					];

					$parent_id = $this->get_variation_parent_term( $blockName );

					if ( ! empty( $parent_id ) ) {
						$term_args['parent'] = $parent_id;
						$term_ids[]          = $parent_id;
					}

					$result = wp_insert_term( $label, BLOCK_CATALOG_TAXONOMY, $term_args );

					if ( ! is_wp_error( $result ) ) {
						$term_ids[] = intval( $result['term_id'] );
					}
				} else {
					$result = get_term_by( 'slug', $slug, BLOCK_CATALOG_TAXONOMY );

					if ( ! empty( $result ) ) {
						$term_ids[] = intval( $result->term_id );
					}

					if ( ! empty( $result->parent ) ) {
						$term_ids[] = $result->parent;
					}
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

		$post_terms = [];
		$variations = [];

		foreach ( $blocks as $block ) {
			$block_terms = $this->block_to_terms( $block );

			if ( ! empty( $block_terms['terms'] ) ) {
				$post_terms = array_replace( $post_terms, $block_terms['terms'] );
			}

			if ( ! empty( $block_terms['variations'] ) ) {
				$variations[] = array_merge( $variations, [
					'blockName' => $block['blockName'],
					'block'     => $block,
					'terms'     => $block_terms['variations'],
				] );
			}
		}

		/**
		 * Filters the computed list of block terms for a post.
		 *
		 * @param array $terms The computed block terms list
		 * @param int   $post_id The post id
		 * @return int The new list of block terms
		 */
		$post_terms = apply_filters( 'block_catalog_post_block_terms', $post_terms, $post_id );

		return [
			'terms'      => $post_terms,
			'variations' => $variations,
		];
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
			// ignore empty blocks
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

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
			return [ 'terms' => [], 'variations' => [] ];
		}

		$terms = [];
		$label = $this->get_block_label( $block );

		/**
		 * Filters the term label corresponding to the block in the catalog.
		 *
		 * @param array $terms The term names corresponding to the block in the catalog
		 * @param array $block The block data
		 * @return string
		 */
		$label = apply_filters( 'block_catalog_block_term_label', $label, $block );

		if ( 'core/block' === $block['blockName'] && ! empty( $block['attrs']['ref'] ) ) {
			$reusable_slug           = 're-' . intval( $block['attrs']['ref'] );
			$terms[ $reusable_slug ] = get_the_title( $block['attrs']['ref'] );
		} else {
			$terms[ $block['blockName'] ] = $label;
		}

		/**
		 * Filters the term labels corresponding to the block in the catalog. This
		 * is useful to build multiple terms from a single block.
		 *
		 * eg:- embed & special-type-of-embed
		 *
		 * @param array $terms The term names corresponding to the block in the catalog
		 * @param array $block The block data
		 * @return array The new list of terms
		 */
		$terms = apply_filters( 'block_catalog_block_terms', $terms, $block );

		/**
		 * Filters the term variations for a given block.
		 *
		 * @param array $block The block data
		 */
		$variations = apply_filters( 'block_catalog_block_variations', [], $block );

		return [
			'terms'      => $terms,
			'variations' => $variations,
		];
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

		$parts       = explode( '/', $name );
		$namespace   = $parts[0] ?? '';
		$short_title = $parts[1] ?? ( $namespace ?? __( 'Untitled', 'block-catalog' ) );

		// if we got here, the block is incorrectly registered, try to guess at the name
		if ( $title === $name ) {
			$title = $this->get_display_title( $short_title );
		}

		/**
		 * Filters the block title for the specified block.
		 *
		 * @param string $title The block title
		 * @param string $name The block name
		 * @param array  $block The block data
		 * @return string The new block title
		 */
		$title = apply_filters( 'block_catalog_block_title', $title, $name, $block );

		return $title;
	}

	/**
	 * Converts phrase to display label.
	 *
	 * @param string $title The title string
	 * @return string
	 */
	public function get_display_title( $title ) {
		$title = str_replace( '-', ' ', $title );
		$title = str_replace( '_', ' ', $title );
		$title = ucwords( $title );

		return $title;
	}

	/**
	 * Returns the name of the parent term from the full block name.
	 *
	 * @param string $name The full block name
	 * @return string
	 */
	public function get_block_parent_name( $name ) {
		if ( 0 === stripos( $name, 're-' ) ) {
			return __( 'Reusable block', 'block-catalog' );
		}

		$parts     = explode( '/', $name );
		$namespace = count( $parts ) > 1 ? $parts[0] : '';

		if ( empty( $namespace ) ) {
			return '';
		}

		/**
		 * Filters the namespace label shown on the parent block term.
		 *
		 * eg:- core/embed => Core
		 *
		 * @param string $label The block namespace label
		 * @param string $name The full block name
		 * @return string
		 */
		return apply_filters( 'block_catalog_namespace_label', $namespace, $name );
	}

	/**
	 * Returns the parent term id of the specified term.
	 *
	 * @param string $name The full block name
	 * @return int|false
	 */
	public function get_block_parent_term( $name ) {
		$name = $this->get_block_parent_name( $name );

		if ( empty( $name ) ) {
			return false;
		}

		$name   = $this->get_display_title( $name );
		$result = get_term_by( 'name', $name, BLOCK_CATALOG_TAXONOMY );

		if ( ! empty( $result ) ) {
			return intval( $result->term_id );
		}

		$result = wp_insert_term( $name, BLOCK_CATALOG_TAXONOMY, [] );

		if ( ! is_wp_error( $result ) ) {
			return intval( $result['term_id'] );
		}

		return false;
	}

	/**
	 * Returns the parent variation term or false if absent.
	 *
	 * @param string $name The parent block name
	 * @return int|false
	 */
	public function get_variation_parent_term( $name ) {
		if ( empty( $name ) ) {
			return false;
		}

		$result = get_term_by( 'slug', sanitize_title( $name ), BLOCK_CATALOG_TAXONOMY );

		if ( empty( $result ) || empty( $result->term_id ) ) {
			return false;
		}

		return intval( $result->term_id );
	}
}
