<?php
/**
 * AJAX handlers for the plugin settings page.
 *
 * @package WpMembershipRegistration
 */

namespace WpMembershipRegistration\Admin;

use WpMembershipRegistration\Pdf\PdfGenerator;

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

	/**
	 * Smoke-test AJAX stub: accepts POST field_values and calls PdfGenerator::generate().
	 *
	 * PDF-01 contract proof — verifies PdfGenerator::generate(array $field_values) works
	 * end-to-end from an AJAX context. This stub is replaced by the real form submission
	 * handler in Phase 4.
	 *
	 * Not registered as a public hook — only callable via wp_ajax_ (logged-in) during dev/test.
	 *
	 * @return void
	 */
	public function handle_generate_pdf_stub(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- smoke-test stub, nonce added in Phase 4.
		$field_values = isset( $_POST['field_values'] ) && is_array( $_POST['field_values'] )
			? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['field_values'] ) )
			: array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$generator = new PdfGenerator();

		try {
			$path = $generator->generate( $field_values );
			wp_delete_file( $path );
			wp_send_json_success( array( 'message' => 'PDF generated successfully.' ) );
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Handle the wmr_download_blank_pdf AJAX action.
	 *
	 * Streams a blank (empty-fields) membership form PDF to the browser,
	 * then deletes the temp file. Accessible without login (nopriv hook).
	 *
	 * @return void
	 */
	public function handle_download_blank_pdf(): void {
		$generator = new PdfGenerator();
		$path      = $generator->generate_blank();

		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="membership-form.pdf"' );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'Pragma: private' );

		ob_clean();
		flush();

		try {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
			readfile( $path );
		} finally {
			// Guarantee cleanup even if readfile() throws.
			@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.unlink_unlink
		}

		exit;
	}
}
