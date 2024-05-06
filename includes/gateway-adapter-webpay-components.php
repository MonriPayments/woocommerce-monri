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
				wp_enqueue_script( 'monri-components', $script_url, [], MONRI_WC_VERSION );
			}
		} );
	}

	/**
	 * @return void
	 */
	public function payment_fields() {

		// @todo: cache based on timestamp/amount for expire?
		$initialize = $this->request_authorize();

		if ( empty( $initialize['client_secret'] ) ) {
			esc_html_e( 'Initialization error occurred.', 'monri' );
			return;
		}

		// @todo: is this needed?
		//$script_url = $this->payment->get_option_bool( 'test_mode' ) ? self::SCRIPT_ENDPOINT_TEST : self::SCRIPT_ENDPOINT;
		//wp_enqueue_script( 'monri-components', $script_url, array( 'jquery' ), MONRI_WC_VERSION );

		$order_total  = (float) WC()->cart->get_total( 'edit' );
		$installments = false;
		if ( $this->payment->get_option_bool( 'paying_in_installments' ) ) {
			$bottom_limit           = (float) $this->payment->get_option( 'bottom_limit', 0 );
			$installments = ( $bottom_limit < 0.01 ) || ( $order_total >= $bottom_limit );
		}

		wc_get_template( 'components.php', array(
			'config'       => array(
				'authenticity_token' => $this->payment->get_option( 'monri_authenticity_token' ),
				'client_secret'      => $initialize['client_secret'],
				'locale'             => $this->payment->get_option( 'form_language' ),
			),
			'installments' => $installments
		), basename( MONRI_WC_PLUGIN_PATH ), MONRI_WC_PLUGIN_PATH . 'templates/' );
	}

	public function prepare_blocks_data() {
		$initialize = $this->request_authorize();

		return [
			'components' => [
				'authenticity_token' => $this->payment->get_option( 'monri_authenticity_token' ),
				'client_secret'      => $initialize['client_secret'] ?? "",
				'locale'             => $this->payment->get_option( 'form_language' ),
			]
		];
	}

	/**
	 * @param int $order_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function process_payment( $order_id ) {

		// monri-transaction is a json value, it is individually sanitized after decode
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$transaction = json_decode( wp_unslash( $_POST['monri-transaction'] ?? '{}' ), true );

		Monri_WC_Logger::log( "Response data: " . sanitize_textarea_field( print_r( $transaction, true ) ), __METHOD__ );

		if ( empty( $transaction ) ) {
			throw new Exception( esc_html( __( 'Missing Monri transaction.', 'monri' ) ) );
		}

		// monri-transaction + validate order_number vs one in session
		// min that needs to be saved here is _monri_components_order_number

		$order = wc_get_order( $order_id );

        $response_code = ! empty( $transaction['transaction_response']['response_code'] ) ?
            sanitize_text_field( $transaction['transaction_response']['response_code'] ) :
            '';

        if ( $response_code === '0000' ) {
            $order->payment_complete(
                isset( $transaction['transaction_response']['id'] ) ?
                    sanitize_key( (string) $transaction['transaction_response']['id'] ) :
                    ''
            );

            $monri_order_number = $transaction['transaction_response']['order_number'] ?
                sanitize_key( $transaction['transaction_response']['order_number'] ) :
                '';

            /* translators: %s: generated id which represents order number */
            $order->add_order_note( sprintf( __( 'Order number in Monri administration: %s', 'monri' ), $monri_order_number ) );

            WC()->cart->empty_cart();

        } else {
            $order->update_status( 'failed', "Response not authorized - response code is $response_code." );
        }

		return array(
			'result'   => 'success',
			'redirect' => $this->payment->get_return_url( $order ),
		);
	}

	/**
	 *
	 * @return array
	 */
	private function request_authorize() {

		$url = $this->payment->get_option_bool( 'test_mode' ) ?
			self::AUTHORIZATION_ENDPOINT_TEST :
			self::AUTHORIZATION_ENDPOINT;

		if (is_admin()) { // admin page editor
			$order_total = 10;
		} else {
			$order_total = (float) WC()->cart->get_total( 'edit' );
		}

		$currency    = get_woocommerce_currency();
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}

		$data = [
			'amount'           => (int) round( $order_total * 100 ),
			'order_number'     => wp_generate_uuid4(), //uniqid('woocommerce-', true),
			'currency'         => $currency,
			'transaction_type' => $this->payment->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase',
			'order_info'       => 'woocommerce order'
		];

		$data = wp_json_encode( $data );

		$timestamp = time();
		$digest    = hash( 'sha512',
			$this->payment->get_option( 'monri_merchant_key' ) .
			$timestamp .
			$this->payment->get_option( 'monri_authenticity_token' ) .
			$data
		);

		$authorization = "WP3-v2 {$this->payment->get_option( 'monri_authenticity_token' )} $timestamp $digest";

		$response = wp_remote_post( $url, [
				'body'      => $data,
				'headers'   => [
					'Content-Type'   => 'application/json',
					'Content-Length' => strlen( $data ),
					'Authorization'  => $authorization
				],
				'timeout'   => 10,
				'sslverify' => true
			]
		);

		if ( is_wp_error( $response ) ) {
			$response = [ 'status' => 'error', 'error' => $response->get_error_message() ];
		}

		$body = wp_remote_retrieve_body( $response );

		return json_decode( $body, true );
	}

}
