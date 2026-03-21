<?php
/**
 * AJAX handler stubs — implemented in Plan 03.
 *
 * @package WpMembershipRegistration
 */

namespace WpMembershipRegistration\Admin;

/**
 * Handles wp_ajax_ actions for the plugin settings page.
 */
class AjaxHandlers {

	/**
	 * Handle the wmr_send_test_email AJAX action.
	 *
	 * @return void
	 */
	public function handle_send_test_email(): void {
		// Implemented in Plan 03.
		wp_send_json_error( array( 'message' => 'Not yet implemented.' ) );
	}
}
