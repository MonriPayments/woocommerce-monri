<?php
/**
 * PHPUnit Integration Test Bootstrap for Monri Payments (WordPress & WooCommerce).
 */

$monri_dir = dirname( __DIR__, 2 );
$wp_phpunit_dir = $monri_dir . '/vendor/wp-phpunit/wp-phpunit';

if ( ! getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );
}

if ( ! defined( 'WP_TESTS_DIR' ) ) {
	define( 'WP_TESTS_DIR', $wp_phpunit_dir );
}

// Load test functions from wp-phpunit.
require_once $wp_phpunit_dir . '/includes/functions.php';

// Hook to load WooCommerce and Monri before WordPress finishes initializing.
tests_add_filter( 'muplugins_loaded', function () use ( $monri_dir ) {
	$wc_file = dirname( $monri_dir ) . '/woocommerce/woocommerce.php';
	if ( file_exists( $wc_file ) ) {
		require_once $wc_file;
	}

	require_once $monri_dir . '/monri.php';
} );

// Start up the WordPress testing environment.
require_once $wp_phpunit_dir . '/includes/bootstrap.php';

// Install WooCommerce tables if WooCommerce is present.
if ( class_exists( 'WC_Install' ) ) {
	WC_Install::install();
}

// Polyfills for PHPUnit versions if not loaded.
if ( file_exists( $monri_dir . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' ) ) {
	require_once $monri_dir . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
}
