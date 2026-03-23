<?php
/**
 * Unit tests for AjaxHandlers::handle_submit_form().
 *
 * @package WpMembershipRegistration\Tests\Unit\Admin
 */

namespace WpMembershipRegistration\Tests\Unit\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use PHPUnit\Framework\TestCase;
use WpMembershipRegistration\Admin\AjaxHandlers;

/**
 * AjaxHandlers unit tests — FORM-03 (honeypot), FORM-01 (submit pipeline).
 */
class AjaxHandlersTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Base stubs used across tests.
		Functions\stubs(
			[
				'check_ajax_referer'   => true,
				'sanitize_text_field'  => fn( $v ) => (string) $v,
				'sanitize_email'       => fn( $v ) => (string) $v,
				'wp_unslash'           => fn( $v ) => $v,
				'get_option'           => function ( $option, $default = null ) {
					if ( 'wmr_field_schema' === $option ) {
						return '[{"label":"E-Mail","type":"email","required":true},{"label":"Vorname","type":"text","required":true}]';
					}
					if ( 'wmr_form_settings' === $option ) {
						return [ 'success_message' => 'Danke!' ];
					}
					return $default;
				},
				'wp_send_json_error'   => function ( $data = null, $status_code = null ) {
					// No-op.
				},
			]
		);
	}

	protected function tearDown(): void {
		// Restore $_POST after each test.
		$_POST = [];
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_honeypot_non_empty_calls_json_success_and_skips_action(): void {
		$_POST['website']    = 'http://spam.example.com';
		$_POST['wmr_fields'] = [];

		Functions\expect( 'wp_send_json_success' )->once();
		Actions\expectDone( 'wmr_form_submitted' )->never();

		$handler = new AjaxHandlers();
		$handler->handle_submit_form();

		// Mockery verifies call counts on tearDown; add a PHPUnit assertion to avoid risky flag.
		$this->addToAssertionCount( 1 );
	}

	public function test_clean_submission_fires_wmr_form_submitted(): void {
		$_POST['website']    = '';
		$_POST['wmr_fields'] = [
			'E-Mail'  => 'max@example.com',
			'Vorname' => 'Max',
		];

		Actions\expectDone( 'wmr_form_submitted' )
			->once()
			->with(
				\Mockery::type( 'array' ),
				'max@example.com'
			);

		Functions\expect( 'wp_send_json_success' )->once();

		$handler = new AjaxHandlers();
		$handler->handle_submit_form();

		// Mockery verifies call counts on tearDown; add a PHPUnit assertion to avoid risky flag.
		$this->addToAssertionCount( 1 );
	}

	public function test_clean_submission_responds_with_success_message(): void {
		$_POST['website']    = '';
		$_POST['wmr_fields'] = [ 'E-Mail' => 'test@example.com' ];

		Functions\expect( 'wp_send_json_success' )
			->once()
			->with( \Mockery::on( fn( $data ) => isset( $data['message'] ) && 'Danke!' === $data['message'] ) );

		$handler = new AjaxHandlers();
		$handler->handle_submit_form();

		// Mockery verifies call counts on tearDown; add a PHPUnit assertion to avoid risky flag.
		$this->addToAssertionCount( 1 );
	}
}
