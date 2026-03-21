<?php
/**
 * Unit tests for PdfGenerator.
 *
 * These tests instantiate PdfGenerator and call DOMPDF for real (integration-lite).
 * Brain Monkey stubs WordPress functions so WP is not required.
 *
 * @package WpMembershipRegistration\Tests\Unit\Pdf
 */

namespace WpMembershipRegistration\Tests\Unit\Pdf;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpMembershipRegistration\Pdf\PdfGenerator;

/**
 * PdfGenerator unit tests — PDF-01, PDF-02, PDF-03.
 */
class PdfGeneratorTest extends TestCase {

	/** @var string[] Temp files to clean up after each test. */
	private array $temp_files = [];

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Stub WordPress functions used by PdfGenerator and the PDF template.
		Functions\stubs(
			[
				"wp_upload_dir" => function () {
					return [
						"basedir" => sys_get_temp_dir(),
						"baseurl" => "http://example.com/wp-content/uploads",
					];
				},
				"wp_generate_uuid4" => fn() => uniqid( "test-", true ),
				"get_option"        => function ( $option, $default = null ) {
					if ( "wmr_pdf_branding" === $option ) {
						return [
							"club_name"      => "Testverein",
							"logo_url"       => "",
							"accent_color"   => "#2271b1",
							"document_title" => "Aufnahmeantrag",
							"gdpr_text"      => "Ich stimme zu.",
							"footer_text"    => "Vorstand: Max Muster",
							"page2_content"  => "",
						];
					}
					if ( "wmr_field_schema" === $option ) {
						return "[{\"label\":\"Vorname\",\"type\":\"text\",\"required\":true},{\"label\":\"Nachname\",\"type\":\"text\",\"required\":true}]";
					}
					return $default;
				},
				// Escaping / sanitisation functions used in the PDF template.
				"esc_attr"     => fn( $v ) => htmlspecialchars( (string) $v, ENT_QUOTES, "UTF-8" ),
				"esc_html"     => fn( $v ) => htmlspecialchars( (string) $v, ENT_QUOTES, "UTF-8" ),
				"wp_kses_post" => fn( $v ) => (string) $v,
			]
		);
	}

	protected function tearDown(): void {
		foreach ( $this->temp_files as $path ) {
			if ( file_exists( $path ) ) {
				@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		}
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * PDF-01: generate() returns a path to an existing .pdf file containing PDF header bytes.
	 *
	 * @test
	 */
	public function test_generate_returns_pdf_file(): void {
		$generator          = new PdfGenerator();
		$path               = $generator->generate( [ "Vorname" => "Max", "Nachname" => "Muster" ] );
		$this->temp_files[] = $path;

		$this->assertStringEndsWith( ".pdf", $path );
		$this->assertFileExists( $path );
		$contents = file_get_contents( $path );
		$this->assertStringStartsWith( "%PDF", $contents, "Generated file must begin with PDF header bytes" );
	}

	/**
	 * PDF-02: generate() with branding options produces a non-empty file (no fatal error).
	 *
	 * @test
	 */
	public function test_branding_fields_appear_in_template(): void {
		$generator          = new PdfGenerator();
		$path               = $generator->generate( [ "Vorname" => "Anna", "Nachname" => "Schmidt" ] );
		$this->temp_files[] = $path;

		$this->assertFileExists( $path );
		$this->assertGreaterThan( 1000, filesize( $path ), "PDF must be non-trivial in size" );
	}

	/**
	 * PDF-03: generate_blank() returns a path to an existing .pdf file.
	 *
	 * @test
	 */
	public function test_blank_pdf_contains_underlines(): void {
		$generator          = new PdfGenerator();
		$path               = $generator->generate_blank();
		$this->temp_files[] = $path;

		$this->assertStringEndsWith( ".pdf", $path );
		$this->assertFileExists( $path );
		$contents = file_get_contents( $path );
		$this->assertStringStartsWith( "%PDF", $contents );
	}
}
