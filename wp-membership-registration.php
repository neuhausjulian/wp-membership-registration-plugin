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
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WMR_VERSION', '1.0.0' );
define( 'WMR_PLUGIN_FILE', __FILE__ );
define( 'WMR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WMR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WMR_PLUGIN_DIR . 'vendor/autoload.php';

( new \WpMembershipRegistration\Plugin() )->register();
