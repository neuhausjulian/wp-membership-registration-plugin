<?php
/**
 * Unit tests for FormRenderer.
 *
 * @package WpMembershipRegistration\Tests\Unit\Frontend
 */

namespace WpMembershipRegistration\Tests\Unit\Frontend;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpMembershipRegistration\Frontend\FormRenderer;

/**
 * FormRenderer unit tests — FORM-01, FORM-02, FORM-03, FORM-04.
 */
class FormRendererTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\stubs(
			[
				'get_option'      => function ( $option, $default = null ) {
					if ( 'wmr_field_schema' === $option ) {
						return '[{"label":"Vorname","type":"text","required":true},{"label":"E-Mail","type":"email","required":true}]';
					}
					if ( 'wmr_pdf_branding' === $option ) {
						return [ 'form_notes' => '<p>Test notes</p>' ];
					}
					if ( 'wmr_form_settings' === $option ) {
						return [ 'consent_text' => 'I agree.', 'success_message' => 'Thank you!' ];
					}
					return $default;
				},
				'wp_kses_post'    => fn( $v ) => (string) $v,
				'esc_html'        => fn( $v ) => htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ),
				'esc_attr'        => fn( $v ) => htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ),
				'esc_html__'      => fn( $text, $domain = 'default' ) => $text,
				'esc_html_e'      => function ( $text, $domain = 'default' ) { echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); },
				'wp_nonce_field'  => fn( $action = '', $name = '_wpnonce', $referer = true, $echo = true ) => '<input type="hidden" name="_wpnonce" value="test_nonce" />',
			]
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_render_returns_form_container(): void {
		$renderer = new FormRenderer();
		$html     = $renderer->render();
		$this->assertStringContainsString( 'wmr-form-container', $html );
		$this->assertStringContainsString( '<form', $html );
	}

	public function test_render_includes_honeypot(): void {
		$renderer = new FormRenderer();
		$html     = $renderer->render();
		$this->assertStringContainsString( 'name="website"', $html );
	}

	public function test_render_marks_required_fields(): void {
		$renderer = new FormRenderer();
		$html     = $renderer->render();
		$this->assertStringContainsString( 'data-required="1"', $html );
	}

	public function test_render_marks_email_fields(): void {
		$renderer = new FormRenderer();
		$html     = $renderer->render();
		$this->assertStringContainsString( 'data-email="1"', $html );
	}

	public function test_render_includes_error_spans(): void {
		$renderer = new FormRenderer();
		$html     = $renderer->render();
		$this->assertStringContainsString( 'wmr-field-error', $html );
	}

	public function test_render_includes_consent_checkbox_when_consent_text_set(): void {
		$renderer = new FormRenderer();
		$html     = $renderer->render();
		$this->assertStringContainsString( 'wmr-consent-checkbox', $html );
		$this->assertStringContainsString( 'I agree.', $html );
	}
}
