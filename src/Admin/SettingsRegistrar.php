<?php
/**
 * Settings API registration for all three option groups.
 *
 * @package WpMembershipRegistration
 */

namespace WpMembershipRegistration\Admin;

use WpMembershipRegistration\Util\FieldSchema;

/**
 * Registers settings, sections, and sanitize callbacks via admin_init.
 */
class SettingsRegistrar {

	/**
	 * Allowed field types for the field schema editor.
	 *
	 * @var string[]
	 */
	private const ALLOWED_FIELD_TYPES = array( 'text', 'email', 'date', 'tel', 'textarea', 'select' );

	/**
	 * Register all settings, sections, and fields.
	 *
	 * @return void
	 */
	public function register(): void {
		// Form Fields tab — stores JSON-encoded field schema.
		register_setting(
			'wmr_form_fields_group',
			'wmr_field_schema',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_field_schema' ),
				'default'           => '[]',
			)
		);

		// PDF Branding tab.
		register_setting(
			'wmr_pdf_branding_group',
			'wmr_pdf_branding',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_pdf_branding' ),
				'default'           => array(
					'club_name'      => '',
					'logo_url'       => '',
					'accent_color'   => '#2271b1',
					'document_title' => '',
					'gdpr_text'      => '',
					'footer_text'    => '',
					'page2_content'  => '',
				),
			)
		);

		// Email Settings tab.
		register_setting(
			'wmr_email_settings_group',
			'wmr_email_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_email_settings' ),
				'default'           => array(
					'recipients' => '',
				),
			)
		);
	}

	/**
	 * Sanitize the field schema JSON string.
	 *
	 * Decodes the JSON (handling WP magic quotes), validates each row,
	 * and re-encodes as clean JSON. Returns '[]' on any decode failure.
	 *
	 * @param string $raw Raw POST value (may be magic-quoted by WordPress).
	 * @return string JSON-encoded array of sanitized field objects.
	 */
	public function sanitize_field_schema( string $raw ): string {
		// wp_unslash() is required before json_decode() because WordPress
		// applies magic quotes to all $_POST data (Pitfall 2 in RESEARCH.md).
		$decoded = json_decode( wp_unslash( $raw ), true );
		if ( ! is_array( $decoded ) ) {
			return '[]';
		}
		$clean = array();
		foreach ( $decoded as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$type    = $row['type'] ?? 'text';
			$clean[] = array(
				'label'    => sanitize_text_field( $row['label'] ?? '' ),
				'type'     => in_array( $type, self::ALLOWED_FIELD_TYPES, true ) ? $type : 'text',
				'required' => ! empty( $row['required'] ),
			);
		}
		return FieldSchema::encode( $clean );
	}

	/**
	 * Sanitize PDF branding settings array.
	 *
	 * @param mixed $input Raw input from form submission.
	 * @return array{club_name: string, logo_url: string, accent_color: string, document_title: string, gdpr_text: string, footer_text: string, page2_content: string}
	 */
	public function sanitize_pdf_branding( mixed $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		$accent_color = sanitize_hex_color( $input['accent_color'] ?? '' );
		return array(
			'club_name'      => sanitize_text_field( $input['club_name'] ?? '' ),
			'logo_url'       => esc_url_raw( $input['logo_url'] ?? '' ),
			'accent_color'   => $accent_color ? $accent_color : '#2271b1',
			'document_title' => sanitize_text_field( $input['document_title'] ?? '' ),
			'gdpr_text'      => wp_kses_post( $input['gdpr_text'] ?? '' ),
			'footer_text'    => wp_kses_post( $input['footer_text'] ?? '' ),
			'page2_content'  => wp_kses_post( $input['page2_content'] ?? '' ),
		);
	}

	/**
	 * Sanitize email settings array.
	 *
	 * Recipients are stored as a newline-separated string. Each line is
	 * sanitized individually with sanitize_email(). Empty lines are removed.
	 *
	 * @param mixed $input Raw input from form submission.
	 * @return array{recipients: string}
	 */
	public function sanitize_email_settings( mixed $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		$raw_recipients = $input['recipients'] ?? '';
		$lines          = explode( "\n", sanitize_textarea_field( wp_unslash( $raw_recipients ) ) );
		$clean_lines    = array_filter( array_map( 'sanitize_email', $lines ) );
		return array(
			'recipients' => implode( "\n", $clean_lines ),
		);
	}
}
