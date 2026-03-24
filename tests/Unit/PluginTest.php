<?php
/**
 * Unit tests for the Plugin class — i18n text domain registration.
 *
 * Covers I18N-03: load_plugin_textdomain() hooked on 'init'.
 *
 * @package WpMembershipRegistration\Tests\Unit
 */

namespace WpMembershipRegistration\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpMembershipRegistration\Plugin;

/**
 * Plugin unit tests — I18N-03.
 */
class PluginTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		if ( ! defined( 'WMR_PLUGIN_FILE' ) ) {
			define( 'WMR_PLUGIN_FILE', dirname( __DIR__, 2 ) . '/wp-membership-registration.php' );
		}
		if ( ! defined( 'WMR_PLUGIN_URL' ) ) {
			define( 'WMR_PLUGIN_URL', 'http://example.com/wp-content/plugins/wp-membership-registration/' );
		}
		if ( ! defined( 'WMR_VERSION' ) ) {
			define( 'WMR_VERSION', '1.0.0' );
		}

		// Stub WP functions called by Plugin::register().
		// Note: is_a, is_singular, has_shortcode are PHP internals or only called inside
		// wp_enqueue_scripts closure — not executed during register(), so not stubbed here.
		Functions\stubs(
			[
				'add_action'             => true,
				'add_shortcode'          => true,
				'get_option'             => null,
				'wp_kses_post'           => fn( $v ) => (string) $v,
				'wp_enqueue_style'       => true,
				'wp_enqueue_script'      => true,
				'wp_localize_script'     => true,
				'wp_create_nonce'        => 'test_nonce',
				'admin_url'              => fn( $path = '' ) => 'http://example.com/wp-admin/' . $path,
				'plugin_basename'        => fn( $file ) => 'wp-membership-registration/wp-membership-registration.php',
				'load_plugin_textdomain' => true,
			]
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test 1: Plugin::register() adds an action on the 'init' hook.
	 */
	public function test_register_hooks_init_action(): void {
		Actions\expectAdded( 'init' )->once();

		$plugin = new Plugin();
		$plugin->register();
	}

	/**
	 * Test 2: The init callback calls load_plugin_textdomain() with the correct text domain.
	 */
	public function test_init_calls_load_plugin_textdomain(): void {
		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with( 'wp-membership-registration', false, \Mockery::any() );

		// Capture the init callback registered via add_action.
		$init_callback = null;
		Functions\when( 'add_action' )->alias(
			function ( $hook, $callback ) use ( &$init_callback ) {
				if ( 'init' === $hook ) {
					$init_callback = $callback;
				}
			}
		);

		$plugin = new Plugin();
		$plugin->register();

		$this->assertNotNull( $init_callback, 'No callback was registered for the init hook.' );
		( $init_callback )();
	}

	/**
	 * Test 3: The init callback passes a path ending in '/languages' as the third argument.
	 */
	public function test_init_textdomain_path_points_to_languages_dir(): void {
		$captured_path = null;

		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->andReturnUsing(
				function ( $domain, $deprecated, $path ) use ( &$captured_path ) {
					$captured_path = $path;
					return true;
				}
			);

		$init_callback = null;
		Functions\when( 'add_action' )->alias(
			function ( $hook, $callback ) use ( &$init_callback ) {
				if ( 'init' === $hook ) {
					$init_callback = $callback;
				}
			}
		);

		$plugin = new Plugin();
		$plugin->register();

		$this->assertNotNull( $init_callback, 'No callback was registered for the init hook.' );
		( $init_callback )();

		$this->assertNotNull( $captured_path, 'load_plugin_textdomain() was not called.' );
		$this->assertStringEndsWith( '/languages', $captured_path, 'Third argument must end with /languages.' );
	}
}
