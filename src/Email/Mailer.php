<?php
/**
 * Mailer — sends membership registration emails with PDF attachment.
 *
 * Listens on the 'wmr_form_submitted' action hook.
 * Phase 4 fires this hook from the AJAX form submission handler.
 *
 * @package WpMembershipRegistration\Email
 */

namespace WpMembershipRegistration\Email;

use WpMembershipRegistration\Pdf\PdfGenerator;

/**
 * Sends membership registration emails with PDF attachment.
 *
 * Handles the wmr_form_submitted action hook. Generates a PDF via
 * PdfGenerator, sends a confirmation email to the member (when a valid
 * member address is present) and a notification email to configured admin
 * recipients, then unconditionally deletes the temp PDF file.
 */
class Mailer {

	/**
	 * Optional PdfGenerator instance (injected for testing).
	 *
	 * @var PdfGenerator|null
	 */
	private ?PdfGenerator $generator;

	/**
	 * Constructor.
	 *
	 * @param PdfGenerator|null $generator Optional PdfGenerator instance (injected for testing).
	 */
	public function __construct( ?PdfGenerator $generator = null ) {
		$this->generator = $generator;
	}

	/**
	 * Handle the wmr_form_submitted action hook.
	 *
	 * Generates a PDF from the submitted field values, sends confirmation
	 * email to the member (if a valid address is provided) and a notification
	 * email to admin recipients, then deletes the temp PDF file.
	 *
	 * @param array<string, string> $field_values Submitted field label => value pairs.
	 * @param string                $member_email Resolved member email address.
	 * @return void
	 */
	public function handle_submission( array $field_values, string $member_email ): void {
		$generator = $this->generator ?? new PdfGenerator();
		$pdf_path  = $generator->generate( $field_values );

		try {
			$this->send_member_email( $member_email, $pdf_path );
			$this->send_admin_email( $pdf_path );
		} finally {
			wp_delete_file( $pdf_path );
		}
	}

	/**
	 * Send a confirmation email to the member.
	 *
	 * Skipped if $member_email is not a valid email address.
	 *
	 * @param string $member_email Member email address.
	 * @param string $pdf_path     Absolute path to the temp PDF file.
	 * @return void
	 */
	private function send_member_email( string $member_email, string $pdf_path ): void {
		if ( ! filter_var( $member_email, FILTER_VALIDATE_EMAIL ) ) {
			return;
		}

		$sent = wp_mail(
			$member_email,
			__( 'Your registration form', 'wp-membership-registration' ),
			__( 'Thank you for your registration. Please find your completed membership form attached.', 'wp-membership-registration' ),
			array(),
			array( $pdf_path )
		);

		if ( false === $sent ) {
			error_log( 'wmr: wp_mail() failed sending member confirmation to ' . $member_email ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Send a notification email with the PDF to all configured admin recipients.
	 *
	 * Skipped if no recipients are configured in wmr_email_settings.
	 *
	 * @param string $pdf_path Absolute path to the temp PDF file.
	 * @return void
	 */
	private function send_admin_email( string $pdf_path ): void {
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
			return;
		}

		$to = 1 === count( $recipients ) ? $recipients[0] : $recipients;

		$sent = wp_mail(
			$to,
			__( 'New membership registration received', 'wp-membership-registration' ),
			__( 'A new membership registration was submitted. The completed form is attached.', 'wp-membership-registration' ),
			array(),
			array( $pdf_path )
		);

		if ( false === $sent ) {
			error_log( 'wmr: wp_mail() failed sending admin notification to ' . implode( ', ', (array) $to ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
