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

	/** @var callable|null Captured init callback. */
	private $captured_init_callback = null;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->captured_init_callback = null;

		if ( ! defined( 'WMR_PLUGIN_FILE' ) ) {
			define( 'WMR_PLUGIN_FILE', dirname( __DIR__, 2 ) . '/wp-membership-registration.php' );
		}
		if ( ! defined( 'WMR_PLUGIN_URL' ) ) {
			define( 'WMR_PLUGIN_URL', 'http://example.com/wp-content/plugins/wp-membership-registration/' );
		}
		if ( ! defined( 'WMR_VERSION' ) ) {
			define( 'WMR_VERSION', '1.0.0' );
		}
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Register Plugin hooks and capture the 'init' callback.
	 * load_plugin_textdomain is NOT pre-stubbed; callers set up expectations themselves.
	 */
	private function register_plugin_and_capture_init(): void {
		$self = $this;

		Functions\when( 'add_action' )->alias(
			function ( $hook, $cb ) use ( $self ) {
				if ( 'init' === $hook ) {
					$self->captured_init_callback = $cb;
				}
			}
		);

		Functions\stubs(
			[
				'add_shortcode'      => true,
				'get_option'         => null,
				'wp_kses_post'       => fn( $v ) => (string) $v,
				'wp_enqueue_style'   => true,
				'wp_enqueue_script'  => true,
				'wp_localize_script' => true,
				'wp_create_nonce'    => 'test_nonce',
				'admin_url'          => fn( $p = '' ) => 'http://example.com/wp-admin/' . $p,
				'plugin_basename'    => fn( $f ) => 'wp-membership-registration/wp-membership-registration.php',
			]
		);

		( new Plugin() )->register();
	}

	/**
	 * Test 1: Plugin::register() adds an action on the 'init' hook.
	 */
	public function test_register_hooks_init_action(): void {
		Actions\expectAdded( 'init' )->once();

		Functions\stubs(
			[
				'add_shortcode'          => true,
				'get_option'             => null,
				'wp_kses_post'           => fn( $v ) => (string) $v,
				'wp_enqueue_style'       => true,
				'wp_enqueue_script'      => true,
				'wp_localize_script'     => true,
				'wp_create_nonce'        => 'test_nonce',
				'admin_url'              => fn( $p = '' ) => 'http://example.com/wp-admin/' . $p,
				'plugin_basename'        => fn( $f ) => 'wp-membership-registration/wp-membership-registration.php',
				'load_plugin_textdomain' => true,
			]
		);

		( new Plugin() )->register();

		// Brain Monkey validates expectAdded at tearDown; count here keeps PHPUnit happy.
		$this->addToAssertionCount( 1 );
	}

	/**
	 * Test 2: The init callback calls load_plugin_textdomain() with the correct text domain.
	 */
	public function test_init_calls_load_plugin_textdomain(): void {
		$this->register_plugin_and_capture_init();

		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with( 'wp-membership-registration', false, \Mockery::any() );

		$this->assertNotNull( $this->captured_init_callback, 'No callback was registered for the init hook.' );
		call_user_func( $this->captured_init_callback );
	}

	/**
	 * Test 3: The init callback passes a path ending in '/languages' as the third argument.
	 */
	public function test_init_textdomain_path_points_to_languages_dir(): void {
		$this->register_plugin_and_capture_init();

		$this->assertNotNull( $this->captured_init_callback, 'No callback was registered for the init hook.' );

		// Use expect with a custom argument matcher to capture and assert the path.
		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with(
				\Mockery::any(),
				\Mockery::any(),
				\Mockery::on(
					function ( $path ) {
						return str_ends_with( $path, '/languages' );
					}
				)
			);

		call_user_func( $this->captured_init_callback );
	}
}
