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
	 * @var string[]
	 */
	public $supports = [ 'products', 'refunds' ];

	/**
	 * @param Monri_WC_Gateway $payment
	 *
	 * @return void
	 */
	public function init( $payment ) {
		$this->payment             = $payment;
		$this->payment->has_fields = true;
		add_action( 'woocommerce_order_status_changed', [ $this, 'process_capture' ], null, 4 );
		add_action( 'woocommerce_order_status_changed', [ $this, 'process_void' ], null, 4 );

		// load components.js on frontend checkout
		add_action( 'template_redirect', function () {
			if ( is_checkout() ) {
				$script_url = $this->payment->get_option_bool( 'test_mode' ) ? self::SCRIPT_ENDPOINT_TEST : self::SCRIPT_ENDPOINT;
				wp_enqueue_script( 'monri-components', $script_url, [], MONRI_WC_VERSION );
			}
		} );

		add_action( 'woocommerce_after_checkout_validation', [ $this, 'after_checkout_validation' ], null, 2);
	}

	/**
	 * @return void
	 */
	public function payment_fields() {

		$client_secret = $this->request_authorize();

		if ( empty( $client_secret ) ) {
			esc_html_e( 'Initialization error occurred.', 'monri' );

			return;
		}

		// @todo: is this needed?
		//$script_url = $this->payment->get_option_bool( 'test_mode' ) ? self::SCRIPT_ENDPOINT_TEST : self::SCRIPT_ENDPOINT;
		//wp_enqueue_script( 'monri-components', $script_url, array( 'jquery' ), MONRI_WC_VERSION );

		$order_total  = (float) WC()->cart->get_total( 'edit' );
		$installments = false;
		if ( $this->payment->get_option_bool( 'paying_in_installments' ) ) {
			$bottom_limit = (float) $this->payment->get_option( 'bottom_limit', 0 );
			$installments = ( $bottom_limit < 0.01 ) || ( $order_total >= $bottom_limit );
		}

		// Prevents rendering this file multiple times - JS part gets duplicated and executed twice
		if ( isset( $_REQUEST['wc-ajax'] ) && $_REQUEST['wc-ajax'] === "update_order_review" ) {
			wc_get_template( 'components.php', array(
				'config'       => array(
					'authenticity_token' => $this->payment->get_option( 'monri_authenticity_token' ),
					'client_secret'      => $client_secret,
					'locale'             => $this->payment->get_option( 'form_language' ),
				),
				'installments' => $installments
			), basename( MONRI_WC_PLUGIN_PATH ), MONRI_WC_PLUGIN_PATH . 'templates/' );
		}
	}

	public function prepare_blocks_data() {
		$client_secret = $this->request_authorize();

		return [
			'components' => [
				'authenticity_token' => $this->payment->get_option( 'monri_authenticity_token' ),
				'client_secret'      => $client_secret ?? "",
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

		$transaction_type        = ! empty( $transaction['transaction_type'] ) ?
			sanitize_text_field( $transaction['transaction_type'] ) :
			'';
		$transaction_response_id = isset( $transaction['transaction_response']['id'] ) ?
			sanitize_key( (string) $transaction['transaction_response']['id'] ) :
			'';
		if ( $response_code === '0000' ) {
			if ( $transaction_type === 'purchase' ) {
				$order->payment_complete( $transaction_response_id );
			} else {
				$order->update_status( 'on-hold', __( 'Order awaiting payment', 'monri' ) );
			}
			$amount_in_minor_units = (int) round( $order->get_total() * 100 );
			WC()->session->set( (string) $amount_in_minor_units . '_client_secret_timestamp', '' );
			$monri_order_number = $transaction['transaction_response']['order_number'] ?
				sanitize_key( $transaction['transaction_response']['order_number'] ) :
				'';

			/* translators: %s: generated id which represents order number */
			$order->add_order_note( sprintf( __( 'Order number in Monri administration: %s', 'monri' ), $monri_order_number ) );

			WC()->cart->empty_cart();
			$order->update_meta_data( 'monri_order_number', $monri_order_number );
			$order->save();

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
	 * @return string
	 */
	private function request_authorize() {

		$url = $this->payment->get_option_bool( 'test_mode' ) ?
			self::AUTHORIZATION_ENDPOINT_TEST :
			self::AUTHORIZATION_ENDPOINT;

		if ( is_admin() ) { // admin page editor
			$order_total = 10;
		} else {
			$order_total = (float) WC()->cart->get_total( 'edit' );
		}

		$amount_in_minor_units = (int) round( $order_total * 100 );
		$session_client_secret = $this->get_session_client_secret( $amount_in_minor_units );
		if ( ! empty( $session_client_secret ) ) {
			return $session_client_secret;
		}

		$currency = get_woocommerce_currency();
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}

		$data = [
			'amount'           => $amount_in_minor_units,
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

		Monri_WC_Logger::log( $data, __METHOD__ );

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

		Monri_WC_Logger::log( $response, __METHOD__ );

		if ( is_wp_error( $response ) ) {
			$response = [ 'status' => 'error', 'error' => $response->get_error_message() ];
		}

		$body          = wp_remote_retrieve_body( $response );
		$client_secret = json_decode( $body, true )['client_secret'];
		// save data to session so that we can reuse it on site refresh
		WC()->session->set(
			$amount_in_minor_units . '_client_secret_timestamp',
			$amount_in_minor_units . '_' . $client_secret . '_' . time()
		);

		return $client_secret;
	}


	/**
	 * Process a refund
	 *
	 * @param int $order_id
	 * @param float $amount
	 * @param string $reason
	 *
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null ) {

		$order          = wc_get_order( $order_id );
		$monri_order_id = $order->get_meta( 'monri_order_number' );
		$currency       = $order->get_currency();

		if ( empty( $monri_order_id ) ) {
			$order->add_order_note( sprintf( __( 'There was an error submitting the refund to Monri.', 'monri' ) ) );

			return false;
		}

		$response = Monri_WC_Api::instance()->refund( $monri_order_id, $amount * 100, $currency );

		if ( is_wp_error( $response ) ) {
			$order->add_order_note( sprintf( __( 'There was an error submitting the refund to Monri.', 'monri' ) ) );

			return false;
		}
		$order->update_meta_data( '_monri_should_close_parent_transaction', '1' );
		$order->save();
		$order->add_order_note( sprintf(
		    /* translators: %s: amount which was successfully refunded */
			__( 'Refund of %s successfully sent to Monri.', 'monri' ),
			wc_price( $amount, array( 'currency' => $currency ) )
		) );

		return true;
	}

	/**
	 * Can the order be refunded
	 *
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		return $order && in_array( $order->get_status(), wc_get_is_paid_statuses() ) &&
		       ! $order->get_meta( '_monri_should_close_parent_transaction' );
	}

	/**
	 * Capture order on Monri side
	 *
	 * @param int $order_id
	 * @param string $from
	 * @param string $to
	 *
	 * @return bool
	 */
	public function process_capture( $order_id, $from, $to ) {

		if ( ! ( in_array( $from, [ 'pending', 'on-hold' ] ) && in_array( $to, wc_get_is_paid_statuses() ) ) ) {
			return false;
		}
		$order          = wc_get_order( $order_id );
		$monri_order_id = $order->get_meta( 'monri_order_number' );
		if ( empty( $monri_order_id ) ) {
			return false;
		}
		$currency = $order->get_currency();
		$amount   = $order->get_total() - $order->get_total_refunded();

		if ( $amount < 0.01 ) {
			return false;
		}

		$response = Monri_WC_Api::instance()->capture( $monri_order_id, $amount * 100, $currency );

		if ( is_wp_error( $response ) ) {
			$order->add_order_note(
				sprintf( __( 'There was an error submitting the capture to Monri.', 'monri' ) ) .
				' ' .
				$response->get_error_message()
			);

			return false;
		}

		$order->payment_complete( $monri_order_id );
		$order->add_order_note( sprintf(
		    /* translators: %s: amount which was successfully captured */
			__( 'Capture of %s successfully sent to Monri.', 'monri' ),
			wc_price( $amount, array( 'currency' => $order->get_currency() ) )
		) );

		return true;
	}

	/**
	 * Void order on Monri side
	 *
	 * @param $order_id
	 * @param string $from
	 * @param string $to
	 *
	 * @return bool
	 */
	public function process_void( $order_id, $from, $to ) {

		if ( ! ( in_array( $from, [ 'pending', 'on-hold' ] ) && in_array( $to, [ 'cancelled', 'failed' ] ) ) ) {
			return false;
		}

		$order          = wc_get_order( $order_id );
		$monri_order_id = $order->get_meta( 'monri_order_number' );
		if ( empty( $monri_order_id ) ) {
			return false;
		}
		$amount   = $order->get_total() - $order->get_total_refunded();
		$currency = $order->get_currency();
		if ( $amount < 0.01 ) {
			return false;
		}

		$response = Monri_WC_Api::instance()->void( $monri_order_id, $amount * 100, $currency );

		if ( is_wp_error( $response ) ) {
			$order->add_order_note(
				sprintf( __( 'There was an error submitting the void to Monri.', 'monri' ) ) .
				' ' .
				$response->get_error_message()
			);

			return false;
		}

		$order->add_order_note( sprintf(
		    /* translators: %s: amount which was successfully voided */
			__( 'Void of %s successfully sent to Monri.', 'monri' ),
			wc_price( $amount, array( 'currency' => $order->get_currency() ) )
		) );

		return true;
	}

	/**
	 * Return client secret from session if it is valid
	 *
	 * @param int $amount_in_minor_units
	 *
	 * @return string
	 */
	public function get_session_client_secret( $amount_in_minor_units ) {
		// @todo: find out exact time
		$allowed_time_seconds           = 900;
		$amount_client_secret_timestamp = WC()->session->get( (string) $amount_in_minor_units . '_client_secret_timestamp' );

		if ( ! empty( $amount_client_secret_timestamp ) ) {
			$amount_client_secret_timestamp = explode( '_', $amount_client_secret_timestamp );
			if ( ! empty( $amount_client_secret_timestamp[0] ) ) {
				$amount = (int) $amount_client_secret_timestamp[0];
			}
			if ( ! empty( $amount_client_secret_timestamp[1] ) ) {
				$client_secret = $amount_client_secret_timestamp[1];
			}
			if ( ! empty( $amount_client_secret_timestamp[2] ) ) {
				$timestamp = (int) $amount_client_secret_timestamp[2];
			}
			if ( ! empty( $amount ) && $amount === $amount_in_minor_units
			     && ! empty( $client_secret ) && ! empty( $timestamp ) &&
			     ( time() - $timestamp ) <= $allowed_time_seconds ) {
				return $client_secret;
			}
		}

		return null;
	}

	/**
	 * Validate TOC on Monri woocommerce_checkout_update_totals ajax request
	 *
	 * @param array $data
	 * @param WP_Error $errors
	 *
	 * @return void
	 */
	public function after_checkout_validation( $data, $errors ) {

		if ( empty( $_POST['monri_components_checkout_validation'] ) ) {
			return;
		}

		if ( !empty( $data['woocommerce_checkout_update_totals'] ) && empty( $data['terms'] ) && ! empty( $data['terms-field'] ) ) {
			$errors->add( 'terms', __( 'Please read and accept the terms and conditions to proceed with your order.', 'woocommerce' ) );
		}
	}
}
