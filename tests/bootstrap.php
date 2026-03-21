<?php
/**
 * PHPUnit bootstrap — loads Brain Monkey and the plugin autoloader.
 *
 * Does NOT load WordPress. Unit tests run without WP via Brain Monkey stubs.
 */

if ( ! defined( 'WMR_PLUGIN_DIR' ) ) {
	define( 'WMR_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once dirname( __DIR__ ) . '/vendor-prefixed/autoload.php';

// Register plugin PSR-4 autoloader for src/ classes.
spl_autoload_register( function ( $class ) {
	$prefix = 'WpMembershipRegistration\\';
	if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
		return;
	}
	$relative = substr( $class, strlen( $prefix ) );
	$file     = dirname( __DIR__ ) . '/src/' . str_replace( '\\', '/', $relative ) . '.php';
	if ( file_exists( $file ) ) {
		require $file;
	}
} );
