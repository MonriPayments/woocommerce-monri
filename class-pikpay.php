<?php

/* PikPay Payment Gateway Class */

class WC_PikPay extends WC_Payment_Gateway {
    // Setup our Gateway's id, description and other values
    function __construct() {

        // The global ID for this Payment method
        $this->id = "pikpay";

        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = __( "PikPay", 'pikpay' );

        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = __( "PikPay Payment Gateway Plug-in for WooCommerce", 'pikpay' );

        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = __( "PikPay", 'pikpay' );

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
        foreach ( $this->settings as $setting_key => $value )
        {
            $this->$setting_key = $value;
        }

        // Define user set variables.
        $this->title                           = $this->settings['title'];
        $this->description                     = $this->settings['description'];
        $this->thankyou_page                   = $this->settings['thankyou_page'];
        $this->instructions                    = $this->get_option( 'instructions' );
        $this->pikpaykey                       = $this->get_option( 'pikpaykey' );
        $this->pikpayauthtoken                 = $this->get_option( 'pikpayauthtoken' );
        $this->pickpay_methods                 = $this->get_option( 'pickpay_methods', array() );
        $this->payment_processor               = $this->get_option('payment_processor', array());
        $this->test_mode                       = $this->get_option('test_mode', array());
        $this->transaction_type                = $this->get_option('transaction_type', array());
        $this->form_language                   = $this->get_option('form_language', array());
        $this->paying_in_installments          = $this->get_option('paying_in_installments', array());
        $this->number_of_allowed_installments  = $this->get_option('number_of_allowed_installments', array());
        $this->bottom_limit                    = $this->get_option('bottom_limit', array());
        $this->price_increase_2                = $this->get_option('price_increase_2', array());
        $this->price_increase_3                = $this->get_option('price_increase_3', array());
        $this->price_increase_4                = $this->get_option('price_increase_4', array());
        $this->price_increase_5                = $this->get_option('price_increase_5', array());
        $this->price_increase_6                = $this->get_option('price_increase_6', array());
        $this->price_increase_7                = $this->get_option('price_increase_7', array());
        $this->price_increase_8                = $this->get_option('price_increase_8', array());
        $this->price_increase_9                = $this->get_option('price_increase_9', array());
        $this->price_increase_10               = $this->get_option('price_increase_10', array());
        $this->price_increase_11               = $this->get_option('price_increase_11', array());
        $this->price_increase_12               = $this->get_option('price_increase_12', array());
        $this->price_increase_13               = $this->get_option('price_increase_13', array());
        $this->price_increase_14               = $this->get_option('price_increase_14', array());
        $this->price_increase_15               = $this->get_option('price_increase_15', array());
        $this->price_increase_16               = $this->get_option('price_increase_16', array());
        $this->price_increase_17               = $this->get_option('price_increase_17', array());
        $this->price_increase_18               = $this->get_option('price_increase_18', array());
        $this->price_increase_19               = $this->get_option('price_increase_19', array());
        $this->price_increase_20               = $this->get_option('price_increase_20', array());
        $this->price_increase_21               = $this->get_option('price_increase_21', array());
        $this->price_increase_22               = $this->get_option('price_increase_22', array());
        $this->price_increase_23               = $this->get_option('price_increase_23', array());
        $this->price_increase_24               = $this->get_option('price_increase_24', array());

        $this->msg['message'] = "";
        $this->msg['class'] = "";

        //add_option('woocommerce_pay_page_id', $page_id);
        
        // Lets check for SSL
        add_action( 'admin_notices', array( $this,  'do_ssl_check' ) );     
        
        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
         } else {
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        }

        if(!$this->pickpay_methods){
            $this->check_pikpay_response();
            add_action('woocommerce_receipt_pikpay', array(&$this, 'receipt_page'));
            $this->has_fields = false;   
        }
        else{
            $this->has_fields = true;     
            $this->check_3dsecure_response();
        }
        
        // Delete monri log file
        $log_file = "/var/sentora/hostdata/admin/public_html/wp-content/plugins/woocommerce-monri/log.txt";  
   
