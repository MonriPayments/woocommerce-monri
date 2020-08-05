<?php
/*
    Plugin Name: Monri
    Plugin URI: http://www.monri.com
    Description: Monri - Payment gateway for woocommerce
    Version: 2.9.11
    Author: Monri Paymnents d.o.o
    Author URI: http://www.monri.com
*/

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'woocommerce_pikpay_init', 0 );
function woocommerce_pikpay_init() {
	// If the parent WC_Payment_Gateway class doesn't exist
	// it means WooCommerce is not installed on the site
	// so do nothing

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	// If we made it this far, then include our Gateway Class
	include_once( 'class-pikpay.php' );

	// Now that we have successfully included our class,
	// Lets add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_pikpay_gateway' );

	function woocommerce_add_pikpay_gateway( $methods ) {
		$methods[] = 'WC_PikPay';
		return $methods;
	}
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pikpay_action_links' );

function pikpay_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_pikpay' ) . '">' . __( 'Settings', 'pikpay' ) . '</a>',
	);

	// Merge our new link with the default ones
	return array_merge( $plugin_links, $links );
}