<?php
/**
 * Renders the frontend membership registration form HTML.
 *
 * @package WpMembershipRegistration\Frontend
 */

namespace WpMembershipRegistration\Frontend;

use WpMembershipRegistration\Util\FieldSchema;

/**
 * Generates the HTML form markup for the [membership_form] shortcode.
 */
class FormRenderer {

	/**
	 * Render the complete registration form HTML.
	 *
	 * Field output order (per CONTEXT.md):
	 *   1. Dynamic fields (from FieldSchema::decode())
	 *   2. form_notes block (from wmr_pdf_branding['form_notes'])
	 *   3. Consent checkbox (always rendered; uses wmr_form_settings['consent_text'] or default label)
	 *   4. Honeypot (hidden name="website")
	 *   5. Nonce field
	 *   6. Submit button
	 *
	 * @return string Complete HTML form element.
	 */
	public function render(): string {
		$schema_json   = get_option( 'wmr_field_schema', '[]' );
		$fields        = FieldSchema::decode( is_string( $schema_json ) ? $schema_json : '[]' );
		$branding      = get_option( 'wmr_pdf_branding', array() );
		$form_settings = get_option( 'wmr_form_settings', array() );
		$form_notes    = $branding['form_notes'] ?? '';
		$consent_text  = $form_settings['consent_text'] ?? '';

		ob_start();
		?>
		<div class="wmr-form-container">
			<form id="wmr-registration-form" class="wmr-form" method="post" novalidate>

				<?php foreach ( $fields as $index => $field ) : ?>
					<?php $field_id = 'wmr-field-' . $index; ?>
					<div class="wmr-field-row">
						<label for="<?php echo esc_attr( $field_id ); ?>" class="wmr-field-label">
							<?php echo esc_html( $field['label'] ); ?>
							<?php if ( $field['required'] ) : ?>
								<span class="wmr-required" aria-hidden="true">*</span>
							<?php endif; ?>
						</label>
						<?php if ( 'textarea' === $field['type'] ) : ?>
							<textarea
								id="<?php echo esc_attr( $field_id ); ?>"
								name="wmr_fields[<?php echo esc_attr( $field['label'] ); ?>]"
								class="wmr-field-input"
								<?php echo $field['required'] ? 'data-required="1"' : ''; ?>
								rows="4"
							></textarea>
						<?php elseif ( 'select' === $field['type'] ) : ?>
							<select
								id="<?php echo esc_attr( $field_id ); ?>"
								name="wmr_fields[<?php echo esc_attr( $field['label'] ); ?>]"
								class="wmr-field-input"
								<?php echo $field['required'] ? 'data-required="1"' : ''; ?>
							>
								<option value=""></option>
							</select>
						<?php else : ?>
							<input
								type="<?php echo esc_attr( $field['type'] ); ?>"
								id="<?php echo esc_attr( $field_id ); ?>"
								name="wmr_fields[<?php echo esc_attr( $field['label'] ); ?>]"
								class="wmr-field-input"
								<?php echo $field['required'] ? 'data-required="1"' : ''; ?>
								<?php echo 'email' === $field['type'] ? 'data-email="1"' : ''; ?>
							/>
						<?php endif; ?>
						<span class="wmr-field-error" aria-live="polite"></span>
					</div>
				<?php endforeach; ?>

				<?php if ( $form_notes ) : ?>
					<div class="wmr-form-notes">
						<?php echo wp_kses_post( $form_notes ); ?>
					</div>
				<?php endif; ?>

				<?php
				$consent_label = $consent_text
					? $consent_text
					: __( 'Ich stimme der Verarbeitung meiner personenbezogenen Daten zu.', 'wp-membership-registration' );
				?>
				<div class="wmr-field-row wmr-consent-row">
					<label class="wmr-consent-label">
						<input
							type="checkbox"
							id="wmr-consent"
							name="wmr_consent"
							value="1"
							class="wmr-consent-checkbox"
							data-required="1"
							data-consent="1"
						/>
						<?php echo esc_html( $consent_label ); ?>
					</label>
					<span class="wmr-field-error wmr-consent-error" aria-live="polite"></span>
				</div>

				<?php /* Honeypot — bots fill this; real users do not see it. */ ?>
				<div class="wmr-honeypot" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;">
					<label for="wmr-website"><?php esc_html_e( 'Leave this field empty', 'wp-membership-registration' ); ?></label>
					<input type="text" id="wmr-website" name="website" tabindex="-1" autocomplete="off" value="" />
				</div>

				<?php wp_nonce_field( 'wmr_submit_form', 'nonce' ); ?>

				<div class="wmr-submit-row">
					<button type="submit" class="wmr-submit-button">
						<?php esc_html_e( 'Submit registration', 'wp-membership-registration' ); ?>
					</button>
				</div>

			</form>
		</div>
		<?php
		return ob_get_clean();
	}
}
