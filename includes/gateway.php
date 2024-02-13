<?php

class Monri_WC_Gateway extends WC_Payment_Gateway {

	protected $adapter;

	function __construct()
	{
		// The global ID for this Payment method
		$this->id = 'monri'; //monri_webpay_form

		// The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
		$this->method_title = __('Monri', 'monri');

		// The description for this Payment Gateway, shown on the actual Payment options page on the backend
		$this->method_description = __("Monri Payment Gateway Plug-in for WooCommerce", 'monri');

		//$this->title = ;

		// If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
		//$this->icon = null;

		// This basically defines your settings which are then loaded with init_settings()
		$this->init_form_fields();

		// After init_settings() is called, you can get the settings and load them into variables, e.g:
		// $this->title = $this->get_option( 'title' );
		$this->init_settings();

		//$this->init_options();

		/*
		foreach ($this->settings as $setting_key => $value) {
			$this->$setting_key = $value;
		}
		*/

		// Define user set variables.
		// The title to be used for the vertical tabs that can be ordered top to bottom

		$this->title = $this->settings['title'] ?? __('Monri', 'monri');
		$this->description = $this->settings['description'];
		$this->instructions = $this->get_option('instructions');

		//add_option('woocommerce_pay_page_id', $page_id);

		// Lets check for SSL
		//add_action('admin_notices', array($this, 'do_ssl_check'));

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

		// based on settings
		$this->adapter = new Monri_WC_Gateway_Wspay();
		$this->adapter->init();

		//$this->check_monri_response();
		add_action('woocommerce_thankyou_' . $this->id, [$this, 'payment_callback']);
		add_action('woocommerce_receipt_' . $this->id, [$this, 'process_redirect']);

		$this->has_fields = true;
	}

	public function get_option_key() {
		return $this->plugin_id . 'monri_settings';
	}

	public function get_field_key( $key ) {
		return $this->plugin_id . 'monri _settings';
	}

	public function admin_optionsxx()
	{
		parent:$this->admin_options();

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
}
