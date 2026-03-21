<?php
/**
 * Plugin Name: WP Membership Registration
 * Plugin URI:  https://github.com/bitstrange/wp-membership-registration-plugin
 * Description: Digital membership registration with PDF generation and email delivery.
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-membership-registration
 * Domain Path: /languages
 *
 * @package WpMembershipRegistration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WMR_VERSION', '1.0.0' );
define( 'WMR_PLUGIN_FILE', __FILE__ );
define( 'WMR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WMR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	function ( $class ) {
		$prefix = 'WpMembershipRegistration\\';
		if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = WMR_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

if ( file_exists( WMR_PLUGIN_DIR . 'vendor-prefixed/autoload.php' ) ) {
	require_once WMR_PLUGIN_DIR . 'vendor-prefixed/autoload.php';
}

( new \WpMembershipRegistration\Plugin() )->register();
