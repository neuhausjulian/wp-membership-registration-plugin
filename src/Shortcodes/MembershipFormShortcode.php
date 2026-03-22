<?php
/**
 * Shortcode handler for [membership_form].
 *
 * Phase 2 implements only the download="blank" case, which renders a link
 * to download the blank membership form PDF. Full form rendering is Phase 4.
 *
 * @package WpMembershipRegistration\Shortcodes
 */

namespace WpMembershipRegistration\Shortcodes;

use WpMembershipRegistration\Frontend\FormRenderer;

/**
 * Registers and handles the [membership_form] shortcode.
 */
class MembershipFormShortcode {

	/**
	 * Render the shortcode output.
	 *
	 * Supported attributes:
	 *   download="blank" — Outputs a download link for the blank membership form PDF.
	 *
	 * All other attribute combinations are stubs for Phase 4 (full form rendering).
	 *
	 * @param array<string, string>|string $atts Raw shortcode attributes.
	 * @return string HTML output.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array( 'download' => '' ),
			$atts,
			'membership_form'
		);

		if ( 'blank' === $atts['download'] ) {
			return $this->render_blank_download_link();
		}

		$renderer = new FormRenderer();
		return $renderer->render();
	}

	/**
	 * Render a download link for the blank membership form PDF.
	 *
	 * The link points to the wmr_download_blank_pdf AJAX action, which is
	 * accessible without login (wp_ajax_nopriv hook registered in Plugin.php).
	 *
	 * @return string HTML anchor element.
	 */
	private function render_blank_download_link(): string {
		$url = admin_url( 'admin-ajax.php?action=wmr_download_blank_pdf' );
		return sprintf(
			'<a href="%s" download>%s</a>',
			esc_url( $url ),
			esc_html__( 'Mitgliedsantrag herunterladen (leer)', 'wp-membership-registration' )
		);
	}
}
