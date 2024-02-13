<?php

require_once __DIR__ . '/gateway-abstract.php';
class Monri_WC_Gateway_Components extends Monri_WC_Gateway
{
	function __construct() {
		// The global ID for this Payment method
		$this->id = 'monri_components'; //monri_webpay_form

		// The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
		$this->method_title = __( 'Monri Components', 'monri' );

		// The description for this Payment Gateway, shown on the actual Payment options page on the backend
		$this->method_description = __( "Monri Payment Gateway Plug-in for WooCommerce", 'monri' );

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

		$this->title        = $this->settings['title'] ?? __( 'Monri', 'monri' );
		$this->description  = $this->settings['description'];
		$this->instructions = $this->get_option( 'instructions' );

		//add_option('woocommerce_pay_page_id', $page_id);

		// Lets check for SSL
		//add_action('admin_notices', array($this, 'do_ssl_check'));

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );

		//$this->check_monri_response();
		add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'payment_callback' ] );
		add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'process_redirect' ] );

		$this->has_fields = true;
	}

	public function init_form_fields() {
		$this->form_fields = Monri_WC_Settings::instance()->get_form_fields();
	}

}
