<?php

defined('MONRI_WS_PAY_REDIRECT') or die('Invalid request.');

function monri_ws_pay_handle_redirect()
{
    $uri = parse_url(site_url() . $_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($uri != '/ws-pay-redirect') {
        return;
    }

    $monri = new MonriApi();
    $rv = $monri->monri_ws_pay_handle_redirect(MonriI18n::get_en_translation());
    if ($rv['success']) {
        // Thankyou page
        $url = wc_get_checkout_url() . get_option('woocommerce_checkout_order_received_endpoint', 'order-received');
    } else {
        $url = WC_Order::get_cancel_endpoint();
    }
    $query = parse_url($url, PHP_URL_QUERY);

// Returns a string if the URL has parameters or NULL if not
    if ($query) {
        $url .= '&message=' . $rv['message'] . '&class=' . $rv['class'];
    } else {
        $url .= '?message=' . $rv['message'] . '&class=' . $rv['class'];
    }
    monri_redirect($url);
}
