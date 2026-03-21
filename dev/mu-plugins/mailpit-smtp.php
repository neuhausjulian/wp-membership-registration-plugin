<?php
/**
 * MU-Plugin: Route wp_mail() through Mailpit for local development.
 *
 * Loaded automatically by WordPress from mu-plugins/. Only mounted
 * in the Docker environment — not included in production plugin zip.
 *
 * @package WpMembershipRegistration
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'wp_mail_from', fn() => 'wordpress@dev.local' );
add_filter( 'wp_mail_from_name', fn() => 'WordPress Dev' );

add_action(
	'phpmailer_init',
	function ( $phpmailer ) {
		$phpmailer->isSMTP();
		$phpmailer->Host        = 'mailpit';
		$phpmailer->Port        = 1025;
		$phpmailer->SMTPAuth    = false;
		$phpmailer->SMTPSecure  = '';
		$phpmailer->SMTPAutoTLS = false;
	}
);
