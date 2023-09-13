<?php

/* Monri Payment Gateway Class */

class WC_Monri extends WC_Payment_Gateway
{
    // Setup our Gateway's id, description and other values
    function __construct()
    {
        // The global ID for this Payment method
        $this->id = "monri";

        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = __("Monri", 'monri');

        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = __("Monri Payment Gateway Plug-in for WooCommerce", 'monri');

        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = __("Monri", 'monri');

        // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
        $this->icon = null;

        // This basically defines your settings which are then loaded with init_settings()
        $this->init_form_fields();

        // After init_settings() is called, you can get the settings and load them into variables, e.g:
        // $this->title = $this->get_option( 'title' );
        $this->init_settings();

        // Thankyou page
        $this->thankyou_page = wc_get_checkout_url() . get_option('woocommerce_checkout_order_received_endpoint', 'order-received');

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // Define user set variables.
        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->instructions = $this->get_option('instructions');
        $this->payment_gateway_service = $this->get_option('monri_payment_gateway_service');

        //        $this->thankyou_page = $this->settings['thankyou_page'];
        $this->callback_url_endpoint = $this->get_option('callback_url_endpoint');

        $this->success_url_override = $this->get_option('success_url_override');
        $this->cancel_url_override = $this->get_option('cancel_url_override');
        $this->callback_url_override = $this->get_option('callback_url_override');

        $this->monri_merchant_key = $this->get_option('monri_merchant_key');
        $this->monri_ws_pay_form_shop_id = $this->get_option('monri_ws_pay_form_shop_id');
        $this->monri_ws_pay_form_secret = $this->get_option('monri_ws_pay_form_secret');
        $this->monri_ws_pay_form_tokenization_enabled = $this->get_option('monri_ws_pay_form_tokenization_enabled');
        $this->monri_ws_pay_form_tokenization_shop_id = $this->get_option('monri_ws_pay_form_tokenization_shop_id');
        $this->monri_ws_pay_form_tokenization_secret = $this->get_option('monri_ws_pay_form_tokenization_secret');
        //        $this->monri_ws_pay_components_secret = $this->get_option('monri_ws_pay_components_secret');
//        $this->monri_ws_pay_components_shop_id = $this->get_option('monri_ws_pay_components_shop_id');
        $this->monri_authenticity_token = $this->get_option('monri_authenticity_token');
        $this->monri_web_pay_integration_type = $this->get_option('monri_web_pay_integration_type', array());
        $this->payment_processor = $this->get_option('payment_processor', array());
        $this->test_mode = $this->get_option('test_mode', array());
        $this->transaction_type = $this->get_option('transaction_type', array());
        $this->form_language = $this->get_option('form_language', array());
        $this->paying_in_installments = $this->get_option('paying_in_installments', array());
        $this->number_of_allowed_installments = $this->get_option('number_of_allowed_installments', array());
        $this->bottom_limit = $this->get_option('bottom_limit', array());
        $this->price_increase_2 = $this->get_option('price_increase_2', array());
        $this->price_increase_3 = $this->get_option('price_increase_3', array());
        $this->price_increase_4 = $this->get_option('price_increase_4', array());
        $this->price_increase_5 = $this->get_option('price_increase_5', array());
        $this->price_increase_6 = $this->get_option('price_increase_6', array());
        $this->price_increase_7 = $this->get_option('price_increase_7', array());
        $this->price_increase_8 = $this->get_option('price_increase_8', array());
        $this->price_increase_9 = $this->get_option('price_increase_9', array());
        $this->price_increase_10 = $this->get_option('price_increase_10', array());
        $this->price_increase_11 = $this->get_option('price_increase_11', array());
        $this->price_increase_12 = $this->get_option('price_increase_12', array());
        $this->price_increase_13 = $this->get_option('price_increase_13', array());
        $this->price_increase_14 = $this->get_option('price_increase_14', array());
        $this->price_increase_15 = $this->get_option('price_increase_15', array());
        $this->price_increase_16 = $this->get_option('price_increase_16', array());
        $this->price_increase_17 = $this->get_option('price_increase_17', array());
        $this->price_increase_18 = $this->get_option('price_increase_18', array());
        $this->price_increase_19 = $this->get_option('price_increase_19', array());
        $this->price_increase_20 = $this->get_option('price_increase_20', array());
        $this->price_increase_21 = $this->get_option('price_increase_21', array());
        $this->price_increase_22 = $this->get_option('price_increase_22', array());
        $this->price_increase_23 = $this->get_option('price_increase_23', array());
        $this->price_increase_24 = $this->get_option('price_increase_24', array());

        $this->msg['message'] = "";
        $this->msg['class'] = "";
        $this->api = new MonriApi();

        //add_option('woocommerce_pay_page_id', $page_id);

        // Lets check for SSL
        add_action('admin_notices', array($this, 'do_ssl_check'));

        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
        } else {
            add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        }