        // Use unlink() function to delete a file  
        @unlink($log_file);

    } // End __construct()

    function init_form_fields()
    {
       $pickpay_methods = array(
            "0" => 'No',
            "1" => 'Yes'
         );

        $transaction_type = array(
            "0" => "Purchase",
            "1" => "Authorize"
        );

        $number_of_allowed_installments = array(
            "24" => "24",
            "12" => "12",
            "6"  => "6"
        );

        $form_language = array(
            "en" => "English",
            "ba-hr" => "Bosanski",
            "hr" => "Hrvatski",
            "sr" => "Srpski"
        );
            
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'wcwcCpg1' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Monri', 'wcwcCpg1' ),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __( 'Title', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( 'Monri', 'wcwcCpg1' )
            ),
            'description' => array(
                'title' => __( 'Description', 'wcwcCpg1' ),
                'type' => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'wcwcCpg1' ),
                'default' => __( 'Description for Monri', 'wcwcCpg1' )
            ),
            'instructions' => array(
                'title' => __( 'Instructions', 'wcwcCpg1' ),
                'type' => 'textarea',
                'description' => __( 'Instructions that will be added to the thank you page.', 'wcwcCpg1' ),
                'default' => __( 'Instructions for Monri.', 'wcwcCpg1' )
            ),
            'thankyou_page' => array(
                'title' => __( 'Success page', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'Success URL potrebno je kopirati u Monri Account na predviđeno mjesto! ', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( wc_get_checkout_url() . get_option('woocommerce_checkout_order_received_endpoint', 'order-received'), 'wcwcCpg1' )
            ),
            'pikpaykey' => array(
                'title' => __( 'Monri Key', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( '', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '', 'wcwcCpg1' )
            ),
            'pikpayauthtoken' => array(
                'title' => __( 'Monri authenticity token', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( '', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '', 'wcwcCpg1' )
            ),
            'pickpay_methods' => array(
                'title'         => __( 'Use DIRECT Monri processing method:', 'wcwcCpg1' ),
                'type'          => 'select',
                'class'         => 'chosen_select',
                'css'           => 'width: 450px;',
                'default'       => true,
                'description'   => __( '', 'wcwcCpg1' ),
                'options'       => $pickpay_methods,
                'desc_tip'      => true,
            ),
            'test_mode' => array(
                'title'         => __( 'Test mode enabled:', 'wcwcCpg1' ),
                'type'          => 'select',
                'class'         => 'chosen_select',
                'css'           => 'width: 450px;',
                'default'       => 0,
                'description'   => __( '', 'wcwcCpg1' ),
                'options'       => $pickpay_methods,
                'desc_tip'      => true,
            ),
            'transaction_type' => array(
                'title'         => __( 'Transaction type:', 'wcwcCpg1' ),
                'type'          => 'select',
                'class'         => 'chosen_select',
                'css'           => 'width: 450px;',
                'default'       => 0,
                'description'   => __( '', 'wcwcCpg1' ),
                'options'       => $transaction_type,
                'desc_tip'      => true,
            ),
            'form_language' => array(
                'title'         => __( 'Form language:', 'wcwcCpg1' ),
                'type'          => 'select',
                'class'         => 'chosen_select',
                'css'           => 'width: 450px;',
                'default'       => 'EN',
                'description'   => __( '', 'wcwcCpg1' ),
                'options'       => $form_language,
                'desc_tip'      => true,
            ),
            'paying_in_installments' => array(
                'title'         => __( 'Allow paying in installments', 'wcwcCpg1' ),
                'type'          => 'select',
                'class'         => 'chosen_select',
                'css'           => 'width: 450px;',
                'default'       => 0,
                'description'   => __( '', 'wcwcCpg1' ),
                'options'       => $pickpay_methods,
                'desc_tip'      => true,
            ),
            'number_of_allowed_installments' => array(
                'title'         => __( 'Number of allowed installments', 'wcwcCpg1' ),
                'type'          => 'select',
                'class'         => 'chosen_select',
                'css'           => 'width: 450px;',
                'default'       => 0,
                'description'   => __( '', 'wcwcCpg1' ),
                'options'       => $number_of_allowed_installments,
                'desc_tip'      => true,
            ),
            'bottom_limit' => array(
                'title' => __( 'Price limit for paying in installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the bottom price limit on which the installments can be used.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_2' => array(
                'title' => __( 'Price increase when paying in 2 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_3' => array(
                'title' => __( 'Price increase when paying in 3 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_4' => array(
                'title' => __( 'Price increase when paying in 4 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_5' => array(
                'title' => __( 'Price increase when paying in 5 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_6' => array(
                'title' => __( 'Price increase when paying in 6 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_7' => array(
                'title' => __( 'Price increase when paying in 7 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_8' => array(
                'title' => __( 'Price increase when paying in 8 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_9' => array(
                'title' => __( 'Price increase when paying in 9 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_10' => array(
                'title' => __( 'Price increase when paying in 10 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_11' => array(
                'title' => __( 'Price increase when paying in 11 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_12' => array(
                'title' => __( 'Price increase when paying in 12 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_13' => array(
                'title' => __( 'Price increase when paying in 13 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_14' => array(
                'title' => __( 'Price increase when paying in 14 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_15' => array(
                'title' => __( 'Price increase when paying in 15 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_16' => array(
                'title' => __( 'Price increase when paying in 16 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_17' => array(
                'title' => __( 'Price increase when paying in 17 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_18' => array(
                'title' => __( 'Price increase when paying in 18 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_19' => array(
                'title' => __( 'Price increase when paying in 19 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_20' => array(
                'title' => __( 'Price increase when paying in 20 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_21' => array(
                'title' => __( 'Price increase when paying in 21 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_22' => array(
                'title' => __( 'Price increase when paying in 22 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_23' => array(
                'title' => __( 'Price increase when paying in 23 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            ),
            'price_increase_24' => array(
                'title' => __( 'Price increase when paying in 24 installments:', 'wcwcCpg1' ),
                'type' => 'text',
                'description' => __( 'This controls the price increase when paying with installments.', 'wcwcCpg1' ),
                'desc_tip' => true,
                'default' => __( '0', 'wcwcCpg1' )
            )

        );
    }

    public function admin_options(){
        echo '<h3>'.__('Monri Payment Gateway', 'Monri').'</h3>';
        echo '<table class="form-table">';
        // Generate the HTML For the settings form.
        $this -> generate_settings_html();
        echo '</table>';

    }

    
    // Check if we are forcing SSL on checkout pages
    // Custom function not required by the Gateway
    public function do_ssl_check() {
        if( $this->enabled == "yes" ) {
            if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
                echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";  
            }
        }       
    }

    /**
     * Process the payment and return the result
     **/
    function process_payment($order_id){

        global $woocommerce;
        $order = new WC_Order($order_id);

        $validation = $this->validate_form_fields($order);

        if($validation["response"]=="false"){
            wc_add_notice($validation["message"], 'error');
            return;
        }                

        if($this->pickpay_methods){         
            //Direct integration
            return $this->direct_integration($order_id);
        }else{
            //Form integration   
            return array('result' => 'success', 'redirect' => add_query_arg('order',
            $order->id, add_query_arg('key', $order->order_key, WC_Cart::get_checkout_url()))
             );
        }             
        
    }

    /**
     * Validate WooCommerce Form fields
     **/
    function validate_form_fields($order){
        if($this->form_language == "en"){
            $lang = $this->get_en_translation();
        }
        elseif($this->form_language == "ba-hr" || $this->form_language == "hr"){
            $lang = $this->get_ba_hr_translation();
        }
        elseif($this->form_language == "sr"){
            $lang = $this->get_sr_translation();
        }


        if(strlen($order->billing_first_name) < 3 || strlen($order->billing_first_name) > 11){
            $validation['response'] = "false";
            $validation['message'] = $lang['FIRST_NAME_ERROR'];
            return $validation;
        }
        if(strlen($order->billing_last_name) < 3 || strlen($order->billing_last_name) > 18){
            $validation['response'] = "false";
            $validation['message'] = $lang['LAST_NAME_ERROR'];
            return $validation;
        }
        if(strlen($order->billing_address_1) < 3 ||strlen($order->billing_address_1) > 300){
            $validation['response'] = "false";
            $validation['message'] = $lang['ADDRESS_ERROR'];
            return $validation;
        }
        if(strlen($order->billing_city) < 3 || strlen($order->billing_city) > 30){
            $validation['response'] = "false";
            $validation['message'] = $lang['CITY_ERROR'];
            return $validation;
        }
        if(strlen($order->billing_postcode) < 3 || strlen($order->billing_postcode) > 9){
            $validation['response'] = "false";
            $validation['message'] = $lang['ZIP_ERROR'];
            return $validation;
        }
        if(strlen($order->billing_phone) < 3 || strlen($order->billing_phone) > 30){
            $validation['response'] = "false";
            $validation['message'] = $lang['PHONE_ERROR'];
            return $validation;
        }
        if(strlen($order->billing_email) < 3 || strlen($order->billing_email) > 100){
            $validation['response'] = "false";
            $validation['message'] = $lang['EMAIL_ERROR'];
            return $validation;
        }

    }

    /**
     *Form integration
     **/
    function receipt_page($order){    
        if($this->form_language == "en"){
            $lang = $this->get_en_translation();
        }
        elseif($this->form_language == "ba-hr" || $this->form_language == "hr"){
            $lang = $this->get_ba_hr_translation();
        }
        elseif($this->form_language == "sr"){
            $lang = $this->get_sr_translation();
        }
        echo '<p>'.__($lang['RECIEPT_PAGE'], 'Monri').'</p>';
        echo $this->generate_form($order);           
        
    }


     /**
     * Generate pikpay button link
     **/    
    public function generate_form($order_id){               
             /**
             * Form integration
             **/
            global $woocommerce;

            $order = new WC_Order( $order_id );  
            $order_info = $order_id.'_'.date("dmy");

            //Check transaction type
            ($this->transaction_type? $transaction_type = 'authorize' : $transaction_type = 'purchase');

            // Check test mode
            if($this->test_mode)
            {
                $liveurl = 'https://ipgtest.monri.com/v2/form';
            }else{
                $liveurl = 'https://ipg.monri.com/v2/form';
            }


            //Convert order amount to number without decimals
            $order_total = $order->order_total * 100;

            if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
                $order_data = $order->get_data();  
                $currency = $order_data["currency"];
            }
            else{
                $order_meta = get_post_meta($order_id);
                $currency = $order_meta["_order_currency"][0];
            }

            //Convert currency to match PikPay's requested currency            
            if($currency == "KM"){
                $currency = "BAM";
            }

            //Generate digest key
            $digest = hash('sha512',$this->pikpaykey .$order->id .$order_total .$currency);

            //Combine first and last name in one string
            $full_name = $order->billing_first_name ." " .$order->billing_last_name;

            //Array of order information 
            $args = array(
                'ch_full_name'         => $full_name,
                'ch_address'           => $order->billing_address_1,
                'ch_city'              => $order->billing_city,
                'ch_zip'               => $order->billing_postcode,
                'ch_country'           => $order->billing_country,
                'ch_phone'             => $order->billing_phone,
                'ch_email'             => $order->billing_email,

                'order_info'           => $order_info,
                'order_number'         => $order->id,
                'amount'               => $order_total,
                'currency'             => $currency,
                'original_amount'      => $order->order_total,

                'language'             => $this->form_language,
                'transaction_type'     => $transaction_type,
                'authenticity_token'   => $this->pikpayauthtoken,
                'digest'               => $digest

              );

            //Generating input fields with order information that will be sent on pikpay
            $args_array = array();
            foreach($args as $key => $value){
              $args_array[] = "<input type='hidden' name='$key' value='$value'/>";
            }

            //Returning the form
            return '<form action="'.$liveurl.'" method="post" data-ajax="false" id="pikpay_payment_form">
                ' . implode('', $args_array) . '
                <input type="submit" class="button-alt" id="submit_pikpay_payment_form" value="'.__('Pay via Monri', 'Monri').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'Monri').'</a>
                <script type="text/javascript">
    jQuery(function(){
    jQuery("body").block(
            {
                message: "<img src=\"' . plugins_url() . '/woocommerce-monri/assets/images/ajax-loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px; width:50px\" />'.__('Hvala na narudžbi. Sada Vas preusmjeravamo na Monri kako bi završili plaćanje.', 'Monri').'",
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
        jQuery("#submit_pikpay_payment_form").click();});</script>
                </form>';
            

    }
    

    /**
     * Check for valid pikpay server callback
     **/
    public function check_pikpay_response(){ 
        if($this->form_language == "en"){
            $lang = $this->get_en_translation();
        }
        elseif($this->form_language == "ba-hr" || $this->form_language == "hr"){
            $lang = $this->get_ba_hr_translation();
        }
        elseif($this->form_language == "sr"){
            $lang = $this->get_sr_translation();
        }

        global $woocommerce;

        if(isset($_REQUEST['approval_code']) && isset($_REQUEST['digest']))
        {
            wp_enqueue_style( 'thankyou-page', plugins_url() . '/woocommerce-monri/assets/style/thankyou-page.css' );
            $order_id = $_REQUEST['order_number'];

            if($order_id != '')
            {
                try
                {
                    $order = new WC_Order($order_id);

                    $digest = $_REQUEST['digest'];
                    $response_code = $_REQUEST['response_code'];

                    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
                    {
                        $protocol = 'https://';
                    }
                    else {
                        $protocol = 'http://';
                    }

                    $url = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']);
                    $full_url = $url.'?'.$_SERVER['QUERY_STRING'];
                    $url_parsed = parse_url(preg_replace('/&digest=[^&]*/', '', $full_url));
                    $calculated_url = $url_parsed['scheme'].'://'.$url_parsed['host'].$url_parsed['path'].'?'.$url_parsed['query'];
                    //Generate digest
                    $checkdigest = hash('sha512', $this->pikpaykey.$calculated_url);
                    $transauthorised = false;
                    if($order->status !=='completed')
                    {
                        if($digest == $checkdigest)
                        {
                            if($response_code == "0000")
                            {
                                $transauthorised = true;
                                $this->msg['message'] = $lang["THANKYOU_SUCCESS"];
                                $this->msg['class'] = 'woocommerce_message';

                                if($order->status == 'processing'){

                                }else{
                                    $order->payment_complete();
                                    $order->add_order_note($lang["PIKPAY_SUCCESS"].$_REQUEST['approval_code']);
                                    $order->add_order_note($this->msg['message']);
                                    $order->add_order_note("Issuer: " . $_REQUEST['issuer']);
                                    if($_REQUEST['number_of_installments'] > 1)
                                    {
                                        $order->add_order_note($lang['NUMBER_OF_INSTALLMENTS'] . ": " . $_REQUEST['number_of_installments']);
                                    }
                                    $woocommerce->cart->empty_cart();
                                }
                            }else if($response_code=="pending"){
                                $this->msg['message'] = $lang["THANKYOU_PENDING"];
                                $this->msg['class'] = 'woocommerce_message woocommerce_message_info';
                                $order->add_order_note($lang['PIKPAY_PENDING'].$_REQUEST['approval_code']);
                                $order->add_order_note($this->msg['message']);
                                $order->add_order_note("Issuer: " . $_REQUEST['issuer']);
                                if($_REQUEST['number_of_installments'] > 1)
                                {
                                    $order->add_order_note($lang['NUMBER_OF_INSTALLMENTS'] . ": " . $_REQUEST['number_of_installments']);
                                }
                                $order->update_status('on-hold');
                                $woocommerce->cart->empty_cart();
                            }
                            else{
                                $this->msg['class'] = 'woocommerce_error';
                                $this->msg['message'] = $lang['THANKYOU_DECLINED'];
                                $order->add_order_note($lang['THANKYOU_DECLINED_NOTE'].$_REQUEST['Error']);                                
                            }
                        }else{
                            $this->msg['class'] = 'error';
                            $this->msg['message'] = $lang['SECURITY_ERROR'];

                        }
                        if($transauthorised==false){
                            $order->update_status('failed');
                            $order->add_order_note('Failed');
                            $order->add_order_note($this->msg['message']);
                        }

                        add_action('the_content', array(&$this, 'showMessage'));
                    }
                }catch(Exception $e){
                        // $errorOccurred = true;
                        $msg = "Error";
                }
            }
        }
    }

     /**
     * Check for valid 3dsecure response
     **/
    function check_3dsecure_response(){ 
        if($this->form_language == "en"){
            $lang = $this->get_en_translation();
        }
        elseif($this->form_language == "ba-hr" || $this->form_language == "hr"){
            $lang = $this->get_ba_hr_translation();
        }
        elseif($this->form_language == "sr"){
            $lang = $this->get_sr_translation();
        }

        if(isset($_POST['PaRes'])){     
               
            $resultXml = $this->handle3dsReturn($_POST);

            if (isset($resultXml->status) && $resultXml->status == "approved") {
                global $woocommerce; 

                $resultXml = (array)$resultXml;
                $order = new WC_Order($resultXml["order-number"]);
                        
                // Payment has been successful
                $order->add_order_note( __( $lang['PAYMENT_COMPLETED'], 'pikpay' ) );

                // Mark order as Paid
                $order->payment_complete();

                // Empty the cart (Very important step)
                $woocommerce->cart->empty_cart();

            }
            else{
                $order = new WC_Order($resultXml["order-number"]);

                $order->update_status('failed');
                $order->add_order_note('Failed');
                $order->add_order_note($this->msg['message']);
            }

        }
    }

    function showMessage($content){
            return '<div class="box '.$this->msg['class'].'-box">'.$this->msg['message'].'</div>'.$content;
        }

    /**
     * Direct integration
     **/
    function direct_integration($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);   
        
        $card_number = str_replace( array(' ', '-' ), '', $_POST['pikpay-card-number'] );
        $card_expiry = str_replace( array( '/', ' '), '', $_POST['pikpay-card-expiry'] );
        $card_expiry1 = substr($card_expiry, 0, 2);
        $card_expiry2 = substr($card_expiry, 2, 2);
        $card_expiry = $card_expiry2 .$card_expiry1;
        $card_installments = $_POST['pikpay-card-installments'];
        $card_cvv = $_POST['pikpay-card-cvc'];  
        $card_data_string = "- Card number: " .$_POST['pikpay-card-number'] ." \n- Card Expiration: " .$_POST['pikpay-card-expiry'] ."\n- CVC: ". $_POST['pikpay-card-cvc'];

        //Credit card validation
        $validation = $this->credit_card_validation($card_number, $card_expiry, $card_expiry1, $card_expiry2, $card_cvv); 

        if($validation["response"] == "false")
        {
            wc_add_notice($validation["message"], 'error');
            return;
        }  

        //Check transaction type
        ($this->transaction_type? $transaction_type = 'authorize' : $transaction_type = 'purchase');

        $order_info = $order_id.'_'.date("dmy");

        $amount = $order->order_total;  
        $number_of_installments = intval($card_installments);

        //Check if paying in installments, if yes set transaction_type to purchase
        if($number_of_installments > 1){
            $transaction_type = 'purchase';
        }


        if($number_of_installments===2){
            if($this->price_increase_2 != 0)
                $amount = $order->order_total + ($order->order_total * $this->price_increase_2/100);
        }
        elseif($number_of_installments===3){
            if($this->price_increase_3 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_3/100);
        }
        elseif($number_of_installments===4){
            if($this->price_increase_4 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_4/100);
        }
        elseif($number_of_installments===5){
            if($this->price_increase_5 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_5/100);
        }
        elseif($number_of_installments===6){
            if($this->price_increase_6 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_6/100);
        }
        elseif($number_of_installments===7){
            if($this->price_increase_7 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_7/100);
        }
        elseif($number_of_installments===8){
            if($this->price_increase_8 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_8/100);
        }
        elseif($number_of_installments===9){
            if($this->price_increase_9 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_9/100);
        }
        elseif($number_of_installments===10){
            if($this->price_increase_10 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_10/100);
        }
        elseif($number_of_installments===11){
            if($this->price_increase_11 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_11/100);
        }
        elseif($number_of_installments===12){
            if($this->price_increase_12 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_12/100);
        }        
        elseif($number_of_installments===13){
            if($this->price_increase_13 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_13/100);
        }
        elseif($number_of_installments===14){
            if($this->price_increase_14 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_14/100);
        }
        elseif($number_of_installments===15){
            if($this->price_increase_15 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_15/100);
        }
        elseif($number_of_installments===16){
            if($this->price_increase_16 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_16/100);
        }
        elseif($number_of_installments===17){
            if($this->price_increase_17 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_17/100);
        }
        elseif($number_of_installments===18){
            if($this->price_increase_18 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_18/100);
        }
        elseif($number_of_installments===19){
            if($this->price_increase_19 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_19/100);
        }
        elseif($number_of_installments===20){
            if($this->price_increase_20 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_20/100);
        }
        elseif($number_of_installments===21){
            if($this->price_increase_21 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_21/100);
        }
        elseif($number_of_installments===22){
            if($this->price_increase_22 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_22/100);
        }
        elseif($number_of_installments===23){
            if($this->price_increase_23 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_23/100);
        }
        elseif($number_of_installments===24){
            if($this->price_increase_24 != 0)
              $amount = $order->order_total + ($order->order_total * $this->price_increase_24/100);
        }

        //Convert order amount to number without decimals
        $amount = ceil($amount * 100);

        if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ) {
            $order_data = $order->get_data();  
            $currency = $order_data["currency"];
        }
        else{
            $order_meta = get_post_meta($order_id);
            $currency = $order_meta["_order_currency"][0];
        }

        if($currency == "KM"){
            $currency = "BAM";
        }

        //Generate digest key
        $digest = sha1($this->pikpaykey .$order->id .$amount .$currency);

        //Array of order information 
        $params = array(
            'ch_full_name'         => $order->billing_first_name ." " . $order->billing_last_name,
            'ch_address'           => $order->billing_address_1,
            'ch_city'              => $order->billing_city,
            'ch_zip'               => $order->billing_postcode,
            'ch_country'           => WC()->countries->countries[$order->billing_country],
            'ch_phone'             => $order->billing_phone,
            'ch_email'             => $order->billing_email,

            'pan'                  => $card_number,
            'cvv'                  => $card_cvv,
            'expiration_date'      => $card_expiry,

            'order_info'           => $order_info,
            'order_number'         => $order->id,
            'amount'               => $amount,
            'currency'             => $currency,

            'ip'                   => $_SERVER['REMOTE_ADDR'],
            'language'             => $this->form_language,
            'transaction_type'     => $transaction_type,
            'authenticity_token'   => $this->pikpayauthtoken,
            'digest'               => $digest,
            'number_of_installments'=> $card_installments
          );
        
        if($transaction_type == "authorize")
        {
           $resultXml = $this->authorize($params);          
        }
        else{
           $resultXml = $this->purchase($params);
        }

        if($this->form_language == "en")
        {
            $lang = $this->get_en_translation();
        }
        elseif($this->form_language == "ba-hr" || $this->form_language == "hr"){
            $lang = $this->get_ba_hr_translation();
        }
        elseif($this->form_language == "sr"){
            $lang = $this->get_sr_translation();
        }

        //check if cc have 3Dsecure validation
        if (isset($resultXml->{'acs-url'}))
        {
            //this is 3dsecure card
            //show user 3d secure form the
            $resultXml = (array)$resultXml;

            $urlEncode = array(
                "acsUrl"    => $resultXml['acs-url'],
                "pareq"     => $resultXml['pareq'],
                "returnUrl" => $this->get_return_url($order),
                "token"     => $resultXml['authenticity-token']
            );

            $params_3ds = http_build_query($urlEncode);
            $redirect = plugins_url() ."/woocommerce-monri/3dsecure.php?" . $params_3ds;
            
            return array(
                'result'   => 'success',
                'redirect' => $redirect,
            );
            
        } elseif (isset($resultXml->status) && $resultXml->status == "approved") {  

            global $woocommerce; 

             $resultXml = (array)$resultXml;
             $order = new WC_Order($resultXml["order-number"]);

            //Payment has been successful
            $order->add_order_note( __( $lang['PAYMENT_COMPLETED'], 'pikpay' ) );
            $pikpay_order_amount1 = $resultXml['amount']/100;
            $pikpay_order_amount2 = number_format($pikpay_order_amount1, 2);
            if($pikpay_order_amount2 != $order->total)
            {
                $order->add_order_note($lang['PIKPAY_ORDER_AMOUNT'] . ": " . $pikpay_order_amount2, true);
            }
            if($params['number_of_installments'] > 1)
            {
                $order->add_order_note($lang['NUMBER_OF_INSTALLMENTS'] . ": " . $params['number_of_installments']);
            }
                                                 
            // Mark order as Paid
            $order->payment_complete();

            // Empty the cart (Very important step)
            $woocommerce->cart->empty_cart();

            // Redirect to thank you page
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );
        }
        else {
            //nope
            wc_add_notice($lang['TRANSACTION_FAILED'] , 'error');
            return false;
        }
               
  }

  /**
     * Validate WooCommerce Form fields
     **/
    function credit_card_validation($card_number, $card_expiry, $card_expiry1, $card_expiry2, $card_cvv)
    {
        $year = (int)$card_expiry2;
        $currentYear = (int)substr(date("Y"), 2);
        $month = (int)$card_expiry1;
        $currentMonth = (int)date('m');
       
        if($this->form_language == "en"){
            $lang = $this->get_en_translation();
        }
        elseif($this->form_language == "ba-hr" || $this->form_language == "hr"){
            $lang = $this->get_ba_hr_translation();
        }
        elseif($this->form_language == "sr"){
            $lang = $this->get_sr_translation();
        }
        
        //Credit card validation
        if(!$this->luhn_check($card_number)){
            $validation['response'] = "false";
            $validation['message'] = $lang['INVALID_CARD_NUMBER'];
            return $validation;
        }
        if(empty($card_number)){
            $validation['response'] = "false";
            $validation['message'] = $lang['CARD_NUMBER_ERROR'];
            return $validation;
        }
        if(empty($card_expiry)){
            $validation['response'] = "false";
            $validation['message'] = $lang['CARD_EXPIRY_ERROR'];
            return $validation;
        }
        if($year < $currentYear){
            $validation['response'] = "false";
            $validation['message'] = $lang['CARD_EXPIRY_ERROR_PAST'];
            return $validation;
        }
        if($card_expiry2 == $currentYear && $card_expiry1 < $currentMonth){
            $validation['response'] = "false";
            $validation['message'] = $lang['CARD_EXPIRY_ERROR_PAST'];
            return $validation;
        }
        if(empty($card_cvv)){
            $validation['response'] = "false";
            $validation['message'] = $lang['CARD_CODE_ERROR'];
            return $validation;
        }   

    }

  public function payment_fields()
  {
        if($this->form_language == "en"){
            $lang = $this->get_en_translation();
        }
        elseif($this->form_language == "ba-hr" || $this->form_language == "hr"){
            $lang = $this->get_ba_hr_translation();
        }
        elseif($this->form_language == "sr"){
            $lang = $this->get_sr_translation();
        }

        if(!$this->pickpay_methods){
             if($this->description) echo wpautop(wptexturize($this->description));
        }
        else{
       
        $this->credit_card_script();

        $default_args = array(
          'fields_have_names' => true, // Some gateways like stripe don't need names as the form is tokenized
        );

        $args = array();
        $args = wp_parse_args( $args, apply_filters( 'woocommerce_credit_card_form_args', $default_args, $this->id ) );
      
        $order_total = $this->get_order_total();

        $price_increase_message = "<span id='price-increase-1' class='price-increase-message' style='display: none; color: red;'></span>";
      
        if($this->price_increase_2 != 0)
        {
            $amount2 = $order_total + ($order_total * $this->price_increase_2/100);
            $price_increase_message .= "<span id='price-increase-2' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_2 ."% = " .$amount2  ."</span>";
        }
        else
        {
            $amount2 = $order_total;
            $price_increase_message .= "<span id='price-increase-2' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_3 != 0)
        {
            $amount3 = $order_total + ($order_total * $this->price_increase_3/100);
            $price_increase_message .= "<span id='price-increase-3' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] . " " .$this->price_increase_3 ."% = " .$amount3  ."</span>";
        }
        else
        {
            $amount3 = $order_total;
            $price_increase_message .= "<span id='price-increase-3' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_4 != 0)
        {
            $amount4 = $order_total + ($order_total * $this->price_increase_4/100);
            $price_increase_message .= "<span id='price-increase-4' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_4 ."% = " .$amount4  ."</span>";
        }
        else
        {
            $amount4 = $order_total;
            $price_increase_message .= "<span id='price-increase-4' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_5 != 0)
        {
            $amount5 = $order_total + ($order_total * $this->price_increase_5/100);
            $price_increase_message .= "<span id='price-increase-5' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_5 ."% = " .$amount5  ."</span>";
        }
        else
        {
            $amount5 = $order_total;
            $price_increase_message .= "<span id='price-increase-5' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_6 != 0)
        {
            $amount6 = $order_total + ($order_total * $this->price_increase_6/100);
            $price_increase_message .= "<span id='price-increase-6' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_6 ."% = " .$amount6  ."</span>";
        }
        else
        {
            $amount6 = $order_total;
            $price_increase_message .= "<span id='price-increase-6' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_7 != 0)
        {
            $amount7 = $order_total + ($order_total * $this->price_increase_7/100);
            $price_increase_message .= "<span id='price-increase-7' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_7 ."% = " .$amount7  ."</span>";
        }
        else
        {
            $amount7 = $order_total;
            $price_increase_message .= "<span id='price-increase-7' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_8 != 0)
        {
            $amount8 = $order_total + ($order_total * $this->price_increase_8/100);
            $price_increase_message .= "<span id='price-increase-8' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_8 ."% = " .$amount8  ."</span>";
        }
        else
        {
            $amount8 = $order_total;
            $price_increase_message .= "<span id='price-increase-8' class='price-increase-message' style='display: none; color: red;'></span>";
        }
        if($this->price_increase_9 != 0)
        {
            $amount9 = $order_total + ($order_total * $this->price_increase_9/100);
            $price_increase_message .= "<span id='price-increase-9' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_9 ."% = " .$amount9  ."</span>";
        }
        else
        {
            $amount9 = $order_total;
            $price_increase_message .= "<span id='price-increase-9' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_10 != 0)
        {
            $amount10 = $order_total + ($order_total * $this->price_increase_10/100);
            $price_increase_message .= "<span id='price-increase-10' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_10 ."% = " .$amount10  ."</span>";
        }
        else
        {
            $amount10 = $order_total;
            $price_increase_message .= "<span id='price-increase-10' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_11 != 0)
        {
            $amount11 = $order_total + ($order_total * $this->price_increase_11/100);
            $price_increase_message .= "<span id='price-increase-11' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_11 ."% = " .$amount11  ."</span>";
        }
        else
        {
            $amount11 = $order_total;
            $price_increase_message .= "<span id='price-increase-11' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_12 != 0)
        {
            $amount12 = $order_total + ($order_total * $this->price_increase_12/100);
            $price_increase_message .= "<span id='price-increase-12' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_12 ."% = " .$amount12  ."</span>";
        }
        else
        {
            $amount12 = $order_total;
            $price_increase_message .= "<span id='price-increase-12' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_13 != 0)
        {
            $amount13 = $order_total + ($order_total * $this->price_increase_13/100);
            $price_increase_message .= "<span id='price-increase-13' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_13 ."% = " .$amount13  ."</span>";
        }
        else
        {
            $amount13 = $order_total;
            $price_increase_message .= "<span id='price-increase-13' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_14 != 0)
        {
            $amount14 = $order_total + ($order_total * $this->price_increase_14/100);
            $price_increase_message .= "<span id='price-increase-14' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_14 ."% = " .$amount14  ."</span>";
        }
        else
        {
            $amount14 = $order_total;
            $price_increase_message .= "<span id='price-increase-14' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_15 != 0)
        {
            $amount15 = $order_total + ($order_total * $this->price_increase_15/100);
            $price_increase_message .= "<span id='price-increase-15' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_15 ."% = " .$amount15  ."</span>";
        }
        else
        {
            $amount15 = $order_total;
            $price_increase_message .= "<span id='price-increase-15' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_16 != 0)
        {
            $amount16 = $order_total + ($order_total * $this->price_increase_16/100);
            $price_increase_message .= "<span id='price-increase-16' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_16 ."% = " .$amount16  ."</span>";
        }
        else
        {
            $amount16 = $order_total;
            $price_increase_message .= "<span id='price-increase-16' class='price-increase-message' style='display: none; color: red;'></span>";
        }


        if($this->price_increase_17 != 0)
        {
            $amount17 = $order_total + ($order_total * $this->price_increase_17/100);
            $price_increase_message .= "<span id='price-increase-17' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_17 ."% = " .$amount17  ."</span>";
        }
        else
        {
            $amount17 = $order_total;
            $price_increase_message .= "<span id='price-increase-17' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_18 != 0)
        {
            $amount18 = $order_total + ($order_total * $this->price_increase_18/100);
            $price_increase_message .= "<span id='price-increase-18' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_18 ."% = " .$amount18  ."</span>";
        }
        else
        {
            $amount18 = $order_total;
            $price_increase_message .= "<span id='price-increase-18' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_19 != 0)
        {
            $amount19 = $order_total + ($order_total * $this->price_increase_19/100);
            $price_increase_message .= "<span id='price-increase-19' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_19 ."% = " .$amount19  ."</span>";
        }
        else
        {
            $amount19 = $order_total;
            $price_increase_message .= "<span id='price-increase-19' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_20 != 0)
        {
            $amount20 = $order_total + ($order_total * $this->price_increase_20/100);
            $price_increase_message .= "<span id='price-increase-20' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_20 ."% = " .$amount20  ."</span>";
        }
        else
        {
            $amount20 = $order_total;
            $price_increase_message .= "<span id='price-increase-20' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_21 != 0)
        {
            $amount21 = $order_total + ($order_total * $this->price_increase_21/100);
            $price_increase_message .= "<span id='price-increase-21' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_21 ."% = " .$amount21  ."</span>";
        }
        else
        {
            $amount21 = $order_total;
            $price_increase_message .= "<span id='price-increase-21' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_22 != 0)
        {
            $amount22 = $order_total + ($order_total * $this->price_increase_22/100);
            $price_increase_message .= "<span id='price-increase-22' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_22 ."% = " .$amount22  ."</span>";
        }
        else
        {
            $amount22 = $order_total;
            $price_increase_message .= "<span id='price-increase-22' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_23 != 0)
        {
            $amount23 = $order_total + ($order_total * $this->price_increase_23/100);
            $price_increase_message .= "<span id='price-increase-23' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_23 ."% = " .$amount23  ."</span>";
        }
        else
        {
            $amount23 = $order_total;
            $price_increase_message .= "<span id='price-increase-23' class='price-increase-message' style='display: none; color: red;'></span>";
        }

        if($this->price_increase_24 != 0)
        {
            $amount24 = $order_total + ($order_total * $this->price_increase_24/100);
            $price_increase_message .= "<span id='price-increase-24' class='price-increase-message' style='display: none; color: red;'> " .$lang["PAYMENT_INCREASE"] ." " .$this->price_increase_24 ."% = " .$amount24  ."</span>";
        }
        else
        {
            $amount24 = $order_total;
            $price_increase_message .= "<span id='price-increase-24' class='price-increase-message' style='display: none; color: red;'></span>";
        }        

        if($this->paying_in_installments and $order_total >= $this->bottom_limit){
            if($this->number_of_allowed_installments == 24)
            {
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
            }else{
                if($this->number_of_allowed_installments == 12)
                {
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
                }else{
                    $options_string = '<option value="1">1</option>
                                  <option value="2">2</option>
                                  <option value="3">3</option>
                                  <option value="4">4</option>
                                  <option value="5">5</option>
                                  <option value="6">6</option>';
                }
            }

            $default_fields = array(

              'card-number-field' => '<p class="form-row form-row-wide">
                        <label for="' . esc_attr( $this->id ) . '-card-number">' . __( $lang['CARD_NUMBER'], 'woocommerce' ) . ' <span class="required">*</span></label>
                        <input data-invalidmessage="' .$lang['INVALID_CARD_NUMBER'] .'" id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . ( $args['fields_have_names'] ? $this->id . '-card-number' : '' ) . '" />
                    </p>',
              'card-expiry-field' => '<p class="form-row form-row-first">
                        <label for="' . esc_attr( $this->id ) . '-card-expiry">' . __( $lang['EXPIRY'].' (MM/YY)', 'woocommerce' ) . ' <span class="required">*</span></label>
                        <input maxlength="7" id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . esc_attr__( 'MM / YY', 'woocommerce' ) . '" name="' . ( $args['fields_have_names'] ? $this->id . '-card-expiry' : '' ) . '" />
                    </p>',
              'card-cvc-field' => '<p class="form-row form-row-last">
                        <label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( $lang['CARD_CODE'], 'woocommerce' ) . ' <span class="required">*</span></label>
                        <input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" name="' . ( $args['fields_have_names'] ? $this->id . '-card-cvc' : '' ) . '" />
                    </p>',
                    'card-installments' => '<p id="pikpay-card-installments-p" style="display: none; float: left;" class="form-row form-row-wide">
                            <label for="' . esc_attr( $this->id ) . '-card-installments">'.$lang['INSTALLMENTS_NUMBER'].'</label>
                            <select id="' . esc_attr( $this->id ) . '-card-installments" class="input-text wc-credit-card-form-card-cvc"  name="' . ( $args['fields_have_names'] ? $this->id . '-card-installments' : '' ) . '">
                              ' .$options_string
                            .'</select>'.$price_increase_message
                
            );
        }else{
            $default_fields = array(

              'card-number-field' => '<p class="form-row form-row-wide">
                        <label for="' . esc_attr( $this->id ) . '-card-number">' . __( $lang['CARD_NUMBER'], 'woocommerce' ) . ' <span class="required">*</span></label>
                        <input data-invalidmessage="' .$lang['INVALID_CARD_NUMBER'] .'"  id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="' . ( $args['fields_have_names'] ? $this->id . '-card-number' : '' ) . '" />
                    </p>',
              'card-expiry-field' => '<p class="form-row form-row-first">
                        <label for="' . esc_attr( $this->id ) . '-card-expiry">' . __( $lang['EXPIRY'].' (MM/YY)', 'woocommerce' ) . ' <span class="required">*</span></label>
                        <input maxlength="7" id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . esc_attr__( 'MM / YY', 'woocommerce' ) . '" name="' . ( $args['fields_have_names'] ? $this->id . '-card-expiry' : '' ) . '" />
                    </p>',
              'card-cvc-field' => '<p class="form-row form-row-last">
                        <label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( $lang['CARD_CODE'], 'woocommerce' ) . ' <span class="required">*</span></label>
                        <input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" name="' . ( $args['fields_have_names'] ? $this->id . '-card-cvc' : '' ) . '" />
                    </p>'
                
            );
        }

        
        $fields = array();
        $fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
        ?>
            <fieldset id="<?php echo $this->id; ?>-cc-form">
                <?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
                <?php
            foreach ( $fields as $field ) {
              echo $field;
            }
          ?>
                <?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
                <div class="clear"></div>
            </fieldset>
            <?php

        }

        
      }

    function credit_card_script() {              
        wp_register_script ('installments',  plugin_dir_url(__FILE__) . 'assets/js/installments.js', array( 'jquery' ),'1',true);      
        wp_enqueue_script('installments');
        wp_enqueue_script( 'wc-credit-card-form' );
    } 

     /**
     * Send purchase request and return the response.
     * Purchases don't need to be approved, funds are transfered in the next settlement between issuer
     * and acquirer banks, usually within one business day. These transactions can be refunded within 180 days.
     *
     * @param type $params
     * @return SimpleXmlElement|boolean
     */
    public function purchase($params) 
    {   
        $xml = $this->generateXml('purchase', $params);

        // check test mode
        if($this->test_mode)
        {
            $liveurl = 'https://ipgtest.monri.com/api';
        }
        else{
            $liveurl = 'https://ipg.monri.com/api';
        }

        return $this->curl($liveurl, $xml);
    }

    /**
     * Send purchase request and return the response
     * Authorization is a preferred transaction type for e-commerce. Merchant must capture
     * these transactions within 28 days in order to transfer the money from buyer's account to his own.
     * This transaction can also be voided if buyer cancel the order. Refund can be done after original authorization is captured.
     *
     * @param type $params
     * @return SimpleXmlElement|boolean
     */
    public function authorize($params) 
    {
        $xml = $this->generateXml('authorize', $params);

        // check test mode
        if($this->test_mode)
        {
            $liveurl = 'https://ipgtest.monri.com/api';
        }else{
            $liveurl = 'https://ipg.monri.com/api';
        }

        return $this->curl($liveurl, $xml);
    }


    public function handle3dsReturn($post)
    {
        $xml = "<?xml version='1.0' encoding='UTF-8'?>
                <secure-message>              
                  <MD>{$post['MD']}</MD>
                  <PaRes>{$post['PaRes']}</PaRes>
                </secure-message>";

        // check test mode
        if($this->test_mode)
        {
            $liveurl = 'https://ipgtest.monri.com/pares';
        }else{
            $liveurl = 'https://ipg.monri.com/pares';
        }
        $return = $this->curl($liveurl, $xml);
        return $return;
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
     * Generates XML string for purchase and authorize requests
     *
     * @param string purchase or authorize
     * @return string generated xml
     */
    function generateXml($type, $params)
    {
        $amount = "amount";
        $currency = "currency";
        $digest = "digest";
        $order_number = "order_number";
        $expiration_date = "expiration_date";
        $pan = "pan";
        $ip = "ip";
        $order_info = "order_info";
        $ch_address = "ch_address";
        $ch_city = "ch_city";
        $ch_country = "ch_country";
        $ch_email = "ch_email";
        $ch_full_name = "ch_full_name";
        $ch_phone = "ch_phone";
        $ch_zip = "ch_zip";
        $language = "language";
        $cvv = "cvv";
        $number_of_installments = "number_of_installments";

        

        $xml = "<?xml version='1.0' encoding='UTF-8'?>
                <transaction>
                  <transaction-type>$type</transaction-type>
                  <amount>{$params[$amount]}</amount>
                  <currency>{$params[$currency]}</currency>
                  <digest>{$params[$digest]}</digest>
                  <authenticity-token>$this->pikpayauthtoken</authenticity-token>
                  <order-number>{$params[$order_number]}</order-number>";

        if ($type === 'authorize' || $type === 'purchase') {
            $xml .= "<expiration-date>{$params[$expiration_date]}</expiration-date>
                  <pan>{$params[$pan]}</pan>
                  <ip>{$params[$ip]}</ip>
                  <order-info>{$params[$order_info]}</order-info>
                  <ch-address>{$params[$ch_address]}</ch-address>
                  <ch-city>{$params[$ch_city]}</ch-city>
                  <ch-country>{$params[$ch_country]}</ch-country>
                  <ch-email>{$params[$ch_email]}</ch-email>
                  <ch-full-name>{$params[$ch_full_name]}</ch-full-name>
                  <ch-phone>{$params[$ch_phone]}</ch-phone>
                  <ch-zip>{$params[$ch_zip]}</ch-zip>
                  <language>{$params[$language]}</language>
                  <cvv>{$params[$cvv]}</cvv>";

            if ($this->paying_in_installments && $params[$number_of_installments] > 1) {
                $xml .= "<number-of-installments>{$params[$number_of_installments]}</number-of-installments>";
            }
        }

        $xml .= "</transaction>";

        return $xml;
    }

    /**
     * Echoes 3D Secure form to user
     *
     * @param string $acsUrl
     * @param string $pareq
     * @param string $token
     * @param string $returnUrl
     */
    protected function show3dSecureForm($acsUrl, $pareq, $token, $returnUrl) {
        return [
            '3ds' => true,
            'form' => "<!DOCTYPE html>
            <html style='display:none'>
              <head>
                <title>Monri 3D Secure Verification</title>
                <script language='Javascript'>
                  function OnLoadEvent() { document.form.submit(); }
                </script>
              </head>
              <body OnLoad='OnLoadEvent();'>
                Invoking 3-D secure form, please wait ...
                <form name='form' action='$acsUrl' method='post'>
                  <input type='hidden' name='PaReq' value='$pareq'>
                  <input type='hidden' name='TermUrl' value='$returnUrl'>
                  <input type='hidden' name='MD' value='$token'>
                  <noscript>
                    <p>Please click</p><input id='to-asc-button' type='submit'>
                  </noscript>
                </form>
                </body>
            </html>"
        ]; // Output 3DS Verification Form
    }

     /*
    ------------------
    Language: English
    ------------------
    */
    public function get_en_translation(){
       
         
        $lang = array();
         
        //Credit card
        $lang['CARD_NUMBER'] = 'Card Number';
        $lang['EXPIRY'] = 'Expiry';
        $lang['CARD_CODE'] = 'Card Code';
        $lang['INSTALLMENTS_NUMBER'] = 'Number of installments';
         
        // Validation messages 
        $lang['FIRST_NAME_ERROR'] = 'First name must have between 3 and 11 characters';
        $lang['LAST_NAME_ERROR'] = 'Last name must have between 3 and 28 characters';
        $lang['ADDRESS_ERROR'] = 'Address must have between 3 and 300 characters';
        $lang['CITY_ERROR'] = 'City must have between 3 and 30 characters';
        $lang['ZIP_ERROR'] = 'ZIP must have between 3 and 30 characters';
        $lang['PHONE_ERROR'] = 'Phone must have between 3 and 30 characters';
        $lang['EMAIL_ERROR'] = 'Email must have between 3 and 30 characters';

        $lang['CARD_NUMBER_ERROR'] = 'Card Number is emtpy';
        $lang['CARD_EXPIRY_ERROR'] = 'Card Expiry is emtpy';
        $lang['CARD_EXPIRY_ERROR_PAST'] = 'Card expiry is in past';
        $lang['CARD_CODE_ERROR'] = 'Card Code is emtpy';
        $lang['INVALID_CARD_NUMBER'] = 'Invalid Credit Card number';

        //Reciept page messages
        $lang['RECIEPT_PAGE'] = 'Thank you for your order, please click the button below to pay with Monri.';

        //Thankyou page messages
        $lang['THANKYOU_SUCCESS'] = 'Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.';
        $lang['PIKPAY_SUCCESS'] = 'Monri payment successful<br/>Approval code: ';
        $lang['THANKYOU_PENDING'] = 'Thank you for shopping with us. Right now your payment status is pending, We will keep you posted regarding the status of your order through e-mail';
        $lang['PIKPAY_PENDING'] = 'Monri payment status is pending<br/>Approval code: ';
        $lang['SECURITY_ERROR'] = 'Security Error. Illegal access detected';
        $lang['THANKYOU_DECLINED'] = 'Thank you for shopping with us. However, the transaction has been declined.';
        $lang['THANKYOU_DECLINED_NOTE'] = 'Transaction Declined: ';

        //Payment notes
        $lang['PAYMENT_COMPLETED'] = 'Monri payment completed.';
        $lang['TRANSACTION_FAILED'] = 'Transaction failed.';

        $lang['PAYMENT_INCREASE'] = 'Depending on the installments number chosen, the price will increase for';

        $lang['NUMBER_OF_INSTALLMENTS'] = 'Number of installments';
        $lang['PIKPAY_ORDER_AMOUNT'] = 'Monri - Order amount';

        return $lang;
    }

    /*
    ------------------
    Language: Bosanski/Hrvatski
    ------------------
    */
    public function get_ba_hr_translation(){
        
        $lang = array();
         
        //Credit card
        $lang['CARD_NUMBER'] = 'Broj kartice';
        $lang['EXPIRY'] = 'Datum isteka';
        $lang['CARD_CODE'] = 'Cvv kod';
        $lang['INSTALLMENTS_NUMBER'] = 'Broj rata';
         
        // Validation messages 
        $lang['FIRST_NAME_ERROR'] = 'Ime mora imati između 3 i 11 karaktera';
        $lang['LAST_NAME_ERROR'] = 'Prezime mora imati između 3 i 28 karaktera';
        $lang['ADDRESS_ERROR'] = 'Adresa mora imati između 3 i 300 karaktera';
        $lang['CITY_ERROR'] = 'Grad mora imati između 3 i 30 karaktera';
        $lang['ZIP_ERROR'] = 'Poštanski broj mora imati između 3 i 30 karaktera';
        $lang['PHONE_ERROR'] = 'Telefon mora imati između 3 i 30 karaktera';
        $lang['EMAIL_ERROR'] = 'Email mora imati između 3 i 30 karaktera';
        $lang['INVALID_CARD_NUMBER'] = 'Neispravan broj kreditne kartice';

        $lang['CARD_NUMBER_ERROR'] = 'Polje Broj kartice je prazno';
        $lang['CARD_EXPIRY_ERROR'] = 'Polje Datum isteka je prazno';
        $lang['CARD_EXPIRY_ERROR_PAST'] = 'Datum isteka je u prošlosti';
        $lang['CARD_CODE_ERROR'] = 'Polje Cvv kod je prazno';

        //Reciept page messages
        $lang['RECIEPT_PAGE'] = 'Zahvaljujemo se na vašoj narudžbi, kliknite da dugme ispod kako bi platili preko Monri-a.';

        //Thankyou page messages
        $lang['THANKYOU_SUCCESS'] = 'Hvala što ste kupovali kod nas. Vaš račun je naplaćen i transakcija je uspješna. Uskoro ćemo vam poslati vašu narudžbu.';
        $lang['PIKPAY_SUCCESS'] = 'Monri plaćanje uspješno <br/>Approval code: ';
        $lang['THANKYOU_PENDING'] = 'Hvala što ste kupovali kod nas. Trenutno vaš status plaćanja je na čekanju.';
        $lang['PIKPAY_PENDING'] = 'Monri plaćanje na čekanju<br/>Approval code: ';
        $lang['SECURITY_ERROR'] = 'Sigurnosna greška. Nedozvoljen pristup detektovan.';
        $lang['THANKYOU_DECLINED'] = 'Hvala što ste kupovali kod nas. Nažalost transakcija je odbijena.';
        $lang['THANKYOU_DECLINED_NOTE'] = 'Transakcija odbijena: ';

        //Payment notes
        $lang['PAYMENT_COMPLETED'] = 'Monri plaćanje uspješno.';
        $lang['TRANSACTION_FAILED'] = 'Transakcija neuspješna.';

        $lang['PAYMENT_INCREASE'] = 'Na osnovu odabranog broja rata cijena će se povećati za';

        $lang['NUMBER_OF_INSTALLMENTS'] = 'Broj rata';
        $lang['PIKPAY_ORDER_AMOUNT'] = 'Monri - Iznos narudžbe sa naknadom';

        return $lang;
    }


    /*
   ------------------
   Language: Srpski
   ------------------
   */
    public function get_sr_translation(){

        $lang = array();

        //Credit card
        $lang['CARD_NUMBER'] = 'Broj kartice';
        $lang['EXPIRY'] = 'Datum isteka';
        $lang['CARD_CODE'] = 'Cvv kod';
        $lang['INSTALLMENTS_NUMBER'] = 'Broj rata';

        // Validation messages
        $lang['FIRST_NAME_ERROR'] = 'Ime mora da ima između 3 i 11 karaktera';
        $lang['LAST_NAME_ERROR'] = 'Prezime mora da ima između 3 i 28 karaktera';
        $lang['ADDRESS_ERROR'] = 'Adresa mora da ima između 3 i 300 karaktera';
        $lang['CITY_ERROR'] = 'Grad mora da ima između 3 i 30 karaktera';
        $lang['ZIP_ERROR'] = 'Poštanski broj mora da ima između 3 i 30 karaktera';
        $lang['PHONE_ERROR'] = 'Telefon mora da ima između 3 i 30 karaktera';
        $lang['EMAIL_ERROR'] = 'Email mora da ima između 3 i 30 karaktera';
        $lang['INVALID_CARD_NUMBER'] = 'Neispravan broj kreditne kartice';

        $lang['CARD_NUMBER_ERROR'] = 'Polje Broj kartice je prazno';
        $lang['CARD_EXPIRY_ERROR'] = 'Polje Datum isteka je prazno';
        $lang['CARD_EXPIRY_ERROR_PAST'] = 'Datum isteka je u prošlosti';
        $lang['CARD_CODE_ERROR'] = 'Polje Cvv kod je prazno';

        //Reciept page messages
        $lang['RECIEPT_PAGE'] = 'Zahvaljujemo se na vašoj narudžbi, kliknite da dugme ispod kako bi platili preko Monri-a.';

        //Thankyou page messages
        $lang['THANKYOU_SUCCESS'] = 'Hvala što ste kupovali kod nas. Vaš račun je naplaćen i transakcija je uspešna. Uskoro ćemo vam poslati vašu narudžbu.';
        $lang['PIKPAY_SUCCESS'] = 'Monri plaćanje uspešno <br/>Approval code: ';
        $lang['THANKYOU_PENDING'] = 'Hvala što ste kupovali kod nas. Trenutno vaš status plaćanja je na čekanju.';
        $lang['PIKPAY_PENDING'] = 'Monri plaćanje na čekanju<br/>Approval code: ';
        $lang['SECURITY_ERROR'] = 'Sigurnosna greška. Nedozvoljen pristup detektovan.';
        $lang['THANKYOU_DECLINED'] = 'Hvala što ste kupovali kod nas. Nažalost transakcija je odbijena.';
        $lang['THANKYOU_DECLINED_NOTE'] = 'Transakcija odbijena: ';

        //Payment notes
        $lang['PAYMENT_COMPLETED'] = 'Monri plaćanje uspešno.';
        $lang['TRANSACTION_FAILED'] = 'Transakcija neuspešna.';

        $lang['PAYMENT_INCREASE'] = 'Na osnovu odabranog broja rata cena će se povećati za';

        $lang['NUMBER_OF_INSTALLMENTS'] = 'Broj rata';
        $lang['PIKPAY_ORDER_AMOUNT'] = 'Monri - Iznos narudžbe sa naknadom';

        return $lang;
    }

    /*
    * Luhn algorithm number checker - (c) 2005-2008 shaman - www.planzero.org *
     * This code has been released into the public domain, however please      *
     * give credit to the original author where possible.                      */

    function luhn_check($number) {

      // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
      $number=preg_replace('/\D/', '', $number);

      // Set the string length and parity
      $number_length=strlen($number);
      $parity=$number_length % 2;

      // Loop through each digit and do the maths
      $total=0;
      for ($i=0; $i<$number_length; $i++) {
        $digit=$number[$i];
        // Multiply alternate digits by two
        if ($i % 2 == $parity) {
          $digit*=2;
          // If the sum is two digits, add them together (in effect)
          if ($digit > 9) {
            $digit-=9;
          }
        }
        // Total up the digits
        $total+=$digit;
      }

      // If the total mod 10 equals 0, the number is valid
      return ($total % 10 == 0) ? TRUE : FALSE;

    }

}
?>
