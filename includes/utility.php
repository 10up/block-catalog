<?php
/**
 * Utility functions for the plugin.
 *
 * This file is for custom helper functions.
 * These should not be confused with WordPress template
 * tags. Template tags typically use prefixing, as opposed
 * to Namespaces.
 *
 * @link https://developer.wordpress.org/themes/basics/template-tags/
 * @package BlockCatalog
 */

namespace BlockCatalog\Utility;

/**
 * Get asset info from extracted asset files
 *
 * @param string $slug Asset slug as defined in build/webpack configuration
 * @param string $attribute Optional attribute to get. Can be version or dependencies
 * @return string|array
 */
function get_asset_info( $slug, $attribute = null ) {
	if ( file_exists( BLOCK_CATALOG_PLUGIN_PATH . 'dist/js/' . $slug . '.asset.php' ) ) {
		$asset = require BLOCK_CATALOG_PLUGIN_PATH . 'dist/js/' . $slug . '.asset.php';
	} elseif ( file_exists( BLOCK_CATALOG_PLUGIN_PATH . 'dist/css/' . $slug . '.asset.php' ) ) {
		$asset = require BLOCK_CATALOG_PLUGIN_PATH . 'dist/css/' . $slug . '.asset.php';
	} else {
		return null;
	}

	if ( ! empty( $attribute ) && isset( $asset[ $attribute ] ) ) {
		return $asset[ $attribute ];
	}

	return $asset;
}

/**
 * Start bulk operation global updates.
 *
 * @props VIP
 */
function start_bulk_operation() {
	// Do not send notification when post is updated to 'published'
	add_filter( 'wpcom_pushpress_should_send_ping', '__return_false' );

	// Disable term count updates for speed
	wp_defer_term_counting( true );

	if ( class_exists( 'ES_WP_Indexing_Trigger' ) ) {
		ES_WP_Indexing_Trigger::get_instance()->disable(); // disconnects the wp action hooks that trigger indexing jobs
	}

	if ( ! defined( 'WP_IMPORTING' ) ) {
		define( 'WP_IMPORTING', true );
	}

	if ( ! defined( 'DOING_AUTOSAVE' ) ) {
		define( 'DOING_AUTOSAVE', true );
	}
}

/**
 * Stop bulk operation global updates
 *
 * @props VIP
 */
function stop_bulk_operation() {
	wp_defer_term_counting( false );
}

/**
 * Clear object caches to avoid oom errors
 *
 * @props VIP
 */
function clear_caches() {
	global $wpdb;

	$wpdb->queries = array();

	// Clear runtime cache to prevent out of memory errors during bulk operations
	// Use wp_cache_supports() to check for runtime flush capability (WordPress 6.0+)
	if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_runtime' ) ) {
		wp_cache_flush_runtime();
	} else {
		// Fallback to wp_cache_flush() for older WordPress versions or implementations without runtime support
		wp_cache_flush();
	}

}

/**
 * Returns the list of post types supported by the BlockCatalog plugin
 *
 * @return array
 */
function get_supported_post_types() {
	$post_types = get_post_types(
		[
			'show_in_rest' => true,
			'_builtin'     => false,
		]
	);

	/**
	 * List of other misc post types that don't need indexing.
	 */
	$excluded_post_types = [
		// Core
		'wp_navigation',

		// Jetpack
		'feedback',
		'jp_pay_order',
		'jp_pay_product',

		// Distributor
		'dt_subscription',
	];

	$post_types = array_diff( $post_types, $excluded_post_types );
	$post_types = array_merge( $post_types, [ 'post', 'page' ] );

	/**
	 * Filters the post types supported by the block catalog plugin.
	 *
	 * @param array $options Default post types
	 * @return array New list of post types
	 */
	$post_types = apply_filters(
		'block_catalog_post_types',
		$post_types,
	);

	return $post_types;
}

/**
 * Returns the capability name required to manage block catalogs
 *
 * @return string
 */
function get_required_capability() {
	/**
	 * Filters the capability name required to use the block catalog plugin.
	 *
	 * @param string $cap The capability name
	 * @return string The new capability name
	 */
	return apply_filters( 'block_catalog_capability', 'edit_posts' );
}
