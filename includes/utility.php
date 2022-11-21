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
	global $wpdb, $wp_object_cache;

	$wpdb->queries = array();

	if ( is_object( $wp_object_cache ) ) {
		$wp_object_cache->group_ops      = array();
		$wp_object_cache->stats          = array();
		$wp_object_cache->memcache_debug = array();
		$wp_object_cache->cache          = array();

		if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
			$wp_object_cache->__remoteset(); // important
		}
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
	 * Allow plugins/themes to update post types for the block catalog taxonomy.
	 *
	 * @param array  $options  Default post types.
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
	return apply_filters( 'block_catalog_capability', 'edit_posts' );
}
