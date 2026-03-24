<?php
/**
 * Unit tests for SettingsPage::enqueue_scripts() — wp_set_script_translations wiring.
 *
 * Covers I18N-06: wp_set_script_translations called for wmr-admin-settings.
 *
 * @package WpMembershipRegistration\Tests\Unit\Admin
 */

namespace WpMembershipRegistration\Tests\Unit\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpMembershipRegistration\Admin\SettingsPage;

class SettingsPageEnqueueTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		if ( ! defined( 'WMR_PLUGIN_URL' ) ) {
			define( 'WMR_PLUGIN_URL', 'http://example.com/wp-content/plugins/wp-membership-registration/' );
		}
		if ( ! defined( 'WMR_PLUGIN_DIR' ) ) {
			define( 'WMR_PLUGIN_DIR', dirname( __DIR__, 3 ) . '/' );
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
	 * Test: enqueue_scripts() calls wp_set_script_translations for wmr-admin-settings.
	 */
	public function test_enqueue_calls_set_script_translations_for_admin(): void {
		Functions\stubs(
			[
				'wp_enqueue_style'   => true,
				'wp_enqueue_script'  => true,
				'wp_enqueue_media'   => true,
				'wp_localize_script' => true,
				'wp_create_nonce'    => 'test_nonce',
				'admin_url'          => fn( $p = '' ) => 'http://example.com/wp-admin/' . $p,
				'add_options_page'   => 'settings_page_wmr-settings',
			]
		);

		Functions\expect( 'wp_set_script_translations' )
			->once()
			->with(
				'wmr-admin-settings',
				'wp-membership-registration',
				\Mockery::on( fn( $p ) => str_ends_with( $p, 'languages' ) )
			);

		$page = new SettingsPage();
		// First, register() to set hook_suffix.
		$page->register();
		// Then call enqueue_scripts with the matching hook suffix.
		$page->enqueue_scripts( 'settings_page_wmr-settings' );
	}

	/**
	 * Test: enqueue_scripts() does NOT run for non-matching hook suffixes.
	 */
	public function test_enqueue_skips_non_matching_hook(): void {
		Functions\stubs(
			[
				'add_options_page' => 'settings_page_wmr-settings',
			]
		);

		Functions\expect( 'wp_set_script_translations' )->never();
		Functions\expect( 'wp_enqueue_script' )->never();

		$page = new SettingsPage();
		$page->register();
		$page->enqueue_scripts( 'some_other_page' );

		$this->addToAssertionCount( 1 );
	}
}
