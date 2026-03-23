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
	 * Handle the wmr_submit_form AJAX action.
	 *
	 * Accessible to unauthenticated users (nopriv hook). Security relies on
	 * check_ajax_referer() — never skip it.
	 *
	 * Flow: nonce → honeypot → sanitize fields → fire wmr_form_submitted action → JSON response.
	 *
	 * @return void
	 */
	public function handle_submit_form(): void {
		check_ajax_referer( 'wmr_submit_form', 'nonce' );

		// Honeypot: non-empty means bot. Return success silently to avoid retry loops.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already checked above.
		$honeypot = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : '';
		if ( '' !== $honeypot ) {
			wp_send_json_success();
			return;
		}

		// Sanitize submitted field values.
		// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce already checked above; each element is sanitized in the foreach loop below.
		$raw_fields = isset( $_POST['wmr_fields'] ) && is_array( $_POST['wmr_fields'] )
			? wp_unslash( (array) $_POST['wmr_fields'] )
			: array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$field_values = array();
		foreach ( $raw_fields as $label => $value ) {
			$clean_label                  = sanitize_text_field( (string) $label );
			$field_values[ $clean_label ] = sanitize_text_field( (string) $value );
		}

		// Determine member email: value of first field whose schema type is 'email'.
		$member_email = '';
		$schema_json  = get_option( 'wmr_field_schema', '[]' );
		$schema       = \WpMembershipRegistration\Util\FieldSchema::decode( is_string( $schema_json ) ? $schema_json : '[]' );
		foreach ( $schema as $field ) {
			if ( 'email' === $field['type'] && isset( $field_values[ $field['label'] ] ) ) {
				$member_email = sanitize_email( $field_values[ $field['label'] ] );
				break;
			}
		}

		// Fire the submission action. Mailer::handle_submission() is already hooked here.
		do_action( 'wmr_form_submitted', $field_values, $member_email );

		$form_settings     = get_option( 'wmr_form_settings', array() );
		$success_message   = sanitize_text_field( $form_settings['success_message'] ?? '' );
		$offer_download    = ! empty( $form_settings['offer_direct_download'] );
		$pdf_url           = null;
		$member_email_sent = '' !== $member_email;

		if ( $offer_download ) {
			$generator = new PdfGenerator();
			$path      = $generator->generate( $field_values );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
			$bytes = file_get_contents( $path );
			wp_delete_file( $path );
			$token = wp_generate_password( 32, false );
			set_transient( 'wmr_pdf_' . $token, base64_encode( $bytes ), 3600 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$pdf_url = add_query_arg(
				array(
					'action' => 'wmr_download_pdf_token',
					'token'  => $token,
				),
				admin_url( 'admin-ajax.php' )
			);
		}

		wp_send_json_success(
			array(
				'message'           => $success_message,
				'pdf_url'           => $pdf_url,
				'member_email_sent' => $member_email_sent,
			)
		);
	}

	/**
	 * Handle the wmr_download_pdf_token AJAX action.
	 *
	 * Streams a transient-stored, base64-encoded PDF to the browser,
	 * then deletes the transient. Accessible to all users (nopriv + priv).
	 *
	 * @return void
	 */
	public function handle_download_pdf_token(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- token is the one-time secret
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		if ( '' === $token ) {
			wp_die( esc_html__( 'Invalid download link.', 'wp-membership-registration' ), '', 400 );
		}
		$encoded = get_transient( 'wmr_pdf_' . $token );
		if ( false === $encoded ) {
			wp_die( esc_html__( 'This download link has expired or is invalid.', 'wp-membership-registration' ), '', 404 );
		}
		delete_transient( 'wmr_pdf_' . $token );
		$bytes = base64_decode( $encoded ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="membership-form.pdf"' );
		header( 'Content-Length: ' . strlen( $bytes ) );
		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		ob_clean();
		flush();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $bytes;
		exit;
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
