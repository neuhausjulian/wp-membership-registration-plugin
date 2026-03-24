<?php
/**
 * Unit tests for SettingsRegistrar — fallback_language sanitization.
 *
 * Covers I18N-07: admin-configurable fallback language stored in wmr_form_settings.
 *
 * @package WpMembershipRegistration\Tests\Unit\Admin
 */

namespace WpMembershipRegistration\Tests\Unit\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpMembershipRegistration\Admin\SettingsRegistrar;

/**
 * SettingsRegistrar unit tests — fallback_language sanitization.
 */
class SettingsRegistrarTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Stub sanitize_text_field to return its input unchanged.
		Functions\stubs(
			[
				'sanitize_text_field' => fn( $v ) => (string) $v,
			]
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Valid 'de' value for fallback_language is preserved.
	 */
	public function test_sanitize_form_settings_includes_fallback_language(): void {
		$registrar = new SettingsRegistrar();
		$input     = [
			'consent_text'          => '',
			'success_message'       => '',
			'offer_direct_download' => false,
			'fallback_language'     => 'de',
		];

		$result = $registrar->sanitize_form_settings( $input );

		$this->assertArrayHasKey( 'fallback_language', $result );
		$this->assertSame( 'de', $result['fallback_language'] );
	}

	/**
	 * Invalid language code 'fr' is rejected and falls back to 'auto'.
	 */
	public function test_sanitize_form_settings_rejects_invalid_fallback_language(): void {
		$registrar = new SettingsRegistrar();
		$input     = [
			'consent_text'          => '',
			'success_message'       => '',
			'offer_direct_download' => false,
			'fallback_language'     => 'fr',
		];

		$result = $registrar->sanitize_form_settings( $input );

		$this->assertArrayHasKey( 'fallback_language', $result );
		$this->assertSame( 'auto', $result['fallback_language'] );
	}

	/**
	 * Missing fallback_language key defaults to 'auto'.
	 */
	public function test_sanitize_form_settings_defaults_fallback_language_to_auto(): void {
		$registrar = new SettingsRegistrar();
		$input     = [
			'consent_text'          => '',
			'success_message'       => '',
			'offer_direct_download' => false,
		];

		$result = $registrar->sanitize_form_settings( $input );

		$this->assertArrayHasKey( 'fallback_language', $result );
		$this->assertSame( 'auto', $result['fallback_language'] );
	}
}
