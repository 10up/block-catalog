<?php
/**
 * Tests that the block catalog index stays in step with post content.
 *
 * @package BlockCatalog
 */

namespace BlockCatalog\Tests\Integration;

use BlockCatalog\CatalogBuilder;
use WP_UnitTestCase;

/**
 * The catalog is derived data: it must mirror the current content of
 * supported posts and leave nothing behind when it is deleted.
 */
class CatalogIndexIntegrityTest extends WP_UnitTestCase {

	/**
	 * Returns the names of the catalog terms assigned to a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string[]
	 */
	private function catalog_names( int $post_id ): array {
		return wp_get_object_terms( $post_id, BLOCK_CATALOG_TAXONOMY, array( 'fields' => 'names' ) );
	}

	/**
	 * Deleting the index removes every relationship, term_taxonomy and term
	 * row of the catalog, and leaves other taxonomies alone.
	 *
	 * @return void
	 */
	public function test_delete_index_removes_every_catalog_row_and_nothing_else(): void {
		global $wpdb;

		$category_id = self::factory()->category->create( array( 'name' => 'Kept category' ) );
		$post_id     = self::factory()->post->create(
			array(
				'post_content'  => file_get_contents( FIXTURES_DIR . '/nested-blocks.html' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local fixture.
				'post_category' => array( $category_id ),
			)
		);

		$catalog_tt_ids = get_terms(
			array(
				'taxonomy'   => BLOCK_CATALOG_TAXONOMY,
				'hide_empty' => false,
				'fields'     => 'tt_ids',
			)
		);
		$catalog_ids    = get_terms(
			array(
				'taxonomy'   => BLOCK_CATALOG_TAXONOMY,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		$this->assertNotEmpty( $catalog_tt_ids, 'Saving the post should have catalogued it.' );

		( new CatalogBuilder() )->delete_index();

		$tt_list   = implode( ',', array_map( 'intval', $catalog_tt_ids ) );
		$term_list = implode( ',', array_map( 'intval', $catalog_ids ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertSame(
			'0',
			$wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ($tt_list)" ),
			'No post may stay related to a deleted catalog term.'
		);
		$this->assertSame(
			'0',
			$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s", BLOCK_CATALOG_TAXONOMY ) ),
			'No catalog term_taxonomy row may remain.'
		);
		$this->assertSame(
			'0',
			$wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->terms} WHERE term_id IN ($term_list)" ),
			'No catalog term row may remain.'
		);
		// phpcs:enable

		$this->assertSame( array( $category_id ), wp_get_post_categories( $post_id ), 'Other taxonomies must be untouched.' );
		$this->assertInstanceOf( \WP_Term::class, get_term( $category_id, 'category' ) );
	}

	/**
	 * Re-cataloguing a post replaces its terms, so a block removed from the
	 * content no longer lists the post.
	 *
	 * @return void
	 */
	public function test_recataloguing_drops_blocks_removed_from_the_content(): void {
		$heading = '<!-- wp:heading --><h2 class="wp-block-heading">Title</h2><!-- /wp:heading -->';
		$quote   = '<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p>Quoted</p><!-- /wp:paragraph --></blockquote><!-- /wp:quote -->';

		$post_id = self::factory()->post->create( array( 'post_content' => $heading . $quote ) );
		$this->assertContains( 'Quote', $this->catalog_names( $post_id ) );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $heading,
			)
		);

		$names = $this->catalog_names( $post_id );
		sort( $names );
		$this->assertSame( array( 'Core', 'Heading' ), $names );
	}

	/**
	 * Saving a post of a type the catalog doesn't support leaves it uncatalogued.
	 *
	 * @return void
	 */
	public function test_saving_an_unsupported_post_type_does_not_catalog_it(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'wp_block',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:paragraph --><p>Reusable</p><!-- /wp:paragraph -->',
			)
		);

		$this->assertSame( array(), $this->catalog_names( $post_id ) );
	}
}
