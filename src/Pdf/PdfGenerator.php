<?php
/**
 * PDF generation service.
 *
 * Generates filled and blank A4 membership form PDFs using TCPDF.
 * All output goes to sys_get_temp_dir(); callers are responsible for unlink().
 *
 * @package WpMembershipRegistration\Pdf
 */

namespace WpMembershipRegistration\Pdf;

use WpMembershipRegistration\Util\FieldSchema;
use WpMembershipRegistration_Vendor_TCPDF as PdfLib;

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
		$pdf  = $this->build_pdf( false, $field_values );
		$data = $pdf->Output( '', 'S' );
		return $this->write_temp_file( $data );
	}

	/**
	 * Generate a blank PDF with AcroForm interactive text fields.
	 * Returns the temp file path. Caller is responsible for unlink().
	 *
	 * @return string Absolute path to the generated temp file.
	 */
	public function generate_blank(): string {
		$pdf  = $this->build_pdf( true, array() );
		$data = $pdf->Output( '', 'S' );
		return $this->write_temp_file( $data );
	}

	/**
	 * Build a TCPDF document with all page content.
	 *
	 * Shared by generate() and generate_blank(). Uses a two-column Cell() layout
	 * for field rows (~50mm label column, remaining width for values/widgets).
	 *
	 * @param bool                  $is_blank     True to render AcroForm TextField widgets instead of static values.
	 * @param array<string, string> $field_values Field label => value (empty array for blank PDFs).
	 * @return PdfLib Configured TCPDF instance (call Output() to get bytes).
	 */
	private function build_pdf( bool $is_blank, array $field_values ): PdfLib {
		$branding       = get_option( 'wmr_pdf_branding', array() );
		$club_name      = sanitize_text_field( $branding['club_name'] ?? '' );
		$raw_color      = sanitize_hex_color( $branding['accent_color'] ?? '#2271b1' );
		$accent_color   = $raw_color ? $raw_color : '#2271b1';
		$document_title = sanitize_text_field( $branding['document_title'] ?? '' );
		$form_notes     = wp_kses_post( $branding['form_notes'] ?? '' );
		$footer_text    = sanitize_text_field( $branding['footer_text'] ?? '' );
		$page2_content  = wp_kses_post( $branding['page2_content'] ?? '' );

		$pdf = new PdfLib( 'P', 'mm', 'A4', true, 'UTF-8', false );
		$pdf->SetCreator( 'WP Membership Registration' );
		$pdf->SetTitle( $document_title ? $document_title : $club_name );
		$pdf->setPrintHeader( false );
		$pdf->setPrintFooter( false );
		$pdf->SetMargins( 20, 20, 20 );
		$pdf->SetAutoPageBreak( true, 20 );
		$pdf->AddPage();

		// --- Club name heading ---
		$pdf->SetFont( 'dejavusans', 'B', 14 );
		$pdf->Cell( 0, 10, esc_html( $club_name ), 0, 1, 'C' );

		// --- Document title ---
		if ( $document_title ) {
			$pdf->SetFont( 'dejavusans', '', 11 );
			$pdf->Cell( 0, 8, esc_html( $document_title ), 0, 1, 'C' );
		}
		$pdf->Ln( 4 );

		// --- Dynamic field rows (two-column layout: ~50mm label, rest for value/widget) ---
		$schema_json = get_option( 'wmr_field_schema', '[]' );
		$fields      = FieldSchema::decode( is_string( $schema_json ) ? $schema_json : '[]' );
		$label_width = 50;
		foreach ( $fields as $field ) {
			$pdf->SetFont( 'dejavusans', 'B', 9 );
			$pdf->Cell( $label_width, 7, esc_html( $field['label'] ) . ':', 0, 0, 'R' );
			$pdf->SetFont( 'dejavusans', '', 10 );

			if ( $is_blank ) {
				// AcroForm text field for fillable blank PDF.
				$field_name = str_replace( ' ', '_', $field['label'] );
				$options    = ( 'date' === $field['type'] )
					? array(
						'v'  => 'TT.MM.JJJJ',
						'dv' => 'TT.MM.JJJJ',
					)
					: array();
				$pdf->TextField(
					$field_name,
					110,
					6,
					array(
						'lineWidth'   => 0.3,
						'borderStyle' => 'solid',
						'fillColor'   => array( 255, 255, 255 ),
					),
					$options
				);
				$pdf->Ln( 8 );
			} else {
				// Static value for filled (submitted) PDF.
				$value = esc_html( $field_values[ $field['label'] ] ?? '' );
				$pdf->Cell( 0, 7, $value, 'B', 1, 'L' );
			}
			$pdf->Ln( 2 );
		}

		// --- form_notes block (HTML from TinyMCE) ---
		if ( $form_notes ) {
			$pdf->Ln( 4 );
			$pdf->SetFont( 'dejavusans', '', 10 );
			$pdf->writeHTML( $form_notes, true, false, true, false, '' );
		}

		// --- Signature / date line (~8mm margin above) ---
		$pdf->Ln( 8 );
		$pdf->SetFont( 'dejavusans', '', 9 );
		$pdf->Cell( 80, 6, '', 'B', 0, 'L' );
		$pdf->Cell( 10, 6, '', 0, 0 );
		$pdf->Cell( 60, 6, '', 'B', 1, 'L' );
		$pdf->SetFont( 'dejavusans', '', 8 );
		$pdf->Cell( 80, 5, esc_html__( 'Ort, Datum', 'wp-membership-registration' ), 0, 0, 'L' );
		$pdf->Cell( 10, 5, '', 0, 0 );
		$pdf->Cell( 60, 5, esc_html__( 'Unterschrift', 'wp-membership-registration' ), 0, 1, 'L' );

		// --- Footer text on page 1 ---
		if ( $footer_text ) {
			$pdf->Ln( 6 );
			$pdf->SetFont( 'dejavusans', 'I', 8 );
			$pdf->MultiCell( 0, 5, esc_html( $footer_text ), 0, 'C' );
		}

		// --- Page 2 (page2_content HTML) ---
		if ( $page2_content ) {
			$pdf->AddPage();
			$pdf->SetFont( 'dejavusans', '', 10 );
			$pdf->writeHTML( $page2_content, true, false, true, false, '' );
		}

		return $pdf;
	}

	/**
	 * Write PDF bytes to a unique temp file.
	 *
	 * @param string $data Raw PDF bytes.
	 * @return string Absolute path to the written temp file.
	 */
	private function write_temp_file( string $data ): string {
		$path = sys_get_temp_dir() . '/wmr-' . wp_generate_uuid4() . '.pdf';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $path, $data );
		return $path;
	}
}