        if ($this->is_form_integration()) {
            $this->check_monri_response();
            add_action('woocommerce_receipt_monri', array(&$this, 'receipt_page'));
            $this->has_fields = true;
        } else {
            $this->has_fields = true;
            $this->check_3dsecure_response();
        }

    } // End __construct()

    // public function process_admin_options()
    // {
    //     $this->init_settings();
    //     if ($this->get_option('monri_ws_pay_form_tokenization_enabled')) {
    //         if (empty($this->get_option('monri_ws_pay_form_tokenization_shop_id'))) {
    //             update_option('monri_ws_pay_form_tokenization_enabled', false);
    //             WC_Admin_Settings::add_error('Set monri_ws_pay_form_tokenization_shop_id for tokenization to be enabled');
    //             return false;
    //         }

    //         if (empty($this->get_option('monri_ws_pay_form_tokenization_secret'))) {
    //             update_option('monri_ws_pay_form_tokenization_enabled', false);
    //             WC_Admin_Settings::add_error('Set monri_ws_pay_form_tokenization_secret for tokenization to be enabled');
    //             return false;
    //         }
    //     }
    //     return parent::process_admin_options();
    // }

    function init_form_fields()
    {
        $yes_or_no = array(
            "0" => 'No',
            "1" => 'Yes'
        );

        $integration_types = array(
            "form" => 'Form',
            "components" => 'Components'
        );

        $transaction_type = array(
            "0" => "Purchase",
            "1" => "Authorize"
        );

        $number_of_allowed_installments = array(
            "24" => "24",
            "12" => "12",
            "6" => "6"
        );

        $form_language = array(
            "en" => "English",
            "ba-hr" => "Bosanski",
            "hr" => "Hrvatski",
            "sr" => "Srpski"
        );

        $payment_gateway_services = array(
            "monri-web-pay" => "Monri WebPay",
            "monri-ws-pay" => "Monri WSPay"
        );

        $form_id = 'wcwcCpg1';
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', $form_id),
                'type' => 'checkbox',
                'label' => __('Enable Monri', $form_id),
                'default' => 'no'
            ),
            'monri_payment_gateway_service' => array(
                'title' => __('Payment Gateway Service:', $form_id),
                'type' => 'select',
                'class' => 'chosen_select',
                'css' => 'width: 450px;',
                'default' => 'monri-web-pay',
                'description' => __('', $form_id),
                'options' => $payment_gateway_services,
                'desc_tip' => true,
            ),
            'title' => array(
                'title' => __('Title', $form_id),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', $form_id),
                'desc_tip' => true,
                'default' => __('Monri', $form_id)
            ),
            'description' => array(
                'title' => __('Description', $form_id),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', $form_id),
                'default' => __('Description for Monri', $form_id)
            ),
            'instructions' => array(
                'title' => __('Instructions', $form_id),
                'type' => 'textarea',
                'description' => __('Instructions that will be added to the thank you page.', $form_id),
                'default' => __('Instructions for Monri.', $form_id)
            ),
            'thankyou_page' => array(
                'title' => __('Success page', $form_id),
                'type' => 'text',
                'description' => __('Success URL potrebno je kopirati u Monri Account na predviđeno mjesto! ', $form_id),
                'desc_tip' => true,
                'default' => __(wc_get_checkout_url() . get_option('woocommerce_checkout_order_received_endpoint', 'order-received'), $form_id)
            ),
            'callback_url_endpoint' => array(
                'title' => __('Callback URL endpoint', 'wcwcGpg1'),
                'type' => 'text',
                'description' => __('Monri Callback URL endpoint koji će primati POST zahtjev sa Monri Gateway-a.', $form_id),
                'desc_tip' => true,
                'default' => '/monri-callback',
                $form_id,
            ),
            'success_url_override' => array(
                'title' => __('Success URL override', 'wcwcGpg1'),
                'type' => 'text',
                'description' => __('Success URL koji želite koristiti pri svakoj transakciji. (HTTPS)', $form_id),
                'desc_tip' => true,
                'default' => '',
                $form_id,
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'cancel_url_override' => array(
                'title' => __('Cancel URL override', 'wcwcGpg1'),
                'type' => 'text',
                'description' => __('Cancel URL koji želite koristiti pri svakoj transakciji. (HTTPS)', $form_id),
                'desc_tip' => true,
                'default' => '',
                $form_id,
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'callback_url_override' => array(
                'title' => __('Callback URL override', 'wcwcGpg1'),
                'type' => 'text',
                'description' => __('Callback URL koji želite koristiti pri svakoj transakciji. (HTTPS)', $form_id),
                'desc_tip' => true,
                'default' => '',
                $form_id,
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'monri_merchant_key' => array(
                'title' => __('Monri Key', $form_id),
                'type' => 'text',
                'description' => __('', $form_id),
                'desc_tip' => true,
                'default' => __('', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'monri_authenticity_token' => array(
                'title' => __('Monri authenticity token', $form_id),
                'type' => 'text',
                'description' => __('', $form_id),
                'desc_tip' => true,
                'default' => __('', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'monri_ws_pay_form_shop_id' => array(
                'title' => __('Monri WsPay Form ShopId', $form_id),
                'type' => 'text',
                'description' => __('', $form_id),
                'desc_tip' => true,
                'default' => __('', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
            ),
            'monri_ws_pay_form_secret' => array(
                'title' => __('Monri WsPay Form Secret', $form_id),
                'type' => 'text',
                'description' => __('', $form_id),
                'desc_tip' => true,
                'default' => __('', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
            ),
            'monri_ws_pay_form_tokenization_enabled' => array(
                'title' => __('Monri WsPay Form Tokenization Enabled', $form_id),
                'type' => 'checkbox',
                'description' => __('', $form_id),
                'desc_tip' => true,
                'default' => 'no',
                'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
            ),
            'monri_ws_pay_form_tokenization_shop_id' => array(
                'title' => __('Monri WsPay Form Tokenization ShopId', $form_id),
                'type' => 'text',
                'description' => __('', $form_id),
                'desc_tip' => true,
                'default' => __('', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
            ),
            'monri_ws_pay_form_tokenization_secret' => array(
                'title' => __('Monri WsPay Form Tokenization Secret', $form_id),
                'type' => 'text',
                'description' => __('', $form_id),
                'desc_tip' => true,
                'default' => __('', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
            ),
            //            'monri_ws_pay_components_shop_id' => array(
//                'title' => __('Monri WsPay Components ShopId', $form_id),
//                'type' => 'text',
//                'description' => __('', $form_id),
//                'desc_tip' => true,
//                'default' => __('', $form_id),
//                'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
//            ),
//            'monri_ws_pay_components_secret' => array(
//                'title' => __('Monri WsPay Components Secret', $form_id),
//                'type' => 'text',
//                'description' => __('', $form_id),
//                'desc_tip' => true,
//                'default' => __('', $form_id),
//                'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
//            ),
            'monri_web_pay_integration_type' => array(
                'title' => __('Integration type:', $form_id),
                'type' => 'select',
                'class' => 'chosen_select woocommerce-monri-dynamic-option monri-web-pay-option',
                'css' => 'width: 450px;',
                'default' => true,
                'description' => __('', $form_id),
                'options' => $integration_types,
                'desc_tip' => true,
            ),
            'test_mode' => array(
                'title' => __('Test mode enabled:', $form_id),
                'type' => 'select',
                'class' => 'chosen_select',
                'css' => 'width: 450px;',
                'default' => 0,
                'description' => __('', $form_id),
                'options' => $yes_or_no,
                'desc_tip' => true,
            ),
            'transaction_type' => array(
                'title' => __('Transaction type:', $form_id),
                'type' => 'select',
                'class' => 'chosen_select woocommerce-monri-dynamic-option monri-web-pay-option',
                'css' => 'width: 450px;',
                'default' => 0,
                'description' => __('', $form_id),
                'options' => $transaction_type,
                'desc_tip' => true
            ),
            'form_language' => array(
                'title' => __('Form language:', $form_id),
                'type' => 'select',
                'class' => 'chosen_select',
                'css' => 'width: 450px;',
                'default' => 'EN',
                'description' => __('', $form_id),
                'options' => $form_language,
                'desc_tip' => true,
            ),
            'paying_in_installments' => array(
                'title' => __('Allow paying in installments', $form_id),
                'type' => 'select',
                'class' => 'chosen_select woocommerce-monri-dynamic-option monri-web-pay-option',
                'css' => 'width: 450px;',
                'default' => 0,
                'description' => __('', $form_id),
                'options' => $yes_or_no,
                'desc_tip' => true,
            ),
            'number_of_allowed_installments' => array(
                'title' => __('Number of allowed installments', $form_id),
                'type' => 'select',
                'class' => 'chosen_select woocommerce-monri-dynamic-option monri-web-pay-option',
                'css' => 'width: 450px;',
                'default' => 0,
                'description' => __('', $form_id),
                'options' => $number_of_allowed_installments,
                'desc_tip' => true,
            ),
            'bottom_limit' => array(
                'title' => __('Price limit for paying in installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the bottom price limit on which the installments can be used.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_2' => array(
                'title' => __('Price increase when paying in 2 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_3' => array(
                'title' => __('Price increase when paying in 3 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_4' => array(
                'title' => __('Price increase when paying in 4 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_5' => array(
                'title' => __('Price increase when paying in 5 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_6' => array(
                'title' => __('Price increase when paying in 6 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_7' => array(
                'title' => __('Price increase when paying in 7 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_8' => array(
                'title' => __('Price increase when paying in 8 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_9' => array(
                'title' => __('Price increase when paying in 9 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_10' => array(
                'title' => __('Price increase when paying in 10 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_11' => array(
                'title' => __('Price increase when paying in 11 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_12' => array(
                'title' => __('Price increase when paying in 12 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_13' => array(
                'title' => __('Price increase when paying in 13 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_14' => array(
                'title' => __('Price increase when paying in 14 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_15' => array(
                'title' => __('Price increase when paying in 15 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_16' => array(
                'title' => __('Price increase when paying in 16 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_17' => array(
                'title' => __('Price increase when paying in 17 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_18' => array(
                'title' => __('Price increase when paying in 18 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_19' => array(
                'title' => __('Price increase when paying in 19 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_20' => array(
                'title' => __('Price increase when paying in 20 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_21' => array(
                'title' => __('Price increase when paying in 21 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_22' => array(
                'title' => __('Price increase when paying in 22 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_23' => array(
                'title' => __('Price increase when paying in 23 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),
            'price_increase_24' => array(
                'title' => __('Price increase when paying in 24 installments:', $form_id),
                'type' => 'text',
                'description' => __('This controls the price increase when paying with installments.', $form_id),
                'desc_tip' => true,
                'default' => __('0', $form_id),
                'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            )
        );
    }

    public function admin_options()
    {
        echo '<h3>' . __('Monri Payment Gateway', 'Monri') . '</h3>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this->generate_settings_html();
        echo '</table>';
        echo '<script>
    (function () {
        updateOptions(jQuery("#woocommerce_monri_monri_payment_gateway_service").val())
    })()
    
    function updateOptions(value) {
        jQuery(".woocommerce-monri-dynamic-option").parents("tr").hide()
        if (value === "monri-ws-pay") {
            jQuery(\'.woocommerce-monri-dynamic-option.monri-web-pay-option\').parents(\'tr\').hide()
            jQuery(\'.woocommerce-monri-dynamic-option.monri-ws-pay-option\').parents(\'tr\').show()
        } else if (value === "monri-web-pay") {
            jQuery(\'.woocommerce-monri-dynamic-option.monri-web-pay-option\').parents(\'tr\').show()
            jQuery(\'.woocommerce-monri-dynamic-option.monri-ws-pay-option\').parents(\'tr\').hide()
        }
    }
    
    jQuery("#woocommerce_monri_monri_payment_gateway_service").on("change", function (e) {
        updateOptions(e.target.value)
    })
</script>';

    }


    // Check if we are forcing SSL on checkout pages
    // Custom function not required by the Gateway
    public function do_ssl_check()
    {
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                //echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
            }
        }
    }

    /**
     * Process the payment and return the result
     **/
    function process_payment($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);

        $validation = $this->validate_form_fields($order);

        if ($validation["response"] == "false") {
            wc_add_notice($validation["message"], 'error');
            return;
        }

        if ($this->is_components() && $this->is_web_pay()) {
            //Direct integration
            return $this->direct_integration($order_id);
        } else {
            return $this->form_integration($order);
        }
    }

    function is_components()
    {
        if ($this->is_ws_pay()) {
            return false;
        }
        return $this->monri_web_pay_integration_type == 'components';
    }

    /**
     * Validate WooCommerce Form fields
     **/
    function validate_form_fields($order)
    {
        if ($this->form_language == "en") {
            $lang = $this->get_en_translation();
        } elseif ($this->form_language == "ba-hr" || $this->form_language == "hr") {
            $lang = $this->get_ba_hr_translation();
        } elseif ($this->form_language == "sr") {
            $lang = $this->get_sr_translation();
        }


        if (strlen($order->billing_first_name) < 3 || strlen($order->billing_first_name) > 11) {
            $validation['response'] = "false";
            $validation['message'] = $lang['FIRST_NAME_ERROR'];
            return $validation;
        }
        if (strlen($order->billing_last_name) < 3 || strlen($order->billing_last_name) > 18) {
            $validation['response'] = "false";
            $validation['message'] = $lang['LAST_NAME_ERROR'];
            return $validation;
        }
        if (strlen($order->billing_address_1) < 3 || strlen($order->billing_address_1) > 300) {
            $validation['response'] = "false";
            $validation['message'] = $lang['ADDRESS_ERROR'];
            return $validation;
        }
        if (strlen($order->billing_city) < 3 || strlen($order->billing_city) > 30) {
            $validation['response'] = "false";
            $validation['message'] = $lang['CITY_ERROR'];
            return $validation;
        }
        if (strlen($order->billing_postcode) < 3 || strlen($order->billing_postcode) > 9) {
            $validation['response'] = "false";
            $validation['message'] = $lang['ZIP_ERROR'];
            return $validation;
        }
        if (strlen($order->billing_phone) < 3 || strlen($order->billing_phone) > 30) {
            $validation['response'] = "false";
            $validation['message'] = $lang['PHONE_ERROR'];
            return $validation;
        }
        if (strlen($order->billing_email) < 3 || strlen($order->billing_email) > 100) {
            $validation['response'] = "false";
            $validation['message'] = $lang['EMAIL_ERROR'];
            return $validation;
        }

    }

    /**
     *Form integration
     **/
    function receipt_page($order)
    {
        if ($this->form_language == "en") {
            $lang = $this->get_en_translation();
        } elseif ($this->form_language == "ba-hr" || $this->form_language == "hr") {
            $lang = $this->get_ba_hr_translation();
        } elseif ($this->form_language == "sr") {
            $lang = $this->get_sr_translation();
        }
        echo '<p>' . __($lang['RECIEPT_PAGE'], 'Monri') . '</p>';
        echo $this->generate_form($order);
    }

    public function generate_form_ws_pay($order)
    {
        // Check test mode
        if ($this->test_mode) {
            $url = 'https://formtest.wspay.biz';
        } else {
            $url = 'https://form.wspay.biz';
        }

        $req = [];
        $req["shopID"] = $this->api->api_username();
        $req["shoppingCartID"] = $order->get_order_number();
        $amount = number_format($order->order_total, 2, ',', '');
        $req["totalAmount"] = $amount;
        $req["signature"] = $this->createTransactionSignature($this->api->api_password(), $this->api->api_username(), $req["shoppingCartID"], $amount);
        $req['returnURL'] = site_url() . '/ws-pay-redirect';
        // TODO: implement this in a different way
        $req["returnErrorURL"] = WC_Order::get_cancel_endpoint();
        $req["cancelURL"] = WC_Order::get_cancel_endpoint();
        $req["version"] = "2.0";
        $req["customerFirstName"] = $order->billing_first_name;
        $req["customerLastName"] = $order->billing_last_name;
        $req["customerAddress"] = $order->billing_address_1;
        $req["customerCity"] = $order->billing_city;
        $req["customerZIP"] = $order->billing_postcode;
        $req["customerCountry"] = $order->billing_country;
        $req["customerPhone"] = $order->billing_phone;
        $req["customerEmail"] = $order->billing_email;
        // check if user is logged in
        // check if tokenization is enabled on settings
        // TODO: is token request should be depending on if save card for future payments is selected
        if (isset($_POST['ws-pay-tokenized-card'])) {
            $tokenized_card = $_POST['ws-pay-tokenized-card'];
        }

        $payment_with_token = isset($tokenized_card) && !empty($tokenized_card) && $tokenized_card != 'not-selected';
        
        if ($this->api->tokenization_enabled() && !$payment_with_token) {
            $req["IsTokenRequest"] = "1";
        }

        if($payment_with_token) {
            $decoded_card = json_decode(base64_decode($tokenized_card));
            $req['Token'] = $decoded_card[0];
            $req['TokenNumber'] = $decoded_card[1];
        }
        // After successful transaction WSPayForm redirects to ReturnURL as described in Parameters which
        // WSPayForm returns to web shop - ReturnURL with three additional parameters:
        // Token - unique identifier representing payment type for the single user of the web shop
        // TokenNumber – number that corresponds to the last 4 digits of the credit card
        // TokenExp – presenting expiration date of the credit card (YYMM)

        // Payment using token
        // <input type="hidden" name="Token" value="e32c9607-f77d-44d5-98e8-e58c9f279bfd">
        // <input type="hidden" name="TokenNumber" value="0189">

        $response = $this->curlJSON($url . "/api/create-transaction", $req);
        if (isset($response['PaymentFormUrl'])) {
            return $response['PaymentFormUrl'];
        } else {
            // TODO: error
            var_dump($url . "/api/create-transaction");
            var_dump($req);
            var_dump($order);
            var_dump($response);
            return "";
        }
    }

    private function createTransactionSignature($secretKey, $shopId, $shoppingCartId, $totalAmount)
    {
        $amount = preg_replace('~\D~', '', $totalAmount);
        ;
        return hash("sha512", $shopId . $secretKey . $shoppingCartId . $secretKey . $amount . $secretKey);
    }

    /**
     * Generate monri button link
     **/
    public function generate_form($order_id)
    {
        /**
         * Form integration
         **/
        global $woocommerce;

        $order = new WC_Order($order_id);
        $order_info = $order_id . '_' . date("dmy");

        //Check transaction type
        ($this->transaction_type ? $transaction_type = 'authorize' : $transaction_type = 'purchase');

        // Check test mode
        if ($this->test_mode) {
            $live_url = 'https://ipgtest.monri.com/v2/form';
        } else {
            $live_url = 'https://ipg.monri.com/v2/form';
        }

        //Convert order amount to number without decimals
        $order_total = $order->order_total * 100;

        if (version_compare(WOOCOMMERCE_VERSION, '3.0.0', '>=')) {
            $order_data = $order->get_data();
            $currency = $order_data["currency"];
        } else {
            $order_meta = get_post_meta($order_id);
            $currency = $order_meta["_order_currency"][0];
        }

        //Convert currency to match Monri's requested currency
        if ($currency == "KM") {
            $currency = "BAM";
        }

        //Generate digest key
        $digest = hash('sha512', $this->api->api_password() . $order->get_id() . $order_total . $currency);

        //Combine first and last name in one string
        $full_name = $order->billing_first_name . " " . $order->billing_last_name;

        //Array of order information
        $args = array(
            'ch_full_name' => $full_name,
            'ch_address' => $order->billing_address_1,
            'ch_city' => $order->billing_city,
            'ch_zip' => $order->billing_postcode,
            'ch_country' => $order->billing_country,
            'ch_phone' => $order->billing_phone,
            'ch_email' => $order->billing_email,

            'order_info' => $order_info,
            'order_number' => $order->get_order_number(),
            'amount' => $order_total,
            'currency' => $currency,
            'original_amount' => $order->order_total,

            'language' => $this->form_language,
            'transaction_type' => $transaction_type,
            'authenticity_token' => $this->api->api_username(),
            'digest' => $digest

        );

        if ($success_url_override = $this->get_option('success_url_override')) {
            $args['success_url_override'] = $success_url_override;
        }

        if ($cancel_url_override = $this->get_option('cancel_url_override')) {
            $args['cancel_url_override'] = $cancel_url_override;
        }

        if ($callback_url_override = $this->get_option('callback_url_override')) {
            $args['callback_url_override'] = $callback_url_override;
        }

        //Generating input fields with order information that will be sent on monri
        $args_array = array();
        foreach ($args as $key => $value) {
            $args_array[] = "<input type='hidden' name='$key' value='$value'/>";
        }

        //Returning the form
        return '<form action="' . $live_url . '" method="post" data-ajax="false" id="monri_payment_form">
                ' . implode('', $args_array) . '
                <input type="submit" class="button-alt" id="submit_monri_payment_form" value="' . __('Pay via Monri', 'Monri') . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel order &amp; restore cart', 'Monri') . '</a>
                <script type="text/javascript">
    jQuery(function(){
    jQuery("body").block(
            {
                message: "<img src=\"' . plugins_url() . '/woocommerce-monri/assets/images/ajax-loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px; width:50px\" />' . __('Hvala na narudžbi. Sada Vas preusmjeravamo na Monri kako bi završili plaćanje.', 'Monri') . '",
                    overlayCSS:
            {
                background: "#fff",
                    opacity: 0.6
        },
        css: {
            padding:        20,
                textAlign:      "center",
                color:          "#555",
                border:         "3px solid #aaa",
                backgroundColor:"#fff",
                cursor:         "wait",
                lineHeight:"32px"
        }
        });
        jQuery("#submit_monri_payment_form").click();    
    });</script>
                </form>';


    }

    /**
     * In some cases page url is rewritten and it contains page path and query string.
     * @return string
     */
    private function get_query_string()
    {
        $arr = explode("?", $_SERVER['REQUEST_URI']);
        // If there's more than one '?' shift and join with ?, it's special case of having '?' in success url
        // eg http://testiranjeintegracija.net/?page_id=6order-recieved?

        if (count($arr) > 2) {
            array_shift($arr);
            return implode('?', $arr);
        }

        return end($arr);
    }

    /**
     * Check for valid monri server callback
     **/
    public function check_monri_response()
    {
        if ($this->form_language == "en") {
            $lang = $this->get_en_translation();
        } elseif ($this->form_language == "ba-hr" || $this->form_language == "hr") {
            $lang = $this->get_ba_hr_translation();
        } elseif ($this->form_language == "sr") {
            $lang = $this->get_sr_translation();
        } else {
            $lang = $this->get_en_translation();
        }

        global $woocommerce;

        if (isset($_REQUEST['approval_code']) && isset($_REQUEST['digest'])) {
            $this->monri_web_pay_handle_redirect($lang, $woocommerce);
        } else if (isset($_REQUEST['WsPayOrderId']) && isset($_REQUEST['ApprovalCode'])) {
            $this->monri_ws_pay_handle_redirect($lang, $woocommerce);
        }
    }

    function tokenization_requested()
    {
        // It's always the case
        return true;
    }

    // Checks if tokenization is enabled by:
    // - checking if user is logged in
    // - checking if integration is wspay -> in that case it checks boolean flag in options
    // - checking if integration is webpay -> in that case ot's false since tokenization is not enabled for WebPay
    function tokenization_enabled()
    {
        if (isset($this->__tokenization_enabled)) {
            return $this->__tokenization_enabled;
        }
        // Tokenization is only enabled for logged in users
        if (!is_user_logged_in()) {
            $this->__tokenization_enabled = false;
        } else if ($this->is_ws_pay()) {
            $this->__tokenization_enabled = $this->monri_ws_pay_form_tokenization_enabled;
        } else if ($this->is_web_pay()) {
            $this->__tokenization_enabled = false;
        } else {
            $this->__tokenization_enabled = false;
        }

        return $this->__tokenization_enabled;
    }

    /**
     * Check for valid 3dsecure response
     **/
    function check_3dsecure_response()
    {
        if ($this->form_language == "en") {
            $lang = $this->get_en_translation();
        } elseif ($this->form_language == "ba-hr" || $this->form_language == "hr") {
            $lang = $this->get_ba_hr_translation();
        } elseif ($this->form_language == "sr") {
            $lang = $this->get_sr_translation();
        }

        if (isset($_POST['PaRes'])) {
            $resultXml = $this->handle3dsReturn($_POST);

            if (isset($resultXml->status) && $resultXml->status == "approved") {
                global $woocommerce;

                $resultXml = (array) $resultXml;
                $order = new WC_Order($resultXml["order-number"]);

                // Payment has been successful
                $order->add_order_note(__($lang['PAYMENT_COMPLETED'], 'monri'));

                // Mark order as Paid
                $order->payment_complete();

                // Empty the cart (Very important step)
                $woocommerce->cart->empty_cart();

            } else {
                $order = new WC_Order($resultXml["order-number"]);

                $this->order_failed($order);
            }

        }
    }

    function showMessage($content)
    {
        return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
    }

    /**
     * Direct integration
     **/
    function direct_integration($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);
        $card_installments = $_POST['monri-card-installments'];
        $monri_token = $_POST['monri-token'];


        $validation = $this->monri_token_validation($monri_token);

        if ($validation["response"] == "false") {
            wc_add_notice($validation["message"], 'error');
            return;
        }

        //Check transaction type
        ($this->transaction_type ? $transaction_type = 'authorize' : $transaction_type = 'purchase');

        $order_info = $order_id . '_' . date("dmy");

        $amount = $order->order_total;
        $number_of_installments = intval($card_installments);

        //Check if paying in installments, if yes set transaction_type to purchase
        if ($number_of_installments > 1) {
            $transaction_type = 'purchase';
        }


        if ($number_of_installments === 2) {
            if ($this->price_increase_2 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_2 / 100);
        } elseif ($number_of_installments === 3) {
            if ($this->price_increase_3 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_3 / 100);
        } elseif ($number_of_installments === 4) {
            if ($this->price_increase_4 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_4 / 100);
        } elseif ($number_of_installments === 5) {
            if ($this->price_increase_5 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_5 / 100);
        } elseif ($number_of_installments === 6) {
            if ($this->price_increase_6 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_6 / 100);
        } elseif ($number_of_installments === 7) {
            if ($this->price_increase_7 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_7 / 100);
        } elseif ($number_of_installments === 8) {
            if ($this->price_increase_8 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_8 / 100);
        } elseif ($number_of_installments === 9) {
            if ($this->price_increase_9 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_9 / 100);
        } elseif ($number_of_installments === 10) {
            if ($this->price_increase_10 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_10 / 100);
        } elseif ($number_of_installments === 11) {
            if ($this->price_increase_11 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_11 / 100);
        } elseif ($number_of_installments === 12) {
            if ($this->price_increase_12 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_12 / 100);
        } elseif ($number_of_installments === 13) {
            if ($this->price_increase_13 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_13 / 100);
        } elseif ($number_of_installments === 14) {
            if ($this->price_increase_14 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_14 / 100);
        } elseif ($number_of_installments === 15) {
            if ($this->price_increase_15 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_15 / 100);
        } elseif ($number_of_installments === 16) {
            if ($this->price_increase_16 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_16 / 100);
        } elseif ($number_of_installments === 17) {
            if ($this->price_increase_17 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_17 / 100);
        } elseif ($number_of_installments === 18) {
            if ($this->price_increase_18 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_18 / 100);
        } elseif ($number_of_installments === 19) {
            if ($this->price_increase_19 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_19 / 100);
        } elseif ($number_of_installments === 20) {
            if ($this->price_increase_20 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_20 / 100);
        } elseif ($number_of_installments === 21) {
            if ($this->price_increase_21 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_21 / 100);
        } elseif ($number_of_installments === 22) {
            if ($this->price_increase_22 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_22 / 100);
        } elseif ($number_of_installments === 23) {
            if ($this->price_increase_23 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_23 / 100);
        } elseif ($number_of_installments === 24) {
            if ($this->price_increase_24 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_24 / 100);
        }

        //Convert order amount to number without decimals
        $amount = ceil($amount * 100);

        if (version_compare(WOOCOMMERCE_VERSION, '3.0.0', '>=')) {
            $order_data = $order->get_data();
            $currency = $order_data["currency"];
        } else {
            $order_meta = get_post_meta($order_id);
            $currency = $order_meta["_order_currency"][0];
        }

        if ($currency == "KM") {
            $currency = "BAM";
        }

        //Generate digest key
        $digest = hash('sha512', $this->api->api_password() . $order->get_id() . $amount . $currency);

        //Array of order information
        $order_number = $order->get_id();
        $params = array(
            'ch_full_name' => $order->billing_first_name . " " . $order->billing_last_name,
            'ch_address' => $order->billing_address_1,
            'ch_city' => $order->billing_city,
            'ch_zip' => $order->billing_postcode,
            'ch_country' => $order->billing_country,
            'ch_phone' => $order->billing_phone,
            'ch_email' => $order->billing_email,

            /* Monri components integrated, this parameters are no longer needed.
            'pan' => $card_number,
            'cvv' => $card_cvv,
            'expiration_date' => $card_expiry,*/

            'order_info' => $order_info,
            'order_number' => $order_number,
            'amount' => $amount,
            'currency' => $currency,

            'ip' => $_SERVER['REMOTE_ADDR'],
            'language' => $this->form_language,
            'transaction_type' => $transaction_type,
            'authenticity_token' => $this->api->api_username(),
            'digest' => $digest,
            'temp_card_id' => $monri_token,
        );

        if ($card_installments > 1) {
            $params['number_of_installments'] = $card_installments;
        }

        if ($transaction_type == "authorize") {
            $resultJSON = $this->authorize($params);
        } else {
            $resultJSON = $this->purchase($params);
        }

        if ($this->form_language == "en") {
            $lang = $this->get_en_translation();
        } elseif ($this->form_language == "ba-hr" || $this->form_language == "hr") {
            $lang = $this->get_ba_hr_translation();
        } elseif ($this->form_language == "sr") {
            $lang = $this->get_sr_translation();
        }

        //check if cc have 3Dsecure validation
        if (isset($resultJSON['secure_message'])) {
            //this is 3dsecure card
            //show user 3d secure form the
            $result = $resultJSON['secure_message'];

            $payment_token = $this->base64url_encode(json_encode([$result['authenticity_token'], $order_number, $this->settings['thankyou_page']]));

            $urlEncode = array(
                "acsUrl" => $result['acs_url'],
                "pareq" => $result['pareq'],
                "returnUrl" => site_url() . '/monri-3ds-payment-result?payment_token=' . $payment_token,
                "token" => $result['authenticity_token']
            );

            $params_3ds = http_build_query($urlEncode);
            $redirect = plugins_url() . "/woocommerce-monri/3dsecure.php?" . $params_3ds;

            return array(
                'result' => 'success',
                'redirect' => $redirect,
            );

        } elseif (isset($resultJSON['transaction']) && $resultJSON['transaction']['status'] == "approved") {

            global $woocommerce;
            $transactionResult = $resultJSON['transaction'];

            $order = new WC_Order($transactionResult["order-number"]);

            //Payment has been successful
            $order->add_order_note(__($lang['PAYMENT_COMPLETED'], 'monri'));
            $monri_order_amount1 = $transactionResult['amount'] / 100;
            $monri_order_amount2 = number_format($monri_order_amount1, 2);
            if ($monri_order_amount2 != $order->total) {
                $order->add_order_note($lang['MONRI_ORDER_AMOUNT'] . ": " . $monri_order_amount2, true);
            }
            if ($params['number_of_installments'] > 1) {
                $order->add_order_note($lang['NUMBER_OF_INSTALLMENTS'] . ": " . $params['number_of_installments']);
            }

            // Mark order as Paid
            $order->payment_complete();

            // Empty the cart (Very important step)
            $woocommerce->cart->empty_cart();

            // Redirect to thank you page
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            );
        } else {
            //nope
            wc_add_notice($lang['TRANSACTION_FAILED'], 'error');
            return false;
        }

    }

    function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }


    function monri_token_validation($monri_token)
    {
        if ($this->form_language == "en") {
            $lang = $this->get_en_translation();
        } elseif ($this->form_language == "ba-hr" || $this->form_language == "hr") {
            $lang = $this->get_ba_hr_translation();
        } elseif ($this->form_language == "sr") {
            $lang = $this->get_sr_translation();
        }

        if (empty($monri_token)) {
            $validation['response'] = "false";
            $validation['message'] = $lang['TRANSACTION_FAILED'];
            return $validation;
        }
    }

    public function payment_fields()
    {

        if($this->is_form_integration()) {
            ?>
            <!-- TODO: i18n -->
            <div class=""><p>Odaberite karticu</p></div>
            <pre><?php 
            // TODO: fetch for shop id
            // TODO: fetch only non expired cards
            $tokenized_cards = $this->api->get_tokenized_cards_for_current_user();
            if(count($tokenized_cards) == 0) {
                return;
            }
            echo "<select name='ws-pay-tokenized-card'>";
            echo "<option value='not-selected'>Odaberite</option>";
            foreach($tokenized_cards as $card) {
                $base_64 = base64_encode(json_encode([$card['token'], $card['token_number']]));
                echo "<option value='".$base_64."' data-token=number='".$card['token_number']."'>".$card['token_number']."</option>";
            }
            echo "</select>";
            ?></pre>
            <?php
            return;
        } 

        if ($this->form_language == "en") {
            $lang = $this->get_en_translation();
        } elseif ($this->form_language == "ba-hr" || $this->form_language == "hr") {
            $lang = $this->get_ba_hr_translation();
        } elseif ($this->form_language == "sr") {
            $lang = $this->get_sr_translation();
        }

        if ($this->is_form_integration()) {
            if ($this->description)
                echo wpautop(wptexturize($this->description));
        } else {

            $this->credit_card_script();

            $default_args = array(
                'fields_have_names' => true, // Some gateways like stripe don't need names as the form is tokenized
            );

            $args = array();
            $args = wp_parse_args($args, apply_filters('woocommerce_credit_card_form_args', $default_args, $this->id));

            $order_total = $this->get_order_total();

            $price_increase_message = "<span id='price-increase-1' class='price-increase-message' style='display: none; color: red;'></span>";

            if ($this->price_increase_2 != 0) {
                $amount2 = $order_total + ($order_total * $this->price_increase_2 / 100);
                $price_increase_message .= "<span id='price-increase-2' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_2 . "% = " . $amount2 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-2' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_3 != 0) {
                $amount3 = $order_total + ($order_total * $this->price_increase_3 / 100);
                $price_increase_message .= "<span id='price-increase-3' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_3 . "% = " . $amount3 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-3' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_4 != 0) {
                $amount4 = $order_total + ($order_total * $this->price_increase_4 / 100);
                $price_increase_message .= "<span id='price-increase-4' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_4 . "% = " . $amount4 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-4' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_5 != 0) {
                $amount5 = $order_total + ($order_total * $this->price_increase_5 / 100);
                $price_increase_message .= "<span id='price-increase-5' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_5 . "% = " . $amount5 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-5' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_6 != 0) {
                $amount6 = $order_total + ($order_total * $this->price_increase_6 / 100);
                $price_increase_message .= "<span id='price-increase-6' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_6 . "% = " . $amount6 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-6' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_7 != 0) {
                $amount7 = $order_total + ($order_total * $this->price_increase_7 / 100);
                $price_increase_message .= "<span id='price-increase-7' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_7 . "% = " . $amount7 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-7' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_8 != 0) {
                $amount8 = $order_total + ($order_total * $this->price_increase_8 / 100);
                $price_increase_message .= "<span id='price-increase-8' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_8 . "% = " . $amount8 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-8' class='price-increase-message' style='display: none; color: red;'></span>";
            }
            if ($this->price_increase_9 != 0) {
                $amount9 = $order_total + ($order_total * $this->price_increase_9 / 100);
                $price_increase_message .= "<span id='price-increase-9' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_9 . "% = " . $amount9 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-9' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_10 != 0) {
                $amount10 = $order_total + ($order_total * $this->price_increase_10 / 100);
                $price_increase_message .= "<span id='price-increase-10' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_10 . "% = " . $amount10 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-10' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_11 != 0) {
                $amount11 = $order_total + ($order_total * $this->price_increase_11 / 100);
                $price_increase_message .= "<span id='price-increase-11' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_11 . "% = " . $amount11 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-11' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_12 != 0) {
                $amount12 = $order_total + ($order_total * $this->price_increase_12 / 100);
                $price_increase_message .= "<span id='price-increase-12' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_12 . "% = " . $amount12 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-12' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_13 != 0) {
                $amount13 = $order_total + ($order_total * $this->price_increase_13 / 100);
                $price_increase_message .= "<span id='price-increase-13' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_13 . "% = " . $amount13 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-13' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_14 != 0) {
                $amount14 = $order_total + ($order_total * $this->price_increase_14 / 100);
                $price_increase_message .= "<span id='price-increase-14' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_14 . "% = " . $amount14 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-14' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_15 != 0) {
                $amount15 = $order_total + ($order_total * $this->price_increase_15 / 100);
                $price_increase_message .= "<span id='price-increase-15' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_15 . "% = " . $amount15 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-15' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_16 != 0) {
                $amount16 = $order_total + ($order_total * $this->price_increase_16 / 100);
                $price_increase_message .= "<span id='price-increase-16' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_16 . "% = " . $amount16 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-16' class='price-increase-message' style='display: none; color: red;'></span>";
            }


            if ($this->price_increase_17 != 0) {
                $amount17 = $order_total + ($order_total * $this->price_increase_17 / 100);
                $price_increase_message .= "<span id='price-increase-17' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_17 . "% = " . $amount17 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-17' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_18 != 0) {
                $amount18 = $order_total + ($order_total * $this->price_increase_18 / 100);
                $price_increase_message .= "<span id='price-increase-18' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_18 . "% = " . $amount18 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-18' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_19 != 0) {
                $amount19 = $order_total + ($order_total * $this->price_increase_19 / 100);
                $price_increase_message .= "<span id='price-increase-19' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_19 . "% = " . $amount19 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-19' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_20 != 0) {
                $amount20 = $order_total + ($order_total * $this->price_increase_20 / 100);
                $price_increase_message .= "<span id='price-increase-20' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_20 . "% = " . $amount20 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-20' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_21 != 0) {
                $amount21 = $order_total + ($order_total * $this->price_increase_21 / 100);
                $price_increase_message .= "<span id='price-increase-21' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_21 . "% = " . $amount21 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-21' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_22 != 0) {
                $amount22 = $order_total + ($order_total * $this->price_increase_22 / 100);
                $price_increase_message .= "<span id='price-increase-22' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_22 . "% = " . $amount22 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-22' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_23 != 0) {
                $amount23 = $order_total + ($order_total * $this->price_increase_23 / 100);
                $price_increase_message .= "<span id='price-increase-23' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_23 . "% = " . $amount23 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-23' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->price_increase_24 != 0) {
                $amount24 = $order_total + ($order_total * $this->price_increase_24 / 100);
                $price_increase_message .= "<span id='price-increase-24' class='price-increase-message' style='display: none; color: red;'> " . $lang["PAYMENT_INCREASE"] . " " . $this->price_increase_24 . "% = " . $amount24 . "</span>";
            } else {
                $price_increase_message .= "<span id='price-increase-24' class='price-increase-message' style='display: none; color: red;'></span>";
            }

            if ($this->paying_in_installments and $order_total >= $this->bottom_limit) {
                if ($this->number_of_allowed_installments == 24) {
                    $options_string = '<option value="1">1</option>
                              <option value="2">2</option>
                              <option value="3">3</option>
                              <option value="4">4</option>
                              <option value="5">5</option>
                              <option value="6">6</option>
                              <option value="7">7</option>
                              <option value="8">8</option>
                              <option value="9">9</option>
                              <option value="10">10</option>
                              <option value="11">11</option>
                              <option value="12">12</option>
                              <option value="13">13</option>
                              <option value="14">14</option>
                              <option value="15">15</option>
                              <option value="16">16</option>
                              <option value="17">17</option>
                              <option value="18">18</option>
                              <option value="19">19</option>
                              <option value="20">20</option>
                              <option value="21">21</option>
                              <option value="22">22</option>
                              <option value="23">23</option>
                              <option value="24">24</option>';
                } else {
                    if ($this->number_of_allowed_installments == 12) {
                        $options_string = '<option value="1">1</option>
                                  <option value="2">2</option>
                                  <option value="3">3</option>
                                  <option value="4">4</option>
                                  <option value="5">5</option>
                                  <option value="6">6</option>
                                  <option value="7">7</option>
                                  <option value="8">8</option>
                                  <option value="9">9</option>
                                  <option value="10">10</option>
                                  <option value="11">11</option>
                                  <option value="12">12</option>';
                    } else {
                        $options_string = '<option value="1">1</option>
                                  <option value="2">2</option>
                                  <option value="3">3</option>
                                  <option value="4">4</option>
                                  <option value="5">5</option>
                                  <option value="6">6</option>';
                    }
                }

                // No longer needed, Morni components JS plugin integrated
                $default_fields = array(
                    'card-installments' => '<p id="monri-card-installments-p" style="display: block; float: left;" class="form-row form-row-wide">
                                  <label for="' . esc_attr($this->id) . '-card-installments">' . $lang['INSTALLMENTS_NUMBER'] . '</label>
                                  <select id="' . esc_attr($this->id) . '-card-installments" class="input-text wc-credit-card-form-card-cvc"  name="' . ($args['fields_have_names'] ? $this->id . '-card-installments' : '') . '">
                                    ' . $options_string
                    . '</select>' . $price_increase_message

                );
            } else {
                $default_fields = array();
            }

            $radnomToken = wp_generate_uuid4();
            $timestamp = (new DateTime())->format('c');
            $digest = hash('SHA512', $this->api->api_password() . $radnomToken . '' . $timestamp . '');


            ?>

            <?php echo isset($default_fields['card-installments']) ? $default_fields['card-installments'] : ''; ?>

            <div id="<?php echo $this->id; ?>">
                <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>

                <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
            </div>

            <script type="text/javascript">

                jQuery('#' + '<?php echo $this->id; ?>').ready(function () {

                    var monri = Monri('<?php echo $this->api->api_username() ?>');
                    var components = monri.components("<?php echo $radnomToken ?>", "<?php echo $digest ?>", '<?php echo $timestamp ?>');

                    var style = {
                        invalid: {
                            color: 'red'
                        },

                    };
                    // Add an instance of the card Component into the `card-element` <div>.
                    var card = components.create('card', { style: style });
                    card.mount('<?php echo $this->id; ?>');


                    jQuery('form.checkout').on('checkout_place_order', function () {
                        // If the Monri radio button is checked, handle Monri token
                        if (jQuery('input#payment_method_monri').is(':checked')) {

                            if (jQuery('#monri-token').length == 0) {
                                // If monri-token element could not be found add it to the form and set its value to 'not-set'.
                                var hiddenInput = document.createElement('input');
                                hiddenInput.setAttribute('type', 'hidden');
                                hiddenInput.setAttribute('name', 'monri-token');
                                hiddenInput.setAttribute('id', 'monri-token');
                                hiddenInput.setAttribute('value', 'not-set');
                                jQuery(this).append(hiddenInput);
                            }


                            if (jQuery('#monri-token').val() == 'not-set') {

                                monri.createToken(card).then(function (result) {
                                    if (result.error) {
                                        // Inform the customer that there was an error.
                                        var errorElement = document.getElementById('card-errors');
                                        errorElement.textContent = result.error.message;

                                    } else {
                                        monriTokenHandler(result.result);
                                    }
                                });


                                function monriTokenHandler(token) {

                                    // Insert the token ID into the form so it gets submitted to the server
                                    jQuery('#monri-token').val(token.id);

                                }

                            }
                        } else {
                            // If the Monri radio button is not checked, delete the errors and the Monri token
                            var displayError = document.getElementById('card-errors');
                            displayError.textContent = '';
                            jQuery('#monri-token').remove();
                        }
                    });

                    jQuery(document.body).on('checkout_error', function () {
                        // Trigger the submit of the checkout form If the thrown wc error is 'set_monri_token_notice' and
                        // no error was returend by  monri.createToken. Else remove the 'monri token' html elemente so a new one can be generated on the next form submit.
                        var error_text = jQuery('.woocommerce-error').find('li').first().text();

                        if (error_text.trim() == 'set_monri_token_notice') {

                            jQuery('.woocommerce-error').remove();

                            if (jQuery('#card-errors').html() == '') {
                                jQuery('#place_order').trigger('click');
                            }

                        }
                    });

                    card.onChange(function (event) {
                        // If monri.createToken returned and error show it to the user.
                        var displayError = document.getElementById('card-errors');
                        if (event.error) {
                            displayError.textContent = event.error.message;
                            jQuery('#monri-token').remove();
                        } else {
                            displayError.textContent = '';
                        }
                    });


                });
            </script>
            <div id="card-errors" class="" role="alert"></div>
            <?php

        }


    }

    function credit_card_script()
    {
        if ($this->test_mode) {
            $live_url = 'https://ipgtest.monri.com/dist/components.js';
        } else {
            $live_url = 'https://ipg.monri.com/dist/components.js';
        }
        wp_register_script('installments', plugin_dir_url(__FILE__) . 'assets/js/installments.js', array('jquery'), '1', true);
        wp_register_script('monri-components', $live_url, array('jquery'), '1', true);
        wp_enqueue_script('installments');
        wp_enqueue_script('monri-components');
        wp_enqueue_script('wc-credit-card-form');
    }

    /**
     * Send purchase request and return the response.
     * Purchases don't need to be approved, funds are transfered in the next settlement between issuer
     * and acquirer banks, usually within one business day. These transactions can be refunded within 180 days.
     *
     * @param arra $params
     * @return
     */
    public function purchase($params)
    {
        //$xml = $this->generateXml('purchase', $params);

        // check test mode
        if ($this->test_mode) {
            $live_url = 'https://ipgtest.monri.com/v2/transaction';
        } else {
            $live_url = 'https://ipg.monri.com/v2/transaction';
        }

        return $this->curlJSON($live_url, ['transaction' => $params]);
        //return $this->curl($live_url, $xml);
    }

    /**
     * Send purchase request and return the response
     * Authorization is a preferred transaction type for e-commerce. Merchant must capture
     * these transactions within 28 days in order to transfer the money from buyer's account to his own.
     * This transaction can also be voided if buyer cancel the order. Refund can be done after original authorization is captured.
     *
     * @param array $params
     * @return array
     */
    public function authorize($params)
    {
        // check test mode
        if ($this->test_mode) {
            $live_url = 'https://ipgtest.monri.com/v2/transaction';
        } else {
            $live_url = 'https://ipg.monri.com/v2/transaction';
        }

        return $this->curlJSON($live_url, ['transaction' => $params]);
    }


    public function handle3dsReturn($post)
    {
        $xml = "<?xml version='1.0' encoding='UTF-8'?>
                <secure-message>              
                  <MD>{$post['MD']}</MD>
                  <PaRes>{$post['PaRes']}</PaRes>
                </secure-message>";

        // check test mode
        if ($this->test_mode) {
            $live_url = 'https://ipgtest.monri.com/pares';
        } else {
            $live_url = 'https://ipg.monri.com/pares';
        }
        return $this->curl($live_url, $xml);
    }

    /**
     * Send POST request to $url with $xml as a field
     *
     * @param string $xml
     * @return SimpleXmlElement|boolean xml response if the request is a successs, false if not
     */
    protected function curl($url, $xml)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/xml',
            'Content-Type: application/xml'
        ]);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, defined('CURL_SSLVERSION_TLSv1') ? CURL_SSLVERSION_TLSv1 : 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        if (!is_callable('curl_exec')) {
            throw new Exception('cURL extension must be enabled on your server to use this module.', 500);
        }

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            //curl_error($curl);
            return false;
        } else {
            curl_close($ch);
        }

        try {
            $resultXml = new \SimpleXmlElement($result);

        } catch (\Exception $e) {
            echo '<pre>'; // for formating
            die(var_export("XML parsing failed. Result: \n $result"));
        }

        if ($resultXml !== false) {
            return $resultXml;
        } else {
            return false;
        }
    }


    /**
     * Send POST request to $url with $params as a field
     *
     * @param string $url
     * @param array $params
     * @return array
     */
    protected function curlJSON($url, $params)
    {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);


        $payload = json_encode($params);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, defined('CURL_SSLVERSION_TLSv1') ? CURL_SSLVERSION_TLSv1 : 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $result = curl_error($ch);
        } else {
            curl_close($ch);
        }


        return json_decode($result, true);
    }


    /**
     * @param array $lang
     * @param $woocommerce
     * @return void
     */
    public function monri_web_pay_handle_redirect($lang, $woocommerce)
    {
        wp_enqueue_style('thankyou-page', plugins_url() . '/woocommerce-monri/assets/style/thankyou-page.css');
        $order_id = $_REQUEST['order_number'];

        if ($order_id != '') {
            try {
                $order = new WC_Order($order_id);

                $digest = $_REQUEST['digest'];
                $response_code = $_REQUEST['response_code'];

                $url = strtok($this->thankyou_page, '?');

                $query_string = $this->get_query_string();
                $full_url = $url . '?' . $query_string;

                $calculated_url = preg_replace('/&digest=[^&]*/', '', $full_url);
                //Generate digest
                $check_digest = hash('sha512', $this->api->api_password() . $calculated_url);
                $trx_authorized = false;
                if ($order->status !== 'completed') {
                    if ($digest == $check_digest) {
                        if ($response_code == "0000") {
                            $trx_authorized = true;
                            $this->msg['message'] = $lang["THANK_YOU_SUCCESS"];
                            $this->msg['class'] = 'woocommerce_message';

                            if ($order->status == 'processing') {

                            } else {
                                $order->payment_complete();
                                $order->add_order_note($lang["MONRI_SUCCESS"] . $_REQUEST['approval_code']);
                                $order->add_order_note($this->msg['message']);
                                $order->add_order_note("Issuer: " . $_REQUEST['issuer']);
                                if ($_REQUEST['number_of_installments'] > 1) {
                                    $order->add_order_note($lang['NUMBER_OF_INSTALLMENTS'] . ": " . $_REQUEST['number_of_installments']);
                                }
                                $woocommerce->cart->empty_cart();
                            }
                        } else if ($response_code == "pending") {
                            $this->msg['message'] = $lang["THANK_YOU_PENDING"];
                            $this->msg['class'] = 'woocommerce_message woocommerce_message_info';
                            $order->add_order_note($lang['MONRI_PENDING'] . $_REQUEST['approval_code']);
                            $order->add_order_note($this->msg['message']);
                            $order->add_order_note("Issuer: " . $_REQUEST['issuer']);
                            if ($_REQUEST['number_of_installments'] > 1) {
                                $order->add_order_note($lang['NUMBER_OF_INSTALLMENTS'] . ": " . $_REQUEST['number_of_installments']);
                            }
                            $order->update_status('on-hold');
                            $woocommerce->cart->empty_cart();
                        } else {
                            $this->msg['class'] = 'woocommerce_error';
                            $this->msg['message'] = $lang['THANK_YOU_DECLINED'];
                            $order->add_order_note($lang['THANK_YOU_DECLINED_NOTE'] . $_REQUEST['Error']);
                        }
                    } else {
                        $this->security_error($lang);

                    }
                    if ($trx_authorized == false) {
                        $this->order_failed($order);
                    }

                    add_action('the_content', array(&$this, 'showMessage'));
                }
            } catch (Exception $e) {
                // $errorOccurred = true;
                $msg = "Error";
            }
        }
    }

    /**
     * @param array $lang
     * @param $woocommerce
     * @return void
     */
    public function monri_ws_pay_handle_redirect($lang, $woocommerce)
    {
        wp_enqueue_style('thankyou-page', plugins_url() . '/woocommerce-monri/assets/style/thankyou-page.css');
        $order_id = $_REQUEST['ShoppingCartID'];

        if ($order_id != '') {
            try {
                $order = new WC_Order($order_id);

                if ($order->status === 'completed') {
                    $this->msg['message'] = $lang["THANK_YOU_SUCCESS"];
                    $this->msg['class'] = 'woocommerce_message';
                } else {
                    $digest = $_REQUEST['Signature'];
                    $success = isset($_REQUEST['Success']) ? $_REQUEST['Success'] : '0';
                    $approval_code = isset($_REQUEST['ApprovalCode']) ? $_REQUEST['ApprovalCode'] : null;
                    $shop_id = $this->api->api_username();
                    $secret_key = $this->api->api_password();
                    // ShopID
                    // SecretKey
                    // ShoppingCartID
                    // SecretKey
                    // Success
                    // SecretKey
                    // ApprovalCode
                    // SecretKey
                    $digest_parts = array(
                        $shop_id,
                        $secret_key,
                        $order_id,
                        $secret_key,
                        $success,
                        $secret_key,
                        $approval_code,
                        $secret_key,
                    );
                    $check_digest = hash('sha512', join("", $digest_parts));
                    if ($check_digest != $digest) {
                        $this->security_error($lang);
                    } else {
                        $trx_authorized = $success == '1' && !empty($approval_code);
                        if ($trx_authorized) {
                            $this->msg['message'] = $lang["THANK_YOU_SUCCESS"];
                            $this->msg['class'] = 'woocommerce_message';
                            $order->payment_complete();
                            $order->add_order_note($lang["MONRI_SUCCESS"] . $_REQUEST['approval_code']);
                            $order->add_order_note($this->msg['message']);
                            $woocommerce->cart->empty_cart();
                        } else {
                            $this->msg['class'] = 'woocommerce_error';
                            $this->msg['message'] = $lang['THANK_YOU_DECLINED'];
                            $this->order_failed($order);
                        }
                    }
                }
            } catch (Exception $e) {
                // $errorOccurred = true;
                $msg = "Error";
            }
        } else {
            $this->msg['class'] = 'woocommerce_error';
            $this->msg['message'] = $lang['THANK_YOU_DECLINED'];
        }

        add_action('the_content', array(&$this, 'showMessage'));
    }

    /**
     * @param WC_Order $order
     * @return void
     */
    public function order_failed(WC_Order $order)
    {
        $order->update_status('failed');
        $order->add_order_note('Failed');
        $order->add_order_note($this->msg['message']);
    }

    /**
     * @param $lang
     * @return void
     */
    public function security_error($lang)
    {
        $this->msg['class'] = 'error';
        $this->msg['message'] = $lang['SECURITY_ERROR'];
    }

    /*
   ------------------
   Language: English
   ------------------
   */
    public static function get_en_translation()
    {
        return MonriI18n::get_en_translation();
    }

    /*
    ------------------
    Language: Bosanski/Hrvatski
    ------------------
    */
    public static function get_ba_hr_translation()
    {
        return MonriI18n::get_ba_hr_translation();
    }


    /*
   ------------------
   Language: Srpski
   ------------------
   */
    public static function get_sr_translation()
    {
        return MonriI18n::get_sr_translation();
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    public function form_integration(WC_Order $order)
    {
        if ($this->is_ws_pay()) {
            return array(
                'result' => 'success',
                'redirect' => $this->generate_form_ws_pay($order)
            );
        } else {
            // Form integration
            return array(
                'result' => 'success',
                'redirect' => add_query_arg(
                    'order',
                    $order->get_id(),
                    add_query_arg('key', $order->order_key, wc_get_checkout_url())
                )
            );
        }
    }

    private function is_form_integration()
    {
        if ($this->is_ws_pay()) {
            return true;
        }
        return $this->monri_web_pay_integration_type == 'form';
    }

    private function is_ws_pay()
    {
        return $this->payment_gateway_service == 'monri-ws-pay';
    }

    private function is_web_pay()
    {
        return $this->payment_gateway_service == 'monri-web-pay';
    }
}

?>