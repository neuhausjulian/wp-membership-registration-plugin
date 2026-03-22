<?php
/**
 * Main Plugin class.
 *
 * Registers all WordPress hooks for the plugin.
 *
 * @package WpMembershipRegistration
 */

namespace WpMembershipRegistration;

use WpMembershipRegistration\Admin\AjaxHandlers;
use WpMembershipRegistration\Admin\SettingsPage;
use WpMembershipRegistration\Admin\SettingsRegistrar;
use WpMembershipRegistration\Email\Mailer;
use WpMembershipRegistration\Shortcodes\MembershipFormShortcode;

/**
 * Main Plugin orchestrator.
 */
class Plugin {

	/**
	 * Register all hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		$settings_page      = new SettingsPage();
		$settings_registrar = new SettingsRegistrar();
		$ajax_handlers      = new AjaxHandlers();

		add_action( 'admin_menu', array( $settings_page, 'register' ) );
		add_action( 'admin_init', array( $settings_registrar, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $settings_page, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_wmr_send_test_email', array( $ajax_handlers, 'handle_send_test_email' ) );
		add_action( 'wp_ajax_wmr_download_blank_pdf', array( $ajax_handlers, 'handle_download_blank_pdf' ) );
		add_action( 'wp_ajax_nopriv_wmr_download_blank_pdf', array( $ajax_handlers, 'handle_download_blank_pdf' ) );
		add_action( 'wp_ajax_wmr_generate_pdf_stub', array( $ajax_handlers, 'handle_generate_pdf_stub' ) );
		$mailer = new Mailer();
		add_action( 'wmr_form_submitted', array( $mailer, 'handle_submission' ), 10, 2 );
		$shortcode = new MembershipFormShortcode();
		add_shortcode( 'membership_form', array( $shortcode, 'render' ) );
	}
}
