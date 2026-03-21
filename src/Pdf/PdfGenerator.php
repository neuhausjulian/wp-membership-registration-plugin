<?php
/**
 * PDF generation service.
 *
 * Generates filled and blank A4 membership form PDFs using DOMPDF.
 * All output goes to sys_get_temp_dir(); callers are responsible for unlink().
 *
 * @package WpMembershipRegistration\Pdf
 */

namespace WpMembershipRegistration\Pdf;

use WpMembershipRegistration\Util\FieldSchema;
use WpMembershipRegistration\Vendor\Dompdf\Dompdf;
use WpMembershipRegistration\Vendor\Dompdf\Options;

/**
 * Generates pre-filled and blank A4 PDF membership forms.
 */
class PdfGenerator {

	/**
	 * Generate a filled PDF from submitted field values.
	 * Returns the temp file path. Caller is responsible for unlink().
	 *
	 * @param array<string, string> $field_values Submitted field label => value pairs.
	 * @return string Absolute path to the generated temp file.
	 */
	public function generate( array $field_values ): string {
		$html     = $this->render_template( $field_values, false );
		$pdf_data = $this->render_pdf( $html );
		return $this->write_temp_file( $pdf_data );
	}

	/**
	 * Generate a blank PDF with underline placeholders for empty fields.
	 * Returns the temp file path. Caller is responsible for unlink().
	 *
	 * @return string Absolute path to the generated temp file.
	 */
	public function generate_blank(): string {
		$html     = $this->render_template( array(), true );
		$pdf_data = $this->render_pdf( $html );
		return $this->write_temp_file( $pdf_data );
	}

	/**
	 * Render the HTML template for DOMPDF.
	 *
	 * @param array<string, string> $field_values Field label => value (empty array for blank).
	 * @param bool                  $is_blank     True to render underlines instead of values.
	 * @return string Rendered HTML string.
	 */
	private function render_template( array $field_values, bool $is_blank ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$branding       = get_option( 'wmr_pdf_branding', array() );
		$club_name      = $branding['club_name'] ?? '';
		$accent_color   = $branding['accent_color'] ?? '#2271b1';
		$document_title = $branding['document_title'] ?? '';
		$gdpr_text      = $branding['gdpr_text'] ?? '';
		$footer_text    = $branding['footer_text'] ?? '';
		$page2_content  = $branding['page2_content'] ?? '';
		$logo_data_uri  = $this->get_logo_data_uri( $branding['logo_url'] ?? '' );
		$fields         = FieldSchema::decode( get_option( 'wmr_field_schema', '[]' ) );

		ob_start();
		include WMR_PLUGIN_DIR . 'templates/pdf/membership-form.php';
		return (string) ob_get_clean();
	}

	/**
	 * Render HTML to PDF bytes using DOMPDF.
	 *
	 * SECURITY: isRemoteEnabled and isPhpEnabled are always false.
	 *
	 * @param string $html Valid HTML string.
	 * @return string Raw PDF bytes.
	 */
	private function render_pdf( string $html ): string {
		$options = new Options();
		$options->set( 'isRemoteEnabled', false );     // SECURITY: must stay false — RCE risk.
		$options->set( 'isPhpEnabled', false );        // SECURITY: must stay false.
		$options->set( 'isHtml5ParserEnabled', true );
		$options->set( 'defaultPaperSize', 'a4' );
		$options->set( 'defaultPaperOrientation', 'portrait' );

		$dompdf = new Dompdf( $options );
		$dompdf->loadHtml( $html, 'UTF-8' );
		$dompdf->setPaper( 'a4', 'portrait' );
		$dompdf->render();
		return (string) $dompdf->output();
	}

	/**
	 * Write PDF bytes to a unique temp file.
	 *
	 * @param string $pdf_data Raw PDF bytes.
	 * @return string Absolute path to the written temp file.
	 */
	private function write_temp_file( string $pdf_data ): string {
		$path = sys_get_temp_dir() . '/wmr-' . wp_generate_uuid4() . '.pdf';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $path, $pdf_data );
		return $path;
	}

	/**
	 * Convert a logo URL to a base64 data-URI for DOMPDF rendering.
	 *
	 * DOMPDF cannot fetch URLs (isRemoteEnabled=false). The logo must be
	 * embedded as a data-URI using the local filesystem path.
	 *
	 * @param string $logo_url The logo URL stored in wmr_pdf_branding.
	 * @return string Base64 data-URI, or empty string if logo not set/found.
	 */
	private function get_logo_data_uri( string $logo_url ): string {
		if ( empty( $logo_url ) ) {
			return '';
		}
		$upload_dir = wp_upload_dir();
		$logo_path  = str_replace(
			$upload_dir['baseurl'],
			$upload_dir['basedir'],
			$logo_url
		);
		if ( ! file_exists( $logo_path ) ) {
			return '';
		}
		$mime = mime_content_type( $logo_path );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$data = base64_encode( file_get_contents( $logo_path ) );
		return 'data:' . $mime . ';base64,' . $data;
	}
}
