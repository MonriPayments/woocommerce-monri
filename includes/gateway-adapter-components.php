<?php

class Monri_WC_Gateway_Adapter_Components
{
	public const ADAPTER_ID = 'monri_webpay_components';

	function __construct() {

		//$this->adapter_id = 'monri_webpay_components'; //monri_webpay_form

		$this->title        = $this->settings['title'] ?? __( 'Monri', 'monri' );
		$this->description  = $this->settings['description'];
		$this->instructions = $this->get_option( 'instructions' );

		//add_option('woocommerce_pay_page_id', $page_id);

		//$this->check_monri_response();
		add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'payment_callback' ] );
		add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'process_redirect' ] );

		$this->has_fields = true;
	}

}
