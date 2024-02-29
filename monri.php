<?php
/*
Plugin Name: WooCommerce Monri
Description: Monri - Payment gateway for WooCommerce
Version: 3.0.0
Author: Monri Payments d.o.o.
Author URI: https://monri.com
WC requires at least: 4.3.0
WC tested up to: 8.6
*/

define( 'MONRI_WC_VERSION', '3.0.0' );
define( 'MONRI_WC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MONRI_WC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/logger.php';

function monri_wc_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	require_once __DIR__ . '/includes/gateway.php';

	function woocommerce_add_monri_gateway( $methods ) {
		$methods[] = Monri_WC_Gateway::class;

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_monri_gateway' );
}
add_action( 'plugins_loaded', 'monri_wc_init', 0 );


/**
 * Declares support for WooCommerce features, HPOS.
 * Coming soon
 */
function monri_declare_woo_feature_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
}
add_action( 'before_woocommerce_init', 'monri_declare_woo_feature_compatibility' );


function monri_wc_action_links( $links ) {
	$links[] = sprintf(
		'<a href="%s">%s</a>',
		admin_url( 'admin.php?page=wc-settings&tab=checkout&section=monri' ),
		__( 'Settings', 'monri' )
	);

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'monri_wc_action_links' );


function load_language() {
	load_plugin_textdomain( 'monri', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'load_language' );


// Registers Blocks integration.
function monri_wc_block_support() {
	if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		require_once __DIR__ . '/includes/blocks-support.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( new Monri_WC_Blocks_Support() );
			}
		);
	}
}
add_action( 'woocommerce_blocks_loaded', 'monri_wc_block_support' );
