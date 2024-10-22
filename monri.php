<?php
/*
Plugin Name: Monri Payments
Description: Official Monri Payments gateway for WooCommerce
Version: 3.2.2
Author: Monri Payments d.o.o.
Author URI: https://monri.com
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
WC requires at least: 4.3.0
WC tested up to: 9.3.3
*/
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MONRI_WC_VERSION', '3.2.2' );
define( 'MONRI_WC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MONRI_WC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MONRI_WC_PLUGIN_INDEX', __FILE__ );

require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/utils.php';
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


function monri_load_language() {
	load_plugin_textdomain( 'monri', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'monri_load_language' );


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


// Migrate settings from older version to new option settings, disable deprecated modules
function monri_legacy_migrate() {

	$monri_settings = get_option( Monri_WC_Settings::SETTINGS_KEY );
	if ( $monri_settings && is_array( $monri_settings ) ) {
		return;
	}

	$old_settings = get_option( 'woocommerce_pikpay_settings' );
	if ( ! $old_settings || ! is_array( $old_settings ) ) {
		return;
	}

	$old_to_new_map = [
		'enabled'                        => 'enabled',
		'title'                          => 'title',
		'description'                    => 'description',
		'instructions'                   => 'instructions',
		'pikpaykey'                      => 'monri_merchant_key',
		'pikpayauthtoken'                => 'monri_authenticity_token',
		'test_mode'                      => 'test_mode',
		'transaction_type'               => 'transaction_type',
		'form_language'                  => 'form_language',
		'paying_in_installments'         => 'paying_in_installments',
		'number_of_allowed_installments' => 'number_of_allowed_installments',
		'bottom_limit'                   => 'bottom_limit'
	];

	for ( $i = 2; $i <= 36; $i ++ ) {
		$old_to_new_map["price_increase_$i"] = "price_increase_$i";
	}

	$new_settings = [];
	foreach ( $old_to_new_map as $old => $new ) {
		if ( isset( $old_settings[ $old ] ) ) {
			$new_settings[ $new ] = $old_settings[ $old ];
		}
	}

	if ( ! $new_settings ) {
		return;
	}

	$new_settings['monri_payment_gateway_service'] = 'monri-web-pay';

	if ( isset( $old_settings['pickpay_methods'] ) && $old_settings['pickpay_methods'] ) {
		$new_settings['monri_web_pay_integration_type'] = 'components';
	} else {
		$new_settings['monri_web_pay_integration_type'] = 'form';
	}

	add_option( Monri_WC_Settings::SETTINGS_KEY, $new_settings );
}

register_activation_hook( __FILE__, 'monri_legacy_migrate' );
