<?php

class Monri_WC_Gateway_Webpay_Components_Pay_Cek extends Monri_WC_Gateway_Webpay_Components_Abstract {

	/**
	 * Supported features
	 *
	 * @var string[]
	 */
	public $supports = array( 'products', 'refunds' );

	/**
	 * Gateway ID
	 *
	 * @var string
	 */
	public $id = 'monri_components_pay_cek';

	/**
	 * Components Google Pay constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init_settings();
		$this->has_fields  = false;
		$this->title       = __( 'Monri PayCek', 'monri' );
		$this->description = __( 'Pay with Monri PayCek', 'monri' );

		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'process_components' ) );
		add_action( 'woocommerce_before_thankyou', array( $this, 'process_return' ) );

		// load components.js on frontend checkout.
		add_action(
			'template_redirect',
			function () {
				if ( is_checkout() ) {
					$script_url = $this->get_option_bool( 'test_mode' ) ? self::SCRIPT_ENDPOINT_TEST : self::SCRIPT_ENDPOINT;
					wp_enqueue_script( 'monri-components-google-pay', $script_url, array(), MONRI_WC_VERSION );
				}
			}
		);
	}

	/**
	 * Passes config data to template file
	 *
	 * @param $order_id
	 *
	 * @return void
	 */
	public function process_components( $order_id ) {

		$order = wc_get_order( $order_id );
		wc_get_template(
			'components-pay-cek.php',
			array(
				'config' => array(
					'env'                => $this->get_option_bool( 'test_mode' ) ? 'test' : 'prod',
					'client_secret'      => $this->request_authorize( $order ),
					'authenticity_token' => $this->get_option( 'monri_authenticity_token' ),
					'locale'             => $this->get_option( 'form_language' ),
					'return_url'         => $this->get_return_url( $order ),
					'ch_full_name'       => wc_trim_string( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
					'ch_address'         => wc_trim_string( $order->get_billing_address_1(), 100, '' ),
					'ch_city'            => wc_trim_string( $order->get_billing_city(), 100, '' ),
					'ch_zip'             => wc_trim_string( $order->get_billing_postcode(), 100, '' ),
					'ch_country'         => wc_trim_string( $order->get_billing_country(), 100, '' ),
					'ch_phone'           => wc_trim_string( $order->get_billing_phone(), 100, '' ),
					'ch_email'           => wc_trim_string( $order->get_billing_email(), 100, '' ),
					'orderInfo'          => $order_id . '_' . gmdate( 'dmy' ),
				),
			),
			basename( MONRI_WC_PLUGIN_PATH ),
			MONRI_WC_PLUGIN_PATH . 'templates/'
		);
	}
}
