<?php

class Monri_WC_Gateway_Adapter_Webpay_Components {

	/**
	 * Adapter ID
	 */
	public const ADAPTER_ID = 'webpay_components';

	public const AUTHORIZATION_ENDPOINT_TEST = 'https://ipgtest.monri.com/v2/payment/new';
	public const AUTHORIZATION_ENDPOINT = 'https://ipg.monri.com/v2/payment/new';

	public const SCRIPT_ENDPOINT_TEST = 'https://ipgtest.monri.com/dist/components.js';
	public const SCRIPT_ENDPOINT = 'https://ipg.monri.com/dist/components.js';

	/**
	 * @var Monri_WC_Gateway
	 */
	private $payment;

	/**
	 * @param Monri_WC_Gateway $payment
	 *
	 * @return void
	 */
	public function init( $payment ) {
		$this->payment             = $payment;
		$this->payment->has_fields = true;

		// load components.js on frontend checkout
		add_action( 'template_redirect', function () {
			if ( is_checkout() ) {
				$script_url = $this->payment->get_option_bool( 'test_mode' ) ? self::SCRIPT_ENDPOINT_TEST : self::SCRIPT_ENDPOINT;
				wp_enqueue_script( 'monri-components', $script_url, array(), MONRI_WC_VERSION );
			}
		} );
	}

	/**
	 * @return void
	 */
	public function payment_fields() {

		// @todo: cache based on timestamp/amount for expire?
		$initialize = $this->request_authorize();

		//if ($initialize['client_secret'])

		// @todo: is this needed?
		//$script_url = $this->payment->get_option_bool( 'test_mode' ) ? self::SCRIPT_ENDPOINT_TEST : self::SCRIPT_ENDPOINT;
		//wp_enqueue_script( 'monri-components', $script_url, array( 'jquery' ), MONRI_WC_VERSION );

		$order_total = (float) WC()->cart->get_total( 'edit' );
		$installments = false;
		if ( $this->payment->get_option_bool( 'paying_in_installments' ) ) {
			$bottom_limit           = (float) $this->payment->get_option( 'bottom_limit', 0 );
			$bottom_limit_satisfied = ( $bottom_limit < 0.01 ) || ( $order_total >= $bottom_limit );

			if ( $bottom_limit_satisfied ) {
				$installments = true;
			}
		}

		wc_get_template( 'components.php', array(
			'config' => array(
				'authenticity_token' => $this->payment->get_option( 'monri_authenticity_token' ),
				'client_secret' => $initialize['client_secret'],
				'locale'             => $this->payment->get_option( 'form_language' ),
			),
			'installments' => $installments
		), basename( MONRI_WC_PLUGIN_PATH ), MONRI_WC_PLUGIN_PATH . 'templates/' );
	}

	/**
	 * @param int $order_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function process_payment( $order_id ) {

		/*
		$monri_token = $_POST['monri-token'] ?? '';

		if ( empty( $monri_token ) ) {
			throw new Exception( esc_html( __( 'Missing Monri token.', 'monri' ) ) );
		}
		*/

		// monri-transaction + validate order_number
		// min that needs to be saved here is _monri_components_order_number -> callback needs to load by that meta

		//Monri_WC_Logger::log( "Request data: " . print_r( $params, true ), __METHOD__ );

		$order  = wc_get_order( $order_id );
		$amount = $order->get_total();

		//Payment has been successful
		/*
		$order->add_order_note( __( 'Monri payment completed.', 'monri' ) );
		$monri_order_amount1 = $transactionResult['amount'] / 100;
		$monri_order_amount2 = number_format( $monri_order_amount1, 2 );
		if ( $monri_order_amount2 != $order->get_total() ) {
			$order->add_order_note( __( 'Monri - Order amount: ', 'monri' ) . $monri_order_amount2, true );
		}
		if ( isset( $params['number_of_installments'] ) && $params['number_of_installments'] > 1 ) {
			$order->add_order_note( __( 'Number of installments: ', 'monri' ) . $params['number_of_installments'] );
		}
		*/

		// Mark order as Paid
		//$order->payment_complete();

		WC()->cart->empty_cart();

		// Redirect to thank you page
		return array(
			'result'   => 'success',
			'redirect' => $this->payment->get_return_url( $order ),
		);


			/*
		throw new Exception(
			isset( $result['errors'] ) && ! empty( $result['errors'] ) ?
				esc_html( implode( '; ', $result['errors'] ) ) :
				esc_html( __( 'Missing Monri token.', 'monri' ) )
		);
			*/
	}

	/**
	 *
	 * @return array
	 */
	private function request_authorize() {

		$url = $this->payment->get_option_bool( 'test_mode' ) ?
			self::AUTHORIZATION_ENDPOINT_TEST :
			self::AUTHORIZATION_ENDPOINT;

		$order_total = (float)WC()->cart->get_total( 'edit' );

		/*
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}
		*/

		$data = [
			'amount' => (int)round($order_total * 100),
			'order_number' => wp_generate_uuid4(), //uniqid('woocommerce-', true),
			'currency' => get_woocommerce_currency(),
			'transaction_type' => $this->payment->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase',
			'order_info' => 'woocommerce order',
			//'scenario' => 'charge'
		];

		$data = wp_json_encode( $data );

		$timestamp = time();
		$digest = hash('sha512',
			$this->payment->get_option( 'monri_merchant_key' ) .
			$timestamp .
			$this->payment->get_option( 'monri_authenticity_token' ) .
			$data
		);

		$authorization = "WP3-v2 {$this->payment->get_option( 'monri_authenticity_token' )} $timestamp $digest";

		$response = wp_remote_post( $url, [
				'body'      => $data,
				'headers'   => [
					'Content-Type' => 'application/json',
					'Content-Length' => strlen($data),
					'Authorization' => $authorization
				],
				'timeout'   => 10,
				'sslverify' => true
			]
		);

		if (is_wp_error( $response ) ) {
			//return $response;
			$response = ['status' => 'error', 'error' => $response->get_error_message()];
		}

		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
	}

}
