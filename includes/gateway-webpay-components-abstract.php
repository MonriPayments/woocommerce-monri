<?php

abstract class Monri_WC_Gateway_Webpay_Components_Abstract extends WC_Payment_Gateway {
	public const AUTHORIZATION_ENDPOINT_TEST = 'https://ipgtest.monri.com/v2/payment/new';
	public const AUTHORIZATION_ENDPOINT      = 'https://ipg.monri.com/v2/payment/new';
	public const SCRIPT_ENDPOINT_TEST        = 'https://ipgtest.monri.com/dist/components.js';
	public const SCRIPT_ENDPOINT             = 'https://ipg.monri.com/dist/components.js';

	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Generate client secret
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	protected function request_authorize( $order ) {
		$order_id = $order->get_id();
		if ( $this->get_option_bool( 'test_mode' ) ) {
			$order_id = Monri_WC_Utils::get_test_order_id( $order_id );
		}

		$url = $this->get_option_bool( 'test_mode' ) ?
			self::AUTHORIZATION_ENDPOINT_TEST :
			self::AUTHORIZATION_ENDPOINT;

		$order_total = (float) $order->get_total();

		$amount_in_minor_units = (int) round( $order_total * 100 );

		$currency = get_woocommerce_currency();
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}

		$data = array(
			'amount'           => $amount_in_minor_units,
			'order_number'     => $order_id,
			'currency'         => $currency,
			'transaction_type' => $this->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase',
			'order_info'       => 'woocommerce order',
			'ip'               => $order->get_customer_ip_address(),
		);

		$data = wp_json_encode( $data );

		$timestamp = time();
		$digest    = hash(
			'sha512',
			$this->get_option( 'monri_merchant_key' ) .
			$timestamp .
			$this->get_option( 'monri_authenticity_token' ) .
			$data
		);

		$authorization = "WP3-v2 {$this->get_option( 'monri_authenticity_token' )} $timestamp $digest";

		Monri_WC_Logger::log( $data, __METHOD__ );

		$response = wp_remote_post(
			$url,
			array(
				'body'      => $data,
				'headers'   => array(
					'Content-Type'   => 'application/json',
					'Content-Length' => strlen( $data ),
					'Authorization'  => $authorization,
				),
				'timeout'   => 10,
				'sslverify' => true,
			)
		);

		Monri_WC_Logger::log( $response, __METHOD__ );

		if ( is_wp_error( $response ) ) {
			$response = array(
				'status' => 'error',
				'error'  => $response->get_error_message(),
			);
		}

		$body = wp_remote_retrieve_body( $response );

		return json_decode( $body, true )['client_secret'];
	}

	/**
	 * Gateway options getter
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function get_option_bool( $key ) {
		return in_array( $this->get_option( $key ), array( 'yes', '1', true ), true );
	}

	/**
	 * Init settings for gateways.
	 */
	public function init_settings() {
		$this->settings = get_option( 'woocommerce_monri_settings', array() );
	}
}
