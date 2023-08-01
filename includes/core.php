<?php
/**
 * Core plugin functionality.
 *
 * @package BlockCatalog
 */

namespace BlockCatalog;

use \WP_Error;
use BlockCatalog\Utility;

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	add_action( 'init', $n( 'i18n' ) );
	add_action( 'init', $n( 'init' ), 1000000 );
	add_action( 'wp_enqueue_scripts', $n( 'styles' ) );
	add_action( 'admin_enqueue_scripts', $n( 'admin_scripts' ) );
	add_action( 'admin_enqueue_scripts', $n( 'admin_styles' ) );
	add_action( 'save_post', $n( 'update_post_block_catalog' ) );
	add_action( 'admin_notices', $n( 'render_index_notice' ) );

	// Hook to allow async or defer on asset loading.
	add_filter( 'script_loader_tag', $n( 'script_loader_tag' ), 10, 2 );

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		\WP_CLI::add_command( 'block-catalog', '\BlockCatalog\CatalogCommand' );
	}

	$tools_page = new ToolsPage();
	$tools_page->register();

	$rest_support = new RESTSupport();
	$rest_support->register();

	do_action( 'block_catalog_plugin_loaded' );
}

/**
 * Registers the default textdomain.
 *
 * @return void
 */
function i18n() {
	/**
	 * Filters the plugin locale
	 *
	 * @param string $locale The plugin locale
	 * @param string $slug The plugin slug
	 */
	$locale = apply_filters( 'plugin_locale', get_locale(), 'block-catalog' );
	load_textdomain( 'block-catalog', WP_LANG_DIR . '/block-catalog/block-catalog-' . $locale . '.mo' );
	load_plugin_textdomain( 'block-catalog', false, plugin_basename( BLOCK_CATALOG_PLUGIN_PATH ) . '/languages/' );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @return void
 */
function init() {
	do_action( 'block_catalog_plugin_init' );

	$block_catalog_taxonomy = new BlockCatalogTaxonomy();
	$block_catalog_taxonomy->register();
}

/**
 * Updates the block catalog for the specified post.
 *
 * @param int $post_id The post id
 */
function update_post_block_catalog( $post_id ) {
	$supported = \BlockCatalog\Utility\get_supported_post_types();
	$post_type = get_post_type( $post_id );

	if ( ! in_array( $post_type, $supported, true ) ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	$builder = new CatalogBuilder();
	$builder->catalog( $post_id );
}

/**
 * Activate the plugin
 *
 * @return void
 */
function activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	init();
	flush_rewrite_rules();

}

/**
 * Displays a notice message if not indexed.
 */
function render_index_notice() {
	$notice_shown = filter_var( get_option( 'block_catalog_notice_shown' ), FILTER_VALIDATE_BOOLEAN );

	if ( $notice_shown ) {
		return;
	}

	update_option( 'block_catalog_notice_shown', 1 );

	?>
	<div class="notice notice-success is-dismissible">
		<p>
			<?php /* translators: %s is the tools page url */ ?>
			<?php echo wp_kses_post( sprintf( __( 'The Block Catalog needs to be indexed. <a href="%s">Index Now</a>', 'block-catalog' ), esc_url( admin_url( 'tools.php?page=block-catalog-tools' ) ) ) ); ?>
		</p>
	</div>
	<?php
}

/**
 * Deactivate the plugin
 *
 * Uninstall routines should be in uninstall.php
 *
 * @return void
 */
function deactivate() {

}


/**
 * The list of knows contexts for enqueuing scripts/styles.
 *
 * @return array
 */
function get_enqueue_contexts() {
	return [ 'admin' ];
}

/**
 * Generate an URL to a script, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $script Script file name (no .js extension)
 * @param string $context Context for the script ('admin', 'frontend', or 'shared')
 *
 * @return string|WP_Error URL
 */
function script_url( $script, $context ) {

	if ( ! in_array( $context, get_enqueue_contexts(), true ) ) {
		return new WP_Error( 'invalid_enqueue_context', 'Invalid $context specified in BlockCatalog script loader.' );
	}

	return BLOCK_CATALOG_PLUGIN_URL . "dist/js/{$script}.js";

}

/**
 * Generate an URL to a stylesheet, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $stylesheet Stylesheet file name (no .css extension)
 * @param string $context Context for the script ('admin', 'frontend', or 'shared')
 *
 * @return string URL
 */
function style_url( $stylesheet, $context ) {

	if ( ! in_array( $context, get_enqueue_contexts(), true ) ) {
		return new WP_Error( 'invalid_enqueue_context', __( 'Invalid $context specified in BlockCatalog stylesheet loader.', 'block-catalog' ) );
	}

	return BLOCK_CATALOG_PLUGIN_URL . "dist/css/{$stylesheet}.css";

}

/**
 * Enqueue scripts for admin.
 *
 * @return void
 */
function admin_scripts() {
}

/**
 * Enqueue styles for front-end.
 *
 * @return void
 */
function styles() {
}

/**
 * Enqueue styles for admin.
 *
 * @return void
 */
function admin_styles() {
	wp_enqueue_style(
		'block_catalog_plugin_tools',
		style_url( 'tools', 'admin' ),
		[],
		BLOCK_CATALOG_PLUGIN_VERSION
	);
}

/**
 * Add async/defer attributes to enqueued scripts that have the specified script_execution flag.
 *
 * @link https://core.trac.wordpress.org/ticket/12009
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @return string
 */
function script_loader_tag( $tag, $handle ) {
	$script_execution = wp_scripts()->get_data( $handle, 'script_execution' );

	if ( ! $script_execution ) {
		return $tag;
	}

	if ( 'async' !== $script_execution && 'defer' !== $script_execution ) {
		return $tag;
	}

	// Abort adding async/defer for scripts that have this script as a dependency. _doing_it_wrong()?
	foreach ( wp_scripts()->registered as $script ) {
		if ( in_array( $handle, $script->deps, true ) ) {
			return $tag;
		}
	}

	// Add the attribute if it hasn't already been added.
	if ( ! preg_match( ":\s$script_execution(=|>|\s):", $tag ) ) {
		$tag = preg_replace( ':(?=></script>):', " $script_execution", $tag, 1 );
	}

	return $tag;
}
