<?php
/**
 * CatalogExporter
 *
 * @package BlockCatalog
 */

namespace BlockCatalog;

/**
 * Catalog Exporter exports the catalog usage data for later analysis & QA purposes.
 */
class CatalogExporter {

	/**
	 * Buffer for CSV rows.
	 *
	 * @var array
	 */
	private $csv_buffer = [];

	/**
	 * Exports the posts associated with the 'block_catalog' taxonomy to a CSV file.
	 *
	 * @param string $output The path to the output CSV file.
	 * @param array  $opts Options for the export, including 'post_type' and 'posts_per_block'.
	 * @return array|WP_Error Summary of the operation, or WP_Error on failure.
	 */
	public function export( $output, $opts ) {
		if ( ! $this->is_output_writable( dirname( $output ) ) ) {
			return new \WP_Error( 'output_not_writable', __( 'The output path is not writable', 'block-catalog' ) );
		}

		$terms = $this->get_block_catalog_terms( $opts );

		// check for WP_Error
		if ( is_wp_error( $terms ) ) {
			return [
				'success' => false,
				'message' => $terms->get_error_message(),
			];
		}

		if ( empty( $terms ) ) {
			return array(
				'success' => false,
				'message' => __( 'No terms found', 'block-catalog' ),
			);
		}

		$total_posts = $this->get_total_posts( $terms, $opts );

		$this->put_csv( array( 'block_name', 'block_slug', 'post_id', 'post_type', 'post_title', 'permalink', 'post_status', 'edit_link', 'post_author', 'post_date', 'post_modified', 'notes' ) );

		// when running in WP CLI mode, there is a progress bar
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$progress         = \WP_CLI\Utils\make_progress_bar( "Exporting catalog usage for $total_posts posts ...", $total_posts );
			$opts['progress'] = $progress;
		}

		foreach ( $terms as $term ) {
			if ( $this->can_export_term( $term, $opts ) ) {
				$this->export_term( $term, $opts );
			}
		}

		$result = $this->flush_csv( $output );

