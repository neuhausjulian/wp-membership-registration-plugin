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
		// Frontend form submission — accessible to unauthenticated visitors.
		add_action( 'wp_ajax_nopriv_wmr_submit_form', array( $ajax_handlers, 'handle_submit_form' ) );
		add_action( 'wp_ajax_wmr_submit_form', array( $ajax_handlers, 'handle_submit_form' ) );
		// PDF token download — accessible to all users (token is the one-time secret).
		add_action( 'wp_ajax_nopriv_wmr_download_pdf_token', array( $ajax_handlers, 'handle_download_pdf_token' ) );
		add_action( 'wp_ajax_wmr_download_pdf_token', array( $ajax_handlers, 'handle_download_pdf_token' ) );
		$mailer = new Mailer();
		add_action( 'wmr_form_submitted', array( $mailer, 'handle_submission' ), 10, 2 );
		$shortcode = new MembershipFormShortcode();
		add_shortcode( 'membership_form', array( $shortcode, 'render' ) );
		// Enqueue frontend form assets only on pages that use the shortcode.
		add_action(
			'wp_enqueue_scripts',
			static function () {
				global $post;
				if ( ! is_singular() || ! is_a( $post, 'WP_Post' ) ) {
					return;
				}
				if ( ! has_shortcode( $post->post_content, 'membership_form' ) ) {
					return;
				}
				$form_settings   = get_option( 'wmr_form_settings', array() );
				$success_message = wp_kses_post( $form_settings['success_message'] ?? '' );
				if ( '' === $success_message ) {
					$success_message = __( 'Thank you for your registration!', 'wp-membership-registration' );
				}

				wp_enqueue_style(
					'wmr-form',
					WMR_PLUGIN_URL . 'assets/css/form.css',
					array(),
					WMR_VERSION
				);
				wp_enqueue_script(
					'wmr-form',
					WMR_PLUGIN_URL . 'assets/js/form.js',
					array(),
					WMR_VERSION,
					true  // Load in footer.
				);
				wp_localize_script(
					'wmr-form',
					'wmrForm',
					array(
						'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
						'submitNonce'    => wp_create_nonce( 'wmr_submit_form' ),
						'successMessage' => $success_message,
					)
				);
			}
		);
	}
}
