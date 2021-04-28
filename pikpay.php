<?php
/*
    Plugin Name: Monri
    Plugin URI: http://www.monri.com
    Description: Monri - Payment gateway for woocommerce
    Version: 2.10.4
    Author: Monri Paymnents d.o.o
    Author URI: http://www.monri.com
*/

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'woocommerce_monri_init', 0 );
function woocommerce_monri_init() {
	// If the parent WC_Payment_Gateway class doesn't exist
	// it means WooCommerce is not installed on the site
	// so do nothing

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	// If we made it this far, then include our Gateway Class
	include_once( 'class-monri.php' );

	// Now that we have successfully included our class,
	// Lets add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_monri_gateway' );

	function woocommerce_add_monri_gateway( $methods ) {
		$methods[] = 'WC_Monri';
		return $methods;
	}
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'monri_action_links' );

function monri_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_monri' ) . '">' . __( 'Settings', 'monri' ) . '</a>',
	);

	// Merge our new link with the default ones
	return array_merge( $plugin_links, $links );
}


// A way to stop the form checkout submit event via JQuery could not be found.
// To simulate this a fake error needs to be thrown so that the generate monri toke can be added to the 'monri-token' form field.
// Once the token is added checkout process will be triggered via JQuery.
add_action('woocommerce_after_checkout_validation', 'fake_checkout_form_error');
function fake_checkout_form_error($posted) {

    if ($_POST['monri-token'] == 'not-set') {
        wc_add_notice( __( "set_monri_token_notice", 'fake_error' ), 'error');
    }
}
