<?php
/**
 * Tests that one capability gates both the Tools page and the REST routes.
 *
 * @package BlockCatalog
 */

namespace BlockCatalog\Tests\Integration;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * The block_catalog_capability filter decides who may open
 * Tools > Block Catalog and who may call its REST routes. Both must follow
 * the same capability, or a user sees a page whose requests all fail.
 */
class CapabilityGateTest extends WP_UnitTestCase {

	/**
	 * Admin menu globals saved before each test.
	 *
	 * @var array
	 */
	private $saved_globals = array();

	/**
	 * Names of the admin menu globals the page access check reads.
	 *
	 * @var string[]
	 */
	const MENU_GLOBALS = array( 'menu', 'submenu', '_wp_submenu_nopriv', '_wp_menu_nopriv', '_registered_pages', '_parent_pages', 'admin_page_hooks', 'pagenow', 'plugin_page', 'typenow' );

	/**
	 * Loads the admin menu API and saves the menu globals.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		foreach ( self::MENU_GLOBALS as $name ) {
			$this->saved_globals[ $name ] = $GLOBALS[ $name ] ?? null;
		}
	}

	/**
	 * Restores the menu globals.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		foreach ( $this->saved_globals as $name => $value ) {
			if ( null === $value ) {
				unset( $GLOBALS[ $name ] );
			} else {
				$GLOBALS[ $name ] = $value;
			}
		}

		parent::tear_down();
	}

	/**
	 * Whether a user may open Tools > Block Catalog.
	 *
	 * Mirrors a request to tools.php?page=block-catalog-tools: wp-admin builds
	 * the menu for the current user by firing admin_menu, then asks
	 * user_can_access_admin_page() and answers 403 when it returns false.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private function can_open_tools_page( int $user_id ): bool {
		wp_set_current_user( $user_id );

		$GLOBALS['menu']               = array();
		$GLOBALS['submenu']            = array();
		$GLOBALS['_wp_submenu_nopriv'] = array();
		$GLOBALS['_wp_menu_nopriv']    = array();
		$GLOBALS['_registered_pages']  = array();
		$GLOBALS['_parent_pages']      = array();
		$GLOBALS['admin_page_hooks']   = array();
		$GLOBALS['pagenow']            = 'tools.php';
		$GLOBALS['plugin_page']        = 'block-catalog-tools';
		$GLOBALS['typenow']            = '';

		do_action( 'admin_menu', '' );

		return user_can_access_admin_page();
	}

	/**
	 * Sends a request to a block catalog REST route.
	 *
	 * @param string $route Route path.
	 * @return int Response status.
	 */
	private function rest_status( string $route ): int {
		$request = new WP_REST_Request( 'POST', $route );
		if ( '/block-catalog/v1/index' === $route ) {
			$request->set_body_params( array( 'post_ids' => array( 1 ) ) );
		}

		return rest_do_request( $request )->get_status();
	}

	/**
	 * When the capability is raised to manage_options, an Editor loses both
	 * the REST routes and the Tools page, while an Administrator keeps the page.
	 *
	 * @return void
	 */
	public function test_raised_capability_locks_editor_out_of_routes_and_page(): void {
		add_filter(
			'block_catalog_capability',
			static function () {
				return 'manage_options';
			}
		);

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$admin  = self::factory()->user->create( array( 'role' => 'administrator' ) );

		wp_set_current_user( $editor );
		foreach ( array( '/block-catalog/v1/posts', '/block-catalog/v1/index', '/block-catalog/v1/terms', '/block-catalog/v1/delete-index' ) as $route ) {
			$this->assertSame( 403, $this->rest_status( $route ), "Editor on $route" );
		}

		$this->assertFalse( $this->can_open_tools_page( $editor ), 'Editor must not open the Tools page.' );
		$this->assertTrue( $this->can_open_tools_page( $admin ), 'Administrator must still open the Tools page.' );
	}

	/**
	 * With the default capability, an Author can't open the Tools page and an
	 * Editor can.
	 *
	 * @return void
	 */
	public function test_default_capability_keeps_author_out_of_tools_page(): void {
		$author = self::factory()->user->create( array( 'role' => 'author' ) );
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );

		$this->assertFalse( $this->can_open_tools_page( $author ), 'Author must not open the Tools page.' );
		$this->assertTrue( $this->can_open_tools_page( $editor ), 'Editor must open the Tools page.' );
	}
}
