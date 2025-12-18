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
		add_filter( 'plugin_action_links_' . BLOCK_CATALOG_PLUGIN_BASENAME, array( $this, 'filter_plugin_action_links' ) );
	}

	/**
	 * Adds tools submenu page.
	 */
	public function register_page() {
		add_submenu_page(
			/**
			 * Filters the name of the block catalog parent menu.
			 *
			 * @param string $name The parent menu name
			 * @return string The new parent menu name
			 */
			apply_filters( 'block_catalog_tools_page_parent', 'tools.php' ),
			__( 'Block Catalog', 'block-catalog' ),
			__( 'Block Catalog', 'block-catalog' ),
			\BlockCatalog\Utility\get_required_capability(),
			$this->slug,
			[ $this, 'render' ]
		);
	}

	/**
	 * Outputs the menu page contents.
	 */
	public function render() {
		$post_types = \BlockCatalog\Utility\get_supported_post_types();
		?>
		<h1><?php esc_html_e( 'Block Catalog - Index', 'block-catalog' ); ?></h1>

		<div id="index-notice" class="notice" style="display:none; margin-left: 0">
			<p id="index-notice-body">
			</h4>
		</div>

		<h4 id="index-message" style="display:none">
		</h4>

		<div id="index-settings">

		<h4>
			<?php esc_html_e( 'Select Post Type(s)', 'block-catalog' ); ?>
		</h4>

		<form method="post" novalidate="novalidate">

		<?php
		foreach ( $post_types as $post_type ) {

			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}

			$post_type_obj = get_post_type_object( $post_type );
			$label         = $post_type_obj->labels->singular_name;
			?>
			<p>
				<label>
					<input checked type="checkbox" id="block-catalog-post-type" class="block-catalog-post-type" name="post_types[]" value="<?php echo esc_attr( $post_type ); ?>">
				<?php echo esc_html( $label ); ?>
				</label>
			</p>
		<?php } ?>

		<p class="submit">
			<input type="button" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Index Posts', 'block-catalog' ); ?>">
			<input type="button" name="reset" id="delete-index" class="button button-secondary" value="<?php esc_attr_e( 'Delete Index', 'block-catalog' ); ?>">
		</p>
		</div>

		<div id="index-status" style="display:none">
			<progress class="index-progress-bar" id="index-progress" value="50" max="100">
		</progress>

			<p class="cancel">
				<input type="button" name="cancel" id="cancel" class="button button-primary" value="<?php esc_attr_e( 'Cancel', 'block-catalog' ); ?>">
			</p>
		</div>

		<div id="delete-status" style="display:none">
			<progress class="index-progress-bar" id="delete-progress" value="50" max="100">
	    </progress>

			<p class="cancel">
				<input type="button" name="cancel-delete" id="cancel-delete" class="button button-primary" value="<?php esc_attr_e( 'Cancel', 'block-catalog' ); ?>">
			</p>
		</div>

		<div id="index-errors" style="display:none">
			<h4><?php esc_html_e( 'Errors', 'block-catalog' ); ?></h4>
			<ul id="index-errors-list">
			</ul>
		</div>

		</form>

		<?php

		wp_enqueue_script(
			'block_catalog_plugin_tools',
			script_url( 'tools', 'admin' ),
			[ 'wp-api-fetch', 'wp-polyfill', 'wp-i18n' ],
			BLOCK_CATALOG_PLUGIN_VERSION,
			true
		);

		wp_localize_script( 'block_catalog_plugin_tools', 'block_catalog', $this->get_settings() );
	}

	/**
	 * Returns the settings to send to the tools page.
	 *
	 * @return array
	 */
	public function get_settings() {
		return [
			'settings' => [
				/**
				 * Filters the number of posts indexed by the block catalog plugin per REST request.
				 *
				 * @param int $batch_size The batch size
				 * @return int The new batch size
				 */
				'index_batch_size'        => apply_filters( 'block_catalog_index_batch_size', 50 ),
				/**
				 * The number of terms deleted by the block catalog plugin per REST request.
				 *
				 * @param int $batch_size The batch size
				 * @return int The new batch size
				 */
				'delete_index_batch_size' => apply_filters( 'block_catalog_delete_index_batch_size', 2 ),
				'posts_endpoint'          => rest_url( 'block-catalog/v1/posts' ),
				'index_endpoint'          => rest_url( 'block-catalog/v1/index' ),
				'terms_endpoint'          => rest_url( 'block-catalog/v1/terms' ),
				'delete_index_endpoint'   => rest_url( 'block-catalog/v1/delete-index' ),
				'catalog_page'            => admin_url( 'edit-tags.php?taxonomy=' . BLOCK_CATALOG_TAXONOMY ),
			],
		];
	}

	/**
	 * Add the action links to the plugin page.
	 *
	 * @param array $links The Action links for the plugin.
	 * @return array Modified action links to include custom link.
	 */
	public function filter_plugin_action_links( $links ) {

		if ( ! is_array( $links ) ) {
			return $links;
		}

		return array_merge(
			array(
				'index-posts' => sprintf(
					'<a href="%s"> %s </a>',
					esc_url( admin_url( 'tools.php?page=block-catalog-tools' ) ),
					esc_html__( 'Index Posts', 'block-catalog' )
				),
			),
			$links
		);
	}
}
