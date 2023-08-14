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
	 * Exports the posts associated with the 'block_catalog' taxonomy to a CSV file.
	 *
	 * @param string $output The path to the output CSV file.
	 * @param array  $opts Options for the export, including 'post_type' and 'posts_per_block'.
	 * @return array|WP_Error Summary of the operation, or WP_Error on failure.
	 */
	public function export( $output, $opts ) {
		if ( ! is_writable( dirname( $output ) ) ) {
			return new \WP_Error( 'output_not_writable', __( 'The output path is not writable', 'block-catalog' ) );
		}

		$terms = $this->get_block_catalog_terms();

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
		$handle      = fopen( $output, 'w' );
		fputcsv( $handle, array( 'block_name', 'block_slug', 'post_id', 'post_type', 'post_title', 'permalink', 'status' ) );

		// when running in WP CLI mode, there is a progress bar
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$progress = \WP_CLI\Utils\make_progress_bar( "Exporting catalog usage for $total_posts posts ...", $total_posts );
			$opts['progress'] = $progress;
		}

		foreach ( $terms as $term ) {
			$this->export_term( $handle, $term, $opts );
		}

		fclose( $handle );

		return array(
			'success'     => true,
			'message'     => __( 'Exported successfully', 'block-catalog' ),
			'total_posts' => $total_posts,
		);
	}

	/**
	 * Retrieves all terms associated with the 'block_catalog' taxonomy.
	 *
	 * @return array List of WP_Term objects.
	 */
	private function get_block_catalog_terms() {
		return get_terms(
			array(
				'taxonomy'   => BLOCK_CATALOG_TAXONOMY,
				'hide_empty' => false,
			)
		);
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
	 * @param resource $handle File handle for the CSV file.
	 * @param WP_Term  $term The term to export.
	 * @param array    $opts Options for the export.
	 */
	private function export_term( $handle, $term, $opts ) {
		$query_args = $this->get_query_args( $term->slug, $opts );
		$query      = new \WP_Query( $query_args );

		for ( $i = 0; $i < count( $query->posts ); $i++ ) {
			$post = $query->posts[ $i ];
			$this->write_csv_row( $handle, $term, $post );

			if ( $i % 100 == 0 ) {
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
	private function get_query_args( $term_slug, $opts ) {
		return array(
			'post_type'      => isset( $opts['post_type'] ) ? $opts['post_type'] : get_post_types( array( 'public' => true ) ),
			'tax_query'      => array(
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
	 * Writes a row to the CSV file for a specific post associated with a term.
	 *
	 * @param resource $handle File handle for the CSV file.
	 * @param WP_Term  $term The term associated with the post.
	 * @param WP_Post  $post The post to write to the CSV file.
	 */
	private function write_csv_row( $handle, $term, $post ) {
		fputcsv(
			$handle,
			[
				$term->name,
				$term->slug,
				$post->ID,
				$post->post_type,
				$post->post_title,
				get_permalink( $post ),
				$post->post_status,
			]
		);
	}
}
