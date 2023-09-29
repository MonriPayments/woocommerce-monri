<?php /** @noinspection ALL */
/*
    Plugin Name: Monri
    Plugin URI: http://www.monri.com
    Description: Monri - Payment gateway for woocommerce
    Version: 2.11
    Author: Monri Payments d.o.o
    Author URI: http://www.monri.com
*/

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action('plugins_loaded', 'woocommerce_monri_init', 0);
function woocommerce_monri_init()
{
    // If the parent WC_Payment_Gateway class doesn't exist
    // it means WooCommerce is not installed on the site
    // so do nothing

    if (!class_exists('WC_Payment_Gateway')) return;

    // If we made it this far, then include our Gateway Class
    include_once('class-monri.php');

    // Now that we have successfully included our class,
    // Lets add it too WooCommerce
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_monri_gateway');

    function woocommerce_add_monri_gateway($methods)
    {
        $methods[] = 'WC_Monri';
        return $methods;
    }
}

// Add custom action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'monri_action_links');

function monri_action_links($links)
{
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_monri') . '">' . __('Settings', 'monri') . '</a>',
    );

    // Merge our new link with the default ones
    return array_merge($plugin_links, $links);
}


// A way to stop the form checkout submit event via JQuery could not be found.
// To simulate this a fake error needs to be thrown so that the generate monri toke can be added to the 'monri-token' form field.
// Once the token is added checkout process will be triggered via JQuery.
add_action('woocommerce_after_checkout_validation', 'fake_checkout_form_error');
function fake_checkout_form_error($posted)
{

    if ($_POST['monri-token'] == 'not-set') {
        wc_add_notice(__("set_monri_token_notice", 'fake_error'), 'error');
    }
}

require_once __DIR__ . '/util.php';
require_once __DIR__ . '/monri-i18n.php';

define('MONRI_CALLBACK_IMPL', true);
require_once __DIR__ . '/callback-url.php';

define('MONRI_WS_PAY_REDIRECT', true);
require_once __DIR__ . '/ws-pay-redirect.php';
require_once __DIR__ . '/monri-api.php';

function handle_incoming_3ds_request()
{
    $uri = parse_url(site_url() . $_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if ($uri === '/monri-3ds-payment-result') {
        $payment_token = isset($_GET['payment_token']) ? $_GET['payment_token'] : null;

        if (!$payment_token) {
            return;
        }

        $monri = new MonriApi();
        $monri->resolvePaymentStatus($_GET['payment_token']);
    }
}

add_action('parse_request', function () {
    handle_incoming_3ds_request();

    $monri_settings = get_option('woocommerce_monri_settings');


    if (!is_array($monri_settings) || !isset($monri_settings['callback_url_endpoint'])) {
        return;
    }

    $monri_callback_url_endpoint = isset($monri_settings['callback_url_endpoint']) ?
        $monri_settings['callback_url_endpoint'] : '/monri-callback';

    $merchant_key = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_ends_with($_SERVER['REQUEST_URI'], $monri_callback_url_endpoint)) {
        if (!isset($monri_settings['monri_merchant_key'])) {
            monri_error('Monri key is not defined or does not exist.', array(404, 'Not Found'));
        }

        $merchant_key = $monri_settings['monri_merchant_key'];
    }

    monri_ws_pay_handle_redirect();

    monri_handle_callback($monri_callback_url_endpoint, $merchant_key, function ($payload) {
        $order_number = $payload['order_number'];

        try {
            $order = new WC_Order($order_number);

            $skip_statuses = array('cancelled', 'failed', 'refunded');

            if (in_array($order->get_status(), $skip_statuses)) {
                monri_done();
            }

            if ($payload['status'] === 'approved') {
                $note = sprintf(
                    'Order #%s-%s is successfully processed, ref. num. %s: %s.',
                    $payload['id'], $order_number, $payload['reference_number'], $payload['response_message']
                );

                $order->update_status('wc-completed', $note);
                $order->add_order_note($note);
            }
        } catch (\Exception $e) {
            $message = sprintf('Order ID: %s not found or does not exist.', $order_number);
            monri_error($message, array(404, 'Not Found'));
        }
    });
});
