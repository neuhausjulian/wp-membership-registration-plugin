<?php
/**
 * PDF template for the membership form.
 *
 * Included by PdfGenerator::render_template() via ob_start() + include.
 * All variables are set by PdfGenerator before inclusion — no get_option() calls here.
 *
 * Available variables (set by PdfGenerator::render_template()):
 *   string $club_name, $accent_color, $document_title
 *   string $gdpr_text, $footer_text, $page2_content (may contain HTML)
 *   string $logo_data_uri (base64 data-URI or empty)
 *   array  $fields         [{label, type, required}, ...]
 *   array  $field_values   [label => value, ...]
 *   bool   $is_blank
 *
 * @package WpMembershipRegistration
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WMR_PLUGIN_DIR' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 20mm 20mm 30mm 20mm; size: A4 portrait; }
body        { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1a1a1a; }
.header     { width: 100%; margin-bottom: 4mm; overflow: hidden; }
.header-logo { float: left; max-height: 18mm; max-width: 40mm; }
.header-text { margin-left: 45mm; }
.header-text-full { margin-left: 0; }
.separator  { height: 1mm; background-color: <?php echo esc_attr( $accent_color ); ?>; clear: both; margin: 2mm 0 4mm; }
.field-row  { margin: 2mm 0; line-height: 1.6; }
.gdpr       { margin: 6mm 0 4mm; font-size: 8.5pt; color: #333; }
.sig-row    { margin: 3mm 0; }
.footer     { position: fixed; bottom: 0; left: 0; right: 0; font-size: 7.5pt; color: #555; border-top: 0.5pt solid #ccc; padding-top: 2mm; }
.page-break { page-break-before: always; }
</style>
</head>
<body>

<!-- Header -->
<div class="header">
	<?php if ( $logo_data_uri ) : ?>
		<img class="header-logo" src="<?php echo esc_attr( $logo_data_uri ); ?>" alt="">
		<div class="header-text">
	<?php else : ?>
		<div class="header-text-full">
	<?php endif; ?>
		<strong><?php echo esc_html( $club_name ); ?></strong><br>
		<?php echo esc_html( $document_title ); ?>
	</div>
</div>
<div class="separator"></div>

<!-- Dynamic Fields -->
<?php foreach ( $fields as $field ) : ?>
	<div class="field-row">
		<?php echo esc_html( $field['label'] ); ?>:&nbsp;&nbsp;
		<?php
		if ( $is_blank ) :
			?>
			___________________________
			<?php
else :
	?>
			<?php echo esc_html( $field_values[ $field['label'] ] ?? '' ); ?><?php endif; ?>
	</div>
<?php endforeach; ?>

<!-- GDPR / Consent Paragraph -->
<?php if ( $gdpr_text ) : ?>
	<div class="gdpr"><?php echo wp_kses_post( $gdpr_text ); ?></div>
<?php endif; ?>

<!-- Place + Date -->
<div class="sig-row">Ort, Datum:&nbsp;&nbsp;___________________________</div>

<!-- Signature -->
<div class="sig-row">Unterschrift:&nbsp;&nbsp;___________________________</div>

<!-- Footer (fixed at bottom of page 1) -->
<?php if ( $footer_text ) : ?>
	<div class="footer"><?php echo wp_kses_post( $footer_text ); ?></div>
<?php endif; ?>

<!-- Page 2 (optional static content) -->
<?php if ( $page2_content ) : ?>
	<div class="page-break"></div>
	<?php echo wp_kses_post( $page2_content ); ?>
<?php endif; ?>

</body>
</html>
