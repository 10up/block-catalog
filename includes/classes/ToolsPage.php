<?php
/**
 * ToolsPage
 *
 * @package BlockCatalog
 */

namespace BlockCatalog;

/**
 * ToolsPage provides the screen for indexing posts to build the block catalog.
 */
class ToolsPage {

	/**
	 * The tools page name
	 *
	 * @var string
	 */
	public $slug = 'block-catalog-tools';

	/**
	 * Registers the menu with WP.
	 */
	public function register() {
		add_action( 'admin_menu', [ $this, 'register_page' ] );
	}

	/**
	 * Adds tools submenu page.
	 */
	public function register_page() {
		add_submenu_page(
			apply_filters( 'block_catalog_tools_page_parent', 'tools.php' ),
			__( 'Block Catalog', 'block-catalog' ),
			__( 'Block Catalog', 'block-catalog' ),
			apply_filters( 'block_catalog_tools_page_cap', 'edit_posts' ),
			$this->slug,
			[ $this, 'render' ]
		);
	}

	/**
	 * Outputs the menu page contents.
	 */
	public function render() {
		$taxonomy   = new BlockCatalogTaxonomy();
		$post_types = $taxonomy->get_post_types();
		?>
		<h1>Block Catalog - Index</h1>

		<div id="index-notice" class="notice" style="display:none; margin-left: 0">
			<p id="index-notice-body">
				Testing
			</h4>
		</div>

		<h4 id="index-message" style="display:none">
		</h4>

		<div id="index-settings">

		<h4>Select Post Type(s)</h4>
		<form method="post" novalidate="novalidate">
		<?php foreach ( $post_types as $post_type ) { ?>
			<p>
				<label>
					<input checked type="checkbox" id="block-catalog-post-type" class="block-catalog-post-type" name="post_types[]" value="<?php echo esc_attr( $post_type ); ?>">
					<?php echo esc_html( get_post_type_object( $post_type )->labels->singular_name ); ?>
				</label>
			</p>
		<?php } ?>

		<p class="submit">
			<input type="button" name="submit" id="submit" class="button button-primary" value="Index Posts">
		</p>
		</div>

		<div id="index-status" style="display:none">
			<progress id="index-progress" value="0" max="100">
	    </progress>
			<p class="cancel">
				<input type="button" name="cancel" id="cancel" class="button button-primary" value="Cancel">
			</p>
		</div>

		<div id="index-errors" style="display:none">
			<h4>Errors</h4>
			<ul id="index-errors-list">
			</ul>
		</div>

		</form>
		<?php

		wp_enqueue_script( 'block_catalog_plugin_tools' );
	}

}
