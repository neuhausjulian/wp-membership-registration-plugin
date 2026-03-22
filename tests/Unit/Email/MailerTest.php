<?php
/**
 * Unit tests for Mailer.
 *
 * @package WpMembershipRegistration\Tests\Unit\Email
 */

namespace WpMembershipRegistration\Tests\Unit\Email;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Mailer unit tests — MAIL-01, MAIL-02.
 *
 * All tests reference WpMembershipRegistration\Email\Mailer which does not exist yet (Wave 1).
 * Running this file before Wave 1 must produce a class-not-found error (red phase).
 */
class MailerTest extends TestCase {

	/**
	 * Schema JSON with one email-type field.
	 *
	 * @var string
	 */
	private const SCHEMA_WITH_EMAIL = '[{"label":"Email","type":"email","required":true}]';

	/**
	 * Schema JSON with no email-type field.
	 *
	 * @var string
	 */
	private const SCHEMA_NO_EMAIL = '[{"label":"Name","type":"text","required":true}]';

	/**
	 * Email settings option with a single admin recipient.
	 *
	 * @var array<string, string>
	 */
	private const EMAIL_SETTINGS_ADMIN = [ 'recipients' => 'admin@example.com' ];

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\stubs(
			[
				'wp_generate_uuid4' => fn() => 'test-uuid',
				'sanitize_email'    => fn( $v ) => $v,
			]
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * MAIL-01: Member email is sent with the PDF as an attachment.
	 *
	 * @test
	 */
	public function test_member_email_sent_with_pdf_attachment(): void {
		Functions\stubs(
			[
				'get_option' => function ( $option, $default = null ) {
					if ( 'wmr_email_settings' === $option ) {
						return self::EMAIL_SETTINGS_ADMIN;
					}
					if ( 'wmr_field_schema' === $option ) {
						return self::SCHEMA_WITH_EMAIL;
					}
					return $default;
				},
				'wp_delete_file' => fn( $path ) => null,
			]
		);

		$mock_generator = $this->createMock( \WpMembershipRegistration\Pdf\PdfGenerator::class );
		$mock_generator->method( 'generate' )->willReturn( '/tmp/test.pdf' );

		Functions\expect( 'wp_mail' )
			->once()
			->with( 'member@test.com', \Mockery::type( 'string' ), \Mockery::type( 'string' ), \Mockery::any(), \Mockery::on( fn( $a ) => in_array( '/tmp/test.pdf', (array) $a, true ) ) )
			->andReturn( true );

		Functions\expect( 'wp_mail' )
			->once()
			->with( \Mockery::any(), \Mockery::type( 'string' ), \Mockery::type( 'string' ), \Mockery::any(), \Mockery::on( fn( $a ) => in_array( '/tmp/test.pdf', (array) $a, true ) ) )
			->andReturn( true );

		$mailer = new \WpMembershipRegistration\Email\Mailer( $mock_generator );
		$mailer->handle_submission( [ 'Email' => 'member@test.com' ], 'member@test.com' );
	}

	/**
	 * MAIL-02: Admin email is sent with the PDF as an attachment.
	 *
	 * @test
	 */
	public function test_admin_email_sent_with_pdf_attachment(): void {
		Functions\stubs(
			[
				'get_option' => function ( $option, $default = null ) {
					if ( 'wmr_email_settings' === $option ) {
						return self::EMAIL_SETTINGS_ADMIN;
					}
					if ( 'wmr_field_schema' === $option ) {
						return self::SCHEMA_WITH_EMAIL;
					}
					return $default;
				},
				'wp_delete_file' => fn( $path ) => null,
			]
		);

		$mock_generator = $this->createMock( \WpMembershipRegistration\Pdf\PdfGenerator::class );
		$mock_generator->method( 'generate' )->willReturn( '/tmp/test.pdf' );

		$admin_received = false;

		Functions\expect( 'wp_mail' )
			->twice()
			->andReturnUsing(
				function ( $to, $subject, $message, $headers = [], $attachments = [] ) use ( &$admin_received ) {
					if ( 'admin@example.com' === $to ) {
						$admin_received = true;
						$this->assertContains( '/tmp/test.pdf', (array) $attachments, 'Admin email must have PDF attachment' );
					}
					return true;
				}
			);

		$mailer = new \WpMembershipRegistration\Email\Mailer( $mock_generator );
		$mailer->handle_submission( [ 'Email' => 'member@test.com' ], 'member@test.com' );

		$this->assertTrue( $admin_received, 'Admin email was not sent' );
	}

	/**
	 * MAIL-01: Member email is skipped when no email-type field exists in the schema.
	 *
	 * @test
	 */
	public function test_member_email_skipped_when_no_email_field_in_schema(): void {
		Functions\stubs(
			[
				'get_option' => function ( $option, $default = null ) {
					if ( 'wmr_email_settings' === $option ) {
						return self::EMAIL_SETTINGS_ADMIN;
					}
					if ( 'wmr_field_schema' === $option ) {
						return self::SCHEMA_NO_EMAIL;
					}
					return $default;
				},
				'wp_delete_file' => fn( $path ) => null,
			]
		);

		$mock_generator = $this->createMock( \WpMembershipRegistration\Pdf\PdfGenerator::class );
		$mock_generator->method( 'generate' )->willReturn( '/tmp/test.pdf' );

		// Only one call — admin only; member send must be skipped.
		Functions\expect( 'wp_mail' )
			->once()
			->andReturn( true );

		$mailer = new \WpMembershipRegistration\Email\Mailer( $mock_generator );
		$mailer->handle_submission( [ 'Name' => 'Max' ], '' );
	}

	/**
	 * MAIL-02: Admin email still sends when member email is skipped due to missing email field.
	 *
	 * @test
	 */
	public function test_admin_email_sends_even_without_member_email(): void {
		Functions\stubs(
			[
				'get_option' => function ( $option, $default = null ) {
					if ( 'wmr_email_settings' === $option ) {
						return self::EMAIL_SETTINGS_ADMIN;
					}
					if ( 'wmr_field_schema' === $option ) {
						return self::SCHEMA_NO_EMAIL;
					}
					return $default;
				},
				'wp_delete_file' => fn( $path ) => null,
			]
		);

		$mock_generator = $this->createMock( \WpMembershipRegistration\Pdf\PdfGenerator::class );
		$mock_generator->method( 'generate' )->willReturn( '/tmp/test.pdf' );

		$admin_to = null;

		Functions\expect( 'wp_mail' )
			->once()
			->andReturnUsing(
				function ( $to ) use ( &$admin_to ) {
					$admin_to = $to;
					return true;
				}
			);

		$mailer = new \WpMembershipRegistration\Email\Mailer( $mock_generator );
		$mailer->handle_submission( [ 'Name' => 'Max' ], '' );

		$this->assertSame( 'admin@example.com', $admin_to, 'Admin email must be sent to configured recipient' );
	}

	/**
	 * MAIL-01/02: Temp PDF file is deleted after successful sends.
	 *
	 * @test
	 */
	public function test_temp_file_deleted_after_successful_sends(): void {
		Functions\stubs(
			[
				'get_option' => function ( $option, $default = null ) {
					if ( 'wmr_email_settings' === $option ) {
						return self::EMAIL_SETTINGS_ADMIN;
					}
					if ( 'wmr_field_schema' === $option ) {
						return self::SCHEMA_WITH_EMAIL;
					}
					return $default;
				},
			]
		);

		$mock_generator = $this->createMock( \WpMembershipRegistration\Pdf\PdfGenerator::class );
		$mock_generator->method( 'generate' )->willReturn( '/tmp/test-uuid.pdf' );

		Functions\expect( 'wp_mail' )
			->twice()
			->andReturn( true );

		Functions\expect( 'wp_delete_file' )
			->once()
			->with( '/tmp/test-uuid.pdf' );

		$mailer = new \WpMembershipRegistration\Email\Mailer( $mock_generator );
		$mailer->handle_submission( [ 'Email' => 'member@test.com' ], 'member@test.com' );
	}

	/**
	 * MAIL-01/02: Temp PDF file is deleted even when wp_mail() returns false (finally block).
	 *
	 * @test
	 */
	public function test_temp_file_deleted_even_when_wp_mail_fails(): void {
		Functions\stubs(
			[
				'get_option' => function ( $option, $default = null ) {
					if ( 'wmr_email_settings' === $option ) {
						return self::EMAIL_SETTINGS_ADMIN;
					}
					if ( 'wmr_field_schema' === $option ) {
						return self::SCHEMA_WITH_EMAIL;
					}
					return $default;
				},
			]
		);

		$mock_generator = $this->createMock( \WpMembershipRegistration\Pdf\PdfGenerator::class );
		$mock_generator->method( 'generate' )->willReturn( '/tmp/test-uuid.pdf' );

		Functions\expect( 'wp_mail' )
			->twice()
			->andReturn( false );

		Functions\expect( 'wp_delete_file' )
			->once()
			->with( '/tmp/test-uuid.pdf' );

		$mailer = new \WpMembershipRegistration\Email\Mailer( $mock_generator );
		$mailer->handle_submission( [ 'Email' => 'member@test.com' ], 'member@test.com' );
	}
}