		if ( ! $result ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to write to output file', 'block-catalog' ),
			);
		}

		return array(
			'success'     => true,
			'message'     => __( 'Exported successfully', 'block-catalog' ),
			'total_posts' => $total_posts,
		);
	}

	/**
	 * Converts a comma-delimited list of pattern names into the equivalent --blocks value.
	 *
	 * Each pattern name (eg:- foo/something) maps to its catalog term slug
	 * (eg:- pattern-foo-something), matching CatalogBuilder::get_pattern_slug().
	 *
	 * @param string $patterns Comma-delimited list of pattern names.
	 * @return string Comma-delimited list of pattern term slugs.
	 */
	public function patterns_to_block_slugs( $patterns ) {
		$names = array_filter( array_map( 'trim', explode( ',', (string) $patterns ) ) );

		$slugs = array_map(
			function ( $name ) {
				return 'pattern-' . sanitize_title( $name );
			},
			$names
		);

		return implode( ',', $slugs );
	}

	/**
	 * Retrieves the terms associated with the 'block_catalog' taxonomy.
	 *
	 * When the 'blocks' option holds a list of slugs, only those terms are returned;
	 * otherwise all catalog terms are returned.
	 *
	 * @param array $opts Options for the export. Supports 'blocks' => array of slugs.
	 * @return array List of WP_Term objects.
	 */
	public function get_block_catalog_terms( $opts = [] ) {
		$args = array(
			'taxonomy'   => BLOCK_CATALOG_TAXONOMY,
			'hide_empty' => false,
		);

		// Restrict to the requested blocks when the --blocks filter is used.
		if ( ! empty( $opts['blocks'] ) ) {
			$args['slug'] = $opts['blocks'];
		}

		return get_terms( $args );
	}

	/**
	 * Gets the total number of posts associated with the given terms.
	 *
	 * @param array $terms List of WP_Term objects.
	 * @param array $opts Options for the query.
	 * @return int Total post count.
	 */
	private function get_total_posts( $terms, $opts ) {
		$total = 0;

		foreach ( $terms as $term ) {
			$query_args           = $this->get_query_args( $term->slug, $opts );
			$query_args['fields'] = 'ids'; // Only retrieve post IDs
			$query                = new \WP_Query( $query_args );
			$total               += $query->post_count;
		}
		return $total;
	}

	/**
	 * Exports the posts associated with a specific term to the CSV file.
	 *
	 * @param WP_Term $term The term to export.
	 * @param array   $opts Options for the export.
	 */
	private function export_term( $term, $opts ) {
		$query_args = $this->get_query_args( $term->slug, $opts );
		$query      = new \WP_Query( $query_args );
		$posts      = $query->posts;
		$total      = count( $posts );

		for ( $i = 0; $i < $total; $i++ ) {
			$post = $posts[ $i ];

			$this->put_csv(
				[
					$term->name,
					$term->slug,
					$post->ID,
					$post->post_type,
					$post->post_title,
					get_permalink( $post ),
					$post->post_status,
					admin_url( "post.php?post={$post->ID}&action=edit" ),
					get_the_author_meta( 'display_name', $post->post_author ),
					$post->post_date,
					$post->post_modified,
					'',
				]
			);

			if ( 0 === $i % 100 ) {
				\BlockCatalog\Utility\clear_caches();
			}

			if ( ! empty( $opts['progress'] ) ) {
				// tick the progress bar if it exists.
				$opts['progress']->tick();
			}
		}
	}

	/**
	 * Constructs the query arguments for retrieving posts associated with a term slug.
	 *
	 * @param string $term_slug The slug of the term.
	 * @param array  $opts Options for the query.
	 * @return array Query arguments.
	 */
	public function get_query_args( $term_slug, $opts ) {
		return array(
			'post_type'      => isset( $opts['post_type'] ) ? $opts['post_type'] : get_post_types( array( 'public' => true ) ),
			'post_status'    => ! empty( $opts['post_status'] ) ? $opts['post_status'] : 'publish',
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => BLOCK_CATALOG_TAXONOMY,
					'field'    => 'slug',
					'terms'    => $term_slug,
				),
			),
			'posts_per_page' => isset( $opts['posts_per_block'] ) ? intval( $opts['posts_per_block'] ) : -1,
		);
	}

	/**
	 * Lazy initializes the wp filesystem.
	 *
	 * @return \WP_Filesystem_Base The filesystem object.
	 */
	private function get_wp_filesystem() {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}

	/**
	 * Checks if the output path is writable.
	 *
	 * @param string $output The path to the output file.
	 * @return bool True if the path is writable, false otherwise.
	 */
	private function is_output_writable( $output ) {
		$filesystem = $this->get_wp_filesystem();
		return $filesystem->is_writable( $output );
	}

	/**
	 * Add a row to the CSV file buffer.
	 *
	 * @param array $row The row to write.
	 */
	private function put_csv( $row ) {
		$this->csv_buffer[] = $row;
	}

	/**
	 * Flush the CSV buffer to the output file.
	 *
	 * @param string $output_path The path to the output file.
	 * @return bool True if the buffer was flushed successfully, false otherwise.
	 */
	private function flush_csv( $output_path ) {
		$filesystem = $this->get_wp_filesystem();
		$output     = '';

		foreach ( $this->csv_buffer as $row ) {
			$row     = array_map( [ $this, 'esc_csv' ], $row );
			$output .= implode( ',', $row ) . "\n";
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::log( "Writing to $output_path ..." );
		}

		return $filesystem->put_contents( $output_path, $output );
	}

	/**
	 * Escapes a value for inclusion in a CSV file.
	 *
	 * Quotes the value when it contains a comma, double quote, or newline (escaping
	 * embedded double quotes by doubling them), and neutralizes CSV/spreadsheet formula
	 * injection by prefixing a leading =, +, -, @, tab, or carriage return with a quote.
	 *
	 * @param string $data The input value to be escaped.
	 * @return string The escaped value.
	 */
	public function esc_csv( $data ) {
		$data = (string) $data;

		// Prevent CSV/spreadsheet formula injection by prefixing risky leading characters.
		if ( '' !== $data && in_array( $data[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			$data = "'" . $data;
		}

		$needs_quotes = false !== strpos( $data, '"' )
			|| false !== strpos( $data, ',' )
			|| false !== strpos( $data, "\n" )
			|| false !== strpos( $data, "\r" );

		if ( $needs_quotes ) {
			$data = str_replace( '"', '""', $data );
			$data = '"' . $data . '"';
		}

		return $data;
	}

	/**
	 * Checks if a term can be exported. Ignores top-level terms by default.
	 *
	 * @param WP_Term $term The term to check.
	 * @param array   $opts Options for the export.
	 * @return bool True if the term can be exported, false otherwise.
	 */
	public function can_export_term( $term, $opts ) {
		// When specific blocks are requested, export exactly those terms.
		if ( ! empty( $opts['blocks'] ) ) {
			return true;
		}

		$ignore_parent = isset( $opts['ignore_parent'] ) ? $opts['ignore_parent'] : true;
		$ignore_parent = filter_var( $ignore_parent, FILTER_VALIDATE_BOOLEAN );

		// if don't ignore top level terms, no need to check further
		if ( ! $ignore_parent ) {
			return true;
		}

		// if the term is a top-level term, ignore it
		if ( 0 === $term->parent ) {
			return false;
		}

		// if the term is not a top-level term, export it
		return true;
	}
}
