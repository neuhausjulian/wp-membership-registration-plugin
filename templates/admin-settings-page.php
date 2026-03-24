<?php
/**
 * Admin settings page template.
 *
 * Renders the three-tab settings page for the WP Membership Registration plugin.
 * Tabs: Form Fields | PDF Branding | Email Settings
 *
 * @package WpMembershipRegistration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$schema         = get_option( 'wmr_field_schema', '[]' );
$branding       = get_option( 'wmr_pdf_branding', array() );
$email_settings = get_option( 'wmr_email_settings', array() );
$fields         = \WpMembershipRegistration\Util\FieldSchema::decode( $schema );
$has_email      = ! empty( array_filter( $fields, fn( $f ) => ( $f['type'] ?? '' ) === 'email' ) );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Membership Registration Settings', 'wp-membership-registration' ); ?></h1>

	<nav class="nav-tab-wrapper wmr-tab-nav">
		<a href="#wmr-tab-fields" class="nav-tab nav-tab-active" data-tab="fields"><?php esc_html_e( 'Form Fields', 'wp-membership-registration' ); ?></a>
		<a href="#wmr-tab-branding" class="nav-tab" data-tab="branding"><?php esc_html_e( 'PDF Branding', 'wp-membership-registration' ); ?></a>
		<a href="#wmr-tab-email" class="nav-tab" data-tab="email"><?php esc_html_e( 'Email Settings', 'wp-membership-registration' ); ?></a>
		<a href="#wmr-tab-form-settings" class="nav-tab" data-tab="form-settings"><?php esc_html_e( 'Form Settings', 'wp-membership-registration' ); ?></a>
	</nav>

	<div id="wmr-tab-fields" class="wmr-tab-panel wmr-tab-panel--active">
		<form method="post" action="options.php">
			<?php settings_fields( 'wmr_form_fields_group' ); ?>

			<table class="wp-list-table widefat fixed striped wmr-fields-table">
				<thead>
					<tr>
						<th style="width:40px"></th>
						<th><?php esc_html_e( 'Label', 'wp-membership-registration' ); ?></th>
						<th style="width:160px"><?php esc_html_e( 'Field Type', 'wp-membership-registration' ); ?></th>
						<th style="width:80px"><?php esc_html_e( 'Required', 'wp-membership-registration' ); ?></th>
						<th style="width:60px"></th>
					</tr>
				</thead>
				<tbody id="wmr-fields-tbody">
					<!-- JS-rendered rows -->
				</tbody>
			</table>

			<script>
			var wmrExistingFields = <?php echo wp_json_encode( \WpMembershipRegistration\Util\FieldSchema::decode( $schema ) ); ?>;
			</script>

			<button type="button" id="wmr-add-field" class="button">
				<span class="dashicons dashicons-plus" aria-hidden="true"></span>
				<?php esc_html_e( 'Add Field', 'wp-membership-registration' ); ?>
			</button>

			<input type="hidden" id="wmr-field-schema-json" name="wmr_field_schema" value="">

			<?php submit_button(); ?>
		</form>
	</div>

	<div id="wmr-tab-branding" class="wmr-tab-panel" style="display:none">
		<form method="post" action="options.php">
			<?php settings_fields( 'wmr_pdf_branding_group' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="wmr-club-name"><?php esc_html_e( 'Club Name', 'wp-membership-registration' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="wmr-club-name"
								name="wmr_pdf_branding[club_name]"
								class="regular-text"
								value="<?php echo esc_attr( $branding['club_name'] ?? '' ); ?>"
							>
							<p class="description"><?php esc_html_e( 'Appears in the header of the generated PDF.', 'wp-membership-registration' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Club Logo', 'wp-membership-registration' ); ?></label>
						</th>
						<td>
							<div class="wmr-logo-field">
								<img
									id="wmr-logo-preview"
									src="<?php echo esc_url( $branding['logo_url'] ?? '' ); ?>"
									alt="<?php esc_attr_e( 'Club logo preview', 'wp-membership-registration' ); ?>"
									style="max-width:200px; max-height:80px; display:<?php echo ! empty( $branding['logo_url'] ) ? 'block' : 'none'; ?>; margin-bottom:8px;"
								>
								<br>
								<button type="button" id="wmr-select-logo" class="button"><?php esc_html_e( 'Select Logo', 'wp-membership-registration' ); ?></button>
								<button type="button" id="wmr-remove-logo" class="button" style="display:<?php echo ! empty( $branding['logo_url'] ) ? 'inline-block' : 'none'; ?>"><?php esc_html_e( 'Remove Logo', 'wp-membership-registration' ); ?></button>
								<input type="hidden" id="wmr-logo-url" name="wmr_pdf_branding[logo_url]" value="<?php echo esc_attr( $branding['logo_url'] ?? '' ); ?>">
							</div>
							<p class="description"><?php esc_html_e( 'Upload your club logo. Appears on the PDF membership form.', 'wp-membership-registration' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wmr-accent-color"><?php esc_html_e( 'Accent Color', 'wp-membership-registration' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="wmr-accent-color"
								name="wmr_pdf_branding[accent_color]"
								class="wmr-color-picker"
								value="<?php echo esc_attr( $branding['accent_color'] ?? '#2271b1' ); ?>"
								data-default-color="#2271b1"
							>
							<p class="description"><?php esc_html_e( 'Used as the highlight color in the PDF template.', 'wp-membership-registration' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wmr-document-title"><?php esc_html_e( 'Document Title', 'wp-membership-registration' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="wmr-document-title"
								name="wmr_pdf_branding[document_title]"
								class="regular-text"
								value="<?php echo esc_attr( $branding['document_title'] ?? '' ); ?>"
							>
							<p class="description"><?php esc_html_e( 'Shown in the PDF header next to the club name. Example: Aufnahmeantrag.', 'wp-membership-registration' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wmr-form-notes"><?php esc_html_e( 'Form information', 'wp-membership-registration' ); ?></label></th>
						<td>
							<?php
							wp_editor(
								wp_kses_post( $branding['form_notes'] ?? '' ),
								'wmr-form-notes',
								array(
									'textarea_name' => 'wmr_pdf_branding[form_notes]',
									'textarea_rows' => 5,
									'media_buttons' => false,
									'teeny'         => true,
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'Shown below form fields on the PDF and on the registration form. Accepts HTML (bold, paragraphs, links).', 'wp-membership-registration' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wmr-footer-text"><?php esc_html_e( 'Footer Text', 'wp-membership-registration' ); ?></label>
						</th>
						<td>
							<textarea
								id="wmr-footer-text"
								name="wmr_pdf_branding[footer_text]"
								class="large-text"
								rows="4"
							><?php echo wp_kses_post( $branding['footer_text'] ?? '' ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Board contact details and bank information shown at the bottom of page 1. May include HTML.', 'wp-membership-registration' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wmr-page2-content"><?php esc_html_e( 'Page 2 content', 'wp-membership-registration' ); ?></label></th>
						<td>
							<?php
							wp_editor(
								wp_kses_post( $branding['page2_content'] ?? '' ),
								'wmr-page2-content',
								array(
									'textarea_name' => 'wmr_pdf_branding[page2_content]',
									'textarea_rows' => 8,
									'media_buttons' => false,
									'teeny'         => true,
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'Legal/conditions page printed as page 2 of the PDF (AGBs, Datenschutzerklärung, Bankverbindung). Not shown on frontend form.', 'wp-membership-registration' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>

	<div id="wmr-tab-email" class="wmr-tab-panel" style="display:none">
		<?php if ( ! $has_email ) : ?>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( 'No email-type field is configured in the form — members will not receive a confirmation email. Add an email-type field on the Form Fields tab.', 'wp-membership-registration' ); ?></p>
		</div>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'wmr_email_settings_group' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="wmr-recipients"><?php esc_html_e( 'Notification Recipients', 'wp-membership-registration' ); ?></label>
						</th>
						<td>
							<textarea
								id="wmr-recipients"
								name="wmr_email_settings[recipients]"
								class="large-text"
								rows="4"
								placeholder="admin@example.com&#10;secretary@example.com"
								aria-describedby="wmr-recipients-description"
							><?php echo esc_textarea( $email_settings['recipients'] ?? '' ); ?></textarea>
							<p id="wmr-recipients-description" class="description"><?php esc_html_e( 'Enter one email address per line. All addresses receive a copy of each membership PDF.', 'wp-membership-registration' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"></th>
						<td>
							<button type="button" id="wmr-send-test-email" class="button">
								<span class="dashicons dashicons-email" aria-hidden="true" style="margin-top:3px;"></span>
								<?php esc_html_e( 'Send Test Email', 'wp-membership-registration' ); ?>
							</button>
							<span id="wmr-test-email-result" class="wmr-inline-notice" aria-live="polite"></span>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Email Previews', 'wp-membership-registration' ); ?></h2>
		<p class="description"><?php esc_html_e( 'These are the emails that will be sent on each registration. The content is fixed for v1 and cannot be edited here.', 'wp-membership-registration' ); ?></p>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Member email', 'wp-membership-registration' ); ?></th>
					<td>
						<p><strong><?php esc_html_e( 'Subject:', 'wp-membership-registration' ); ?></strong> <?php esc_html_e( 'Your registration form', 'wp-membership-registration' ); ?></p>
						<p><strong><?php esc_html_e( 'Body:', 'wp-membership-registration' ); ?></strong> <?php esc_html_e( 'Thank you for your registration. Please find your completed membership form attached.', 'wp-membership-registration' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Admin email', 'wp-membership-registration' ); ?></th>
					<td>
						<p><strong><?php esc_html_e( 'Subject:', 'wp-membership-registration' ); ?></strong> <?php esc_html_e( 'New membership registration received', 'wp-membership-registration' ); ?></p>
						<p><strong><?php esc_html_e( 'Body:', 'wp-membership-registration' ); ?></strong> <?php esc_html_e( 'A new membership registration was submitted. The completed form is attached.', 'wp-membership-registration' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<?php $form_settings = get_option( 'wmr_form_settings', array() ); ?>
	<div id="wmr-tab-form-settings" class="wmr-tab-panel" style="display:none">
		<form method="post" action="options.php">
			<?php settings_fields( 'wmr_form_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="wmr-consent-text"><?php esc_html_e( 'Consent checkbox text', 'wp-membership-registration' ); ?></label>
						</th>
						<td>
							<input type="text" id="wmr-consent-text" name="wmr_form_settings[consent_text]"
								value="<?php echo esc_attr( $form_settings['consent_text'] ?? '' ); ?>"
								class="large-text" />
							<p class="description"><?php esc_html_e( 'Label for the GDPR consent checkbox on the registration form. Required for submission.', 'wp-membership-registration' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wmr-success-message"><?php esc_html_e( 'Success message', 'wp-membership-registration' ); ?></label>
						</th>
						<td>
							<input type="text" id="wmr-success-message" name="wmr_form_settings[success_message]"
								value="<?php echo esc_attr( $form_settings['success_message'] ?? '' ); ?>"
								class="large-text" />
							<p class="description"><?php esc_html_e( 'Message shown in-place after a successful form submission.', 'wp-membership-registration' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wmr-offer-direct-download"><?php esc_html_e( 'Offer direct download link', 'wp-membership-registration' ); ?></label>
						</th>
						<td>
							<input
								type="checkbox"
								id="wmr-offer-direct-download"
								name="wmr_form_settings[offer_direct_download]"
								value="1"
								<?php checked( ! empty( $form_settings['offer_direct_download'] ) ); ?>
							/>
							<p class="description"><?php esc_html_e( 'When enabled, the success screen shows a link for the member to download their pre-filled PDF immediately.', 'wp-membership-registration' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wmr-fallback-language"><?php esc_html_e( 'Fallback Language', 'wp-membership-registration' ); ?></label>
						</th>
						<td>
							<select id="wmr-fallback-language" name="wmr_form_settings[fallback_language]">
								<option value="auto" <?php selected( $form_settings['fallback_language'] ?? 'auto', 'auto' ); ?>>
									<?php esc_html_e( 'Auto (follow site language)', 'wp-membership-registration' ); ?>
								</option>
								<option value="de" <?php selected( $form_settings['fallback_language'] ?? 'auto', 'de' ); ?>>
									<?php esc_html_e( 'German (de)', 'wp-membership-registration' ); ?>
								</option>
								<option value="en" <?php selected( $form_settings['fallback_language'] ?? 'auto', 'en' ); ?>>
									<?php esc_html_e( 'English (en)', 'wp-membership-registration' ); ?>
								</option>
							</select>
							<p class="description"><?php esc_html_e( 'Override the language used for plugin strings. Defaults to the WordPress site language.', 'wp-membership-registration' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
</div>
