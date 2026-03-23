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

// Stub WordPress i18n functions not auto-stubbed by Brain Monkey.
// These are global stubs (not per-test) because they are pure pass-through
// in a test context and do not need per-test assertion control.
if ( ! function_exists( '__' ) ) {
	// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText,WordPress.WP.I18n.NonSingularStringLiteralDomain
	function __( string $text, string $domain = 'default' ): string { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.textFound
		return $text;
	}
}

// Stub wpautop() — not auto-stubbed by Brain Monkey; used by PdfGenerator::build_pdf().
// In a test context, return the input unchanged (no real HTML transformation needed).
if ( ! function_exists( 'wpautop' ) ) {
	function wpautop( string $pee, bool $br = true ): string {
		return $pee;
	}
}

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
