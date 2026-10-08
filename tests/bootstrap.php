<?php
/**
 * PHPUnit Test Bootstrap for Monri Payments.
 */

// Determine whether running the integration test suite.
$is_integration = false;
$argv = $_SERVER['argv'] ?? [];
foreach ( $argv as $arg ) {
	if ( stripos( $arg, 'integration' ) !== false ) {
		$is_integration = true;
		break;
	}
}

if ( $is_integration ) {
	require_once __DIR__ . '/Integration/bootstrap.php';
	return;
}

// Define ABSPATH if not defined.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

if ( ! defined( 'WP_DEBUG_LOG' ) ) {
	define( 'WP_DEBUG_LOG', false );
}

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load stubs.
require_once __DIR__ . '/stubs.php';

// Polyfills for PHPUnit versions.
require_once dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

// Initialize Brain Monkey for file includes that execute hooks on load.
\Brain\Monkey\setUp();

// Load plugin classes.
require_once dirname( __DIR__ ) . '/includes/settings.php';
require_once dirname( __DIR__ ) . '/includes/utils.php';
require_once dirname( __DIR__ ) . '/includes/logger.php';
require_once dirname( __DIR__ ) . '/includes/monri-api.php';
require_once dirname( __DIR__ ) . '/includes/monri-wspay-api.php';
require_once dirname( __DIR__ ) . '/includes/callback.php';
require_once dirname( __DIR__ ) . '/includes/installments-fee.php';
require_once dirname( __DIR__ ) . '/includes/gateway-adapter-webpay-form.php';
require_once dirname( __DIR__ ) . '/includes/gateway-adapter-webpay-lightbox.php';
require_once dirname( __DIR__ ) . '/includes/gateway-adapter-webpay-components.php';
require_once dirname( __DIR__ ) . '/includes/gateway-adapter-webpay-components-new.php';
require_once dirname( __DIR__ ) . '/includes/gateway-adapter-wspay.php';
require_once dirname( __DIR__ ) . '/includes/gateway-adapter-wspay-iframe.php';
require_once dirname( __DIR__ ) . '/includes/gateway.php';
require_once dirname( __DIR__ ) . '/includes/gateway-webpay-components-abstract.php';
require_once dirname( __DIR__ ) . '/includes/gateway-webpay-components-apple-pay.php';
require_once dirname( __DIR__ ) . '/includes/gateway-webpay-components-google-pay.php';
require_once dirname( __DIR__ ) . '/includes/gateway-webpay-components-keks-pay.php';
require_once dirname( __DIR__ ) . '/includes/gateway-webpay-components-pay-cek.php';
require_once dirname( __DIR__ ) . '/includes/blocks-support.php';
require_once dirname( __DIR__ ) . '/includes/blocks-support-components-apple-pay.php';
require_once dirname( __DIR__ ) . '/includes/blocks-support-components-google-pay.php';
require_once dirname( __DIR__ ) . '/includes/blocks-support-components-keks-pay.php';
require_once dirname( __DIR__ ) . '/includes/blocks-support-components-pay-cek.php';
require_once dirname( __DIR__ ) . '/includes/payment-token-webpay.php';
require_once dirname( __DIR__ ) . '/includes/payment-token-wspay.php';
require_once dirname( __DIR__ ) . '/monri.php';

\Brain\Monkey\tearDown();
