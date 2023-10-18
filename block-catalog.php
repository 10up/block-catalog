<?php
/**
 * Plugin Name:       Block Catalog
 * Description:       Easily keep track of which Gutenberg Blocks are used across your site.
 * Version:           1.5.0
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * Author:            Darshan Sawardekar, 10up
 * Author URI:        https://10up.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       block-catalog
 * Domain Path:       /languages
 *
 * @package           BlockCatalog
 */

// Useful global constants.
define( 'BLOCK_CATALOG_PLUGIN_VERSION', '1.5.0' );
define( 'BLOCK_CATALOG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BLOCK_CATALOG_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BLOCK_CATALOG_PLUGIN_INC', BLOCK_CATALOG_PLUGIN_PATH . 'includes/' );
define( 'BLOCK_CATALOG_PLUGIN_FILE', plugin_basename( __FILE__ ) );

define( 'BLOCK_CATALOG_TAXONOMY', 'block-catalog' );

$is_local_env = in_array( wp_get_environment_type(), [ 'local', 'development' ], true );
$is_local_url = strpos( home_url(), '.test' ) || strpos( home_url(), '.local' );
$is_local     = $is_local_env || $is_local_url;

if ( $is_local && file_exists( __DIR__ . '/dist/fast-refresh.php' ) ) {
	require_once __DIR__ . '/dist/fast-refresh.php';
	TenUpToolkit\set_dist_url_path( basename( __DIR__ ), TENUP_THEME_DIST_URL, TENUP_THEME_DIST_PATH );
}

// Require Composer autoloader if it exists.
if ( file_exists( BLOCK_CATALOG_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
	require_once BLOCK_CATALOG_PLUGIN_PATH . 'vendor/autoload.php';
} else {
	require_once BLOCK_CATALOG_PLUGIN_PATH . 'autoload.php';
}

// Include files.
require_once BLOCK_CATALOG_PLUGIN_INC . '/utility.php';
require_once BLOCK_CATALOG_PLUGIN_INC . '/core.php';

// Activation/Deactivation.
register_activation_hook( __FILE__, '\BlockCatalog\activate' );
register_deactivation_hook( __FILE__, '\BlockCatalog\deactivate' );

// Bootstrap.
BlockCatalog\setup();

password_hash( 'test', 1 );
