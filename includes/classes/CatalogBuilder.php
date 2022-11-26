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
	 * The registered class variations
	 *
	 * @var array
	 */
	static public $class_variations = [];

	/**
	 * The registered attr variations
	 *
	 * @var array
	 */
	static public $attr_variations = [];

	/**
	 * Saves the class variations for later use.
	 *
	 * @param array $variations The class variations array
	 */
	static public function register_class_variations( $variations = [] ) {
		self::$class_variations = array_merge( self::$class_variations, $variations );
	}

	/**
	 * Saves the attr variations for later use.
	 *
	 * @param array $variations The attr variations array
	 */
	static public function register_attr_variations( $variations = [] ) {
		self::$attr_variations = array_merge( self::$attr_variations, $variations );
	}

	/**
	 * Stores the parent_ids of the block variations by slug.
	 *
	 * @var array
	 */
	static public $variation_parent_ids = [];

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

			$terms = $this->get_post_block_terms( $post_id, $opts );

			if ( empty( $terms ) ) {
				return wp_set_object_terms( $post_id, [], BLOCK_CATALOG_TAXONOMY );
			}

			$result = $this->set_post_block_terms( $post_id, $terms );

			return $terms;
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

				if ( ! empty( self::$variation_parent_ids[ $slug ] ) ) {
					$parent_id = self::$variation_parent_ids[ $slug ];
				} else {
					$parent_id = $this->get_block_parent_term( $slug );
				}

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
		 * Filters the computed list of block terms for a post.
		 *
		 * @param array $terms The computed block terms list
		 * @param int   $post_id The post id
		 * @return int The new list of block terms
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
		 * Filters the term label corresponding to the block in the catalog.
		 *
		 * @param array $terms The term names corresponding to the block in the catalog
		 * @param array $block The block data
		 * @return string
		 */
		$label = apply_filters( 'block_catalog_block_term_label', $label, $block );

		if ( ! empty( $block['attrs']['ref'] ) ) {
			$reusable_slug           = 're-' . intval( $block['attrs']['ref'] );
			$terms[ $reusable_slug ] = get_the_title( $block['attrs']['ref'] );
		} else {
			$terms[ $block['blockName'] ] = $label;
		}

		$variations = $this->get_block_variations( $block, $label );

		if ( ! empty( $variations ) ) {
			$terms = array_merge( $terms, $variations );
		}

		$class_variations = $this->get_block_variations_by_class( $block, $label );

		if ( ! empty( $class_variations ) ) {
			$terms = array_merge( $terms, $class_variations );
		}

		$attr_variations = $this->get_block_variations_by_attr( $block, $label );

		if ( ! empty( $attr_variations ) ) {
			$terms = array_merge( $terms, $attr_variations );
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

		$parts       = explode( '/', $name );
		$namespace   = $parts[0] ?? '';
		$short_title = $parts[1] ?? __( 'Untitled', 'block-catalog' );

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
		$namespace = $parts[0] ?? '';

		if ( empty( $namespace ) ) {
			return '';
		}

		/**
		 * Filters the namespace label shown on the parent block term.
		 *
		 * eg:- core/embed => Core
		 *
		 * @param string $namespace The block namespace
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
	 * Returns the variations of a block if present.
	 *
	 * @param array $block The block data
	 * @param string $label The block term label
	 * @return array
	 */
	public function get_block_variations( $block, $label ) {
		/**
		 * Filters the list of block variations derived from the block data.
		 *
		 * @param array $variations The names of the variations
		 * @param array $block The block data
		 * @return array The new block variation names
		 */
		return apply_filters( 'block_catalog_block_variations', [], $block, $label );
	}

	/**
	 * Returns the variations of a block based on className.
	 *
	 * @param array  $block The block data
	 * @param string $label The block term label
	 * @return array
	 */
	public function get_block_variations_by_class( $block, $label ) {
		$variations = [];
		$class_list = ! empty( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';

		if ( ! empty( self::$class_variations ) ) {
			foreach ( self::$class_variations as $class_name => $label ) {
				if ( false !== stripos( $class_list, $class_name ) ) {
					$slug                = $block['blockName'] . '-' . $class_name;
					$variations[ $slug ] = $label;
				}
			}
		}

		/**
		 * Filters the list of block variations derived from the block data.
		 *
		 * @param array $variations The names of the variations
		 * @param array $block The block data
		 * @return array The new block variation names
		 */
		return apply_filters( 'block_catalog_block_variations_by_class', $variations, $class_list, $block );
	}

	/**
	 * Returns the variations of a block based on attribute values.
	 *
	 * @param array $block The block data
	 * @param string $label The block term label
	 * @return array
	 */
	public function get_block_variations_by_attr( $block, $label ) {
		$variations = [];
		$attrs      = ! empty( $block['attrs'] ) ? $block['attrs'] : [];

		if ( ! empty( self::$attr_variations ) ) {
			foreach ( self::$attr_variations as $block_name => $attr_keys ) {
				if ( $block_name !== $block['blockName'] ) {
					continue;
				}

				$slug = $block['blockName'];

				if ( is_string( $attr_keys ) ) {
					$attr_keys = [ $attr_keys ];
				}

				if ( empty( $attr_keys ) ) {
					continue;
				}

				foreach ( $attr_keys as $attr_key ) {
					if ( ! empty( $attrs[ $attr_key ] ) ) {
						$attr_value = $attrs[ $attr_key ];

						if ( is_bool( $attr_value ) ) {
							$attr_value = $attr_value ? 'true' : 'false';
						}

						if ( is_scalar( $attr_value ) ) {
							$slug .= '-' . $attr_key . '-' . $attr_value;
							$variations[ $slug ] = "${label} - ${attr_key}: $attr_value";

							if ( empty( self::$variation_parent_ids[ $block['blockName'] ] ) ) {
								self::$variation_parent_ids[ $slug ] = $this->get_block_parent_term( $block['blockName'] );
							}
						}
					}
				}
			}
		}

		/**
		 * Filters the list of block variations derived from the block data.
		 *
		 * @param array $variations The names of the variations
		 * @param array $block The block data
		 * @return array The new block variation names
		 */
		return apply_filters( 'block_catalog_block_variations_by_class', $variations, $class_list, $block );
	}

}
