<?php
/**
 * AJAX handlers for the plugin settings page.
 *
 * @package WpMembershipRegistration
 */

namespace WpMembershipRegistration\Admin;

/**
 * Handles wp_ajax_ actions for the plugin settings page.
 */
class AjaxHandlers {

	/**
	 * Handle the wmr_send_test_email AJAX action.
	 *
	 * Hooked on wp_ajax_wmr_send_test_email (admin-only; no wp_ajax_nopriv_ variant).
	 *
	 * @return void
	 */
	public function handle_send_test_email(): void {
		check_ajax_referer( 'wmr_send_test_email', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Unauthorized.', 'wp-membership-registration' ) ),
				403
			);
		}

		$settings   = get_option( 'wmr_email_settings', array() );
		$raw        = $settings['recipients'] ?? '';
		$recipients = array_values(
			array_filter(
				array_map(
					'sanitize_email',
					explode( "\n", $raw )
				)
			)
		);

		if ( empty( $recipients ) ) {
			wp_send_json_error(
				array(
					'message' => __(
						'No recipient address configured. Add at least one email address above and save before testing.',
						'wp-membership-registration'
					),
				)
			);
		}

		$sent = wp_mail(
			$recipients,
			__( '[Test] Membership Registration email', 'wp-membership-registration' ),
			__( 'This is a test email from the WP Membership Registration plugin.', 'wp-membership-registration' )
		);

		if ( $sent ) {
			wp_send_json_success(
				array( 'message' => __( 'Test email sent.', 'wp-membership-registration' ) )
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __(
						'Email delivery failed. Check your SMTP settings (see Mailpit on port 8025 in local dev).',
						'wp-membership-registration'
					),
				)
			);
		}
	}
}
