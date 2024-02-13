<?php
/*
Plugin Name: Monri
Plugin URI: http://www.monri.com
Description: Monri - Payment gateway for WooCommerce
Version: 3.0.0
Author: Monri Payments d.o.o.
Author URI: http://www.monri.com
WC requires at least: 3.1.0
WC tested up to: 8.5
*/

define( 'MONRI_WC_VERSION', '3.0.0' );
define( 'MONRI_WC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/settings.php';
//require_once __DIR__ . '/includes/i18n.php';

function monri_wc_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
	    return;
    }

	require_once __DIR__ . '/includes/gateway-webpay-form.php';
	require_once __DIR__ . '/includes/gateway-components.php';

    function woocommerce_add_monri_gateway($methods)
    {
        $methods[] = Monri_WC_Gateway_Webpay_Form::class;
	    $methods[] = Monri_WC_Gateway_Components::class;
        return $methods;
    }
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_monri_gateway');

	// woocommerce actions/filters here?!

}
add_action('plugins_loaded', 'monri_wc_init', 0);

function monri_wc_action_links($links)
{
	$links[] = sprintf(
		'<a href="%s">%s</a>',
		admin_url('admin.php?page=wc-settings&tab=checkout&section=monri_wc'),
		__('Settings', 'monri_wc')
	);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'monri_wc_action_links');


// controllers here? call what's needed

/*
require_once __DIR__ . '/util.php';
require_once __DIR__ . '/monri-i18n.php';

define('MONRI_CALLBACK_IMPL', true);
require_once __DIR__ . '/callback-url.php';

define('MONRI_WS_PAY_REDIRECT', true);
require_once __DIR__ . '/ws-pay-redirect.php';
require_once __DIR__ . '/monri-api.php';
*/
