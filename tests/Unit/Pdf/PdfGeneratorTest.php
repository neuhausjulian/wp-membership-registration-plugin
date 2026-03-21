<?php
/**
 * Unit tests for PdfGenerator.
 *
 * Tests run without WordPress using Brain Monkey function stubs.
 *
 * @package WpMembershipRegistration\Tests\Unit\Pdf
 */

namespace WpMembershipRegistration\Tests\Unit\Pdf;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

/**
 * PdfGenerator unit tests — PDF-01, PDF-02, PDF-03.
 */
class PdfGeneratorTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * PDF-01: generate() returns a path to an existing file with .pdf extension.
	 * The file begins with the PDF header bytes (%PDF).
	 *
	 * @test
	 */
	public function test_generate_returns_pdf_file(): void {
		$this->markTestIncomplete( 'PdfGenerator not yet implemented — Wave 1.' );
	}

	/**
	 * PDF-02: The HTML template includes the club name and accent color
	 * from wmr_pdf_branding options.
	 *
	 * @test
	 */
	public function test_branding_fields_appear_in_template(): void {
		$this->markTestIncomplete( 'PdfGenerator not yet implemented — Wave 1.' );
	}

	/**
	 * PDF-03: generate_blank() returns a path to an existing file;
	 * the HTML rendered for the blank PDF contains underline placeholders.
	 *
	 * @test
	 */
	public function test_blank_pdf_contains_underlines(): void {
		$this->markTestIncomplete( 'PdfGenerator not yet implemented — Wave 1.' );
	}
}
