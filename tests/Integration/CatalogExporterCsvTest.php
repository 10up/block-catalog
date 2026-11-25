<?php
/**
 * Tests the rows and fields of the block catalog CSV export.
 *
 * @package BlockCatalog
 */

namespace BlockCatalog\Tests\Integration;

use BlockCatalog\CatalogExporter;
use WP_UnitTestCase;

/**
 * The export writes one row per block term per post. Fields that contain
 * commas or quotes are quoted, and top-level namespace terms (e.g. "Core")
 * are left out unless ignore_parent is false.
 */
class CatalogExporterCsvTest extends WP_UnitTestCase {

	/**
	 * CSV file written by the current test.
	 *
	 * @var string
	 */
	private $csv_file;

	/**
	 * Picks a fresh output path.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		$this->csv_file = get_temp_dir() . uniqid( 'block-catalog-', true ) . '.csv';
	}

	/**
	 * Deletes the output file.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		if ( file_exists( $this->csv_file ) ) {
			unlink( $this->csv_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test temp file.
		}

		parent::tear_down();
	}

	/**
	 * Exports the catalog for posts and parses the file.
	 *
	 * @param array $opts Extra export options.
	 * @return array[] Rows keyed by the header columns, without the header.
	 */
	private function export_rows( array $opts = array() ): array {
		$result = ( new CatalogExporter() )->export( $this->csv_file, array_merge( array( 'post_type' => 'post' ), $opts ) );
		$this->assertTrue( $result['success'], 'The export should succeed.' );

		$lines  = array_filter( explode( "\n", file_get_contents( $this->csv_file ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Test temp file.
		$header = str_getcsv( array_shift( $lines ), ',', '"', '' );

		$rows = array();
		foreach ( $lines as $line ) {
			$fields = str_getcsv( $line, ',', '"', '' );
			$this->assertCount( count( $header ), $fields, "Row has the wrong number of fields: $line" );
			$rows[] = array_combine( $header, $fields );
		}

		return $rows;
	}

	/**
	 * Creates a published post with a single paragraph block.
	 *
	 * @param string $title Post title.
	 * @return int Post ID.
	 */
	private function create_paragraph_post( string $title ): int {
		return self::factory()->post->create(
			array(
				'post_title'   => $title,
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:paragraph --><p>Body</p><!-- /wp:paragraph -->',
			)
		);
	}

	/**
	 * A title containing a comma and double quotes stays one field and
	 * round-trips unchanged.
	 *
	 * @return void
	 */
	public function test_title_with_comma_and_quotes_stays_one_field(): void {
		$post_id = $this->create_paragraph_post( 'Say "hello", then leave' );

		$rows = $this->export_rows();

		$this->assertCount( 1, $rows );
		$this->assertSame( 'core-paragraph', $rows[0]['block_slug'] );
		$this->assertSame( (string) $post_id, $rows[0]['post_id'] );
		$this->assertSame( get_post_field( 'post_title', $post_id ), $rows[0]['post_title'] );
		$this->assertSame( 'publish', $rows[0]['status'], 'Columns after the title must not shift.' );
	}

	/**
	 * Top-level namespace terms are left out by default and included when
	 * ignore_parent is false.
	 *
	 * @return void
	 */
	public function test_namespace_terms_are_only_exported_when_ignore_parent_is_false(): void {
		$this->create_paragraph_post( 'Plain title' );

		$default = array_column( $this->export_rows(), 'block_slug' );
		$this->assertSame( array( 'core-paragraph' ), $default );

		$with_parents = array_column( $this->export_rows( array( 'ignore_parent' => 'false' ) ), 'block_slug' );
		sort( $with_parents );
		$this->assertSame( array( 'core', 'core-paragraph' ), $with_parents );
	}
}
