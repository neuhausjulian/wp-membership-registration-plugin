<?php
/**
 * Settings page registration and script enqueue.
 *
 * @package WpMembershipRegistration
 */

namespace WpMembershipRegistration\Admin;

/**
 * Registers the plugin settings page under Settings menu.
 */
class SettingsPage {

	/**
	 * Hook suffix returned by add_options_page().
	 *
	 * @var string
	 */
	private string $hook_suffix = '';

	/**
	 * Register the settings page via add_options_page().
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hook_suffix = (string) add_options_page(
			__( 'Membership Registration Settings', 'wp-membership-registration' ),
			__( 'Membership Registration', 'wp-membership-registration' ),
			'manage_options',
			'wmr-settings',
			array( $this, 'render' )
		);
	}

	/**
	 * Render the settings page template.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include WMR_PLUGIN_DIR . 'templates/admin-settings-page.php';
	}

	/**
	 * Enqueue admin scripts and styles — only on this plugin's settings page.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_scripts( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->hook_suffix ) {
			return;
		}

		// Color picker style (bundled with WordPress).
		wp_enqueue_style( 'wp-color-picker' );

		// Plugin admin stylesheet.
		wp_enqueue_style(
			'wmr-admin-settings',
			WMR_PLUGIN_URL . 'assets/css/admin-settings.css',
			array(),
			WMR_VERSION
		);

		// Media library scripts (only on this page — avoids conflicts elsewhere).
		wp_enqueue_media();

		// Plugin admin script with all bundled WP dependencies.
		wp_enqueue_script(
			'wmr-admin-settings',
			WMR_PLUGIN_URL . 'assets/js/admin-settings.js',
			array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ),
			WMR_VERSION,
			true
		);

		// Localize nonces and AJAX URL for admin-settings.js.
		wp_localize_script(
			'wmr-admin-settings',
			'wmrSettings',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'testEmailNonce' => wp_create_nonce( 'wmr_send_test_email' ),
			)
		);
	}
}
