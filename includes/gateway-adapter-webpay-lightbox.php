<?php

require_once __DIR__ . '/gateway-adapter-webpay-form.php';

class Monri_WC_Gateway_Adapter_Webpay_Lightbox extends Monri_WC_Gateway_Adapter_Webpay_Form {

	/**
	 * Adapter ID
	 */
	public const ADAPTER_ID = 'webpay_lightbox';

	public const ENDPOINT_TEST = 'https://ipgtest.monri.com/dist/lightbox.js';
	public const ENDPOINT = 'https://ipg.monri.com/dist/lightbox.js';


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
		parent::init( $payment );

		// load iframe resizer on receipt page
		add_action(
			'template_redirect',
			function () {
				if ( is_checkout_pay_page() ) {
					wp_enqueue_script(
						'monri-iframe-resizer',
						MONRI_WC_PLUGIN_URL . 'assets/js/iframe-resizer.parent.js',
						[],
						MONRI_WC_VERSION,
						false
					);
				}
			}
		);
		add_action( 'woocommerce_before_thankyou', [ $this, 'process_return' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'process_capture' ], null, 4 );
		add_action( 'woocommerce_order_status_changed', [ $this, 'process_void' ], null, 4 );
		add_action( 'woocommerce_receipt_' . $this->payment->id, [ $this, 'process_payment' ] );

		// load lightbox.js on frontend checkout
//		add_action( 'template_redirect', function () {
//			if ( is_checkout() ) {
//				$script_url = $this->payment->get_option_bool( 'test_mode' ) ? self::ENDPOINT_TEST : self::ENDPOINT;
//				wp_enqueue_script( 'monri-components', $script_url, [], MONRI_WC_VERSION );
//			}
//		} );
	}


	/**
	 * Redirect to receipt page
	 *
	 * @param $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		$order_pay_url = $order->get_checkout_payment_url( true );

		$total = (int) wc_get_order( $order_id )->get_total() * 100 ;
		$currency = get_woocommerce_currency();
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}

		//Generate digest key
		$key   = $this->payment->get_option( 'monri_merchant_key' );
		$token = $this->payment->get_option( 'monri_authenticity_token' );
		$digest = hash( 'sha512', $key . $order_id . $total . $currency );
		$config = [
			'src' => $this->payment->get_option_bool( 'test_mode' ) ? self::ENDPOINT_TEST : self::ENDPOINT,
			'data-authenticity-token' => $token,
			'data-amount'           => $total,
			'data-currency'         => $currency,
			'data-order-number'         => $order_id,
			'data-order-info'         => 'Monri Lightbox',
			'data-digest'         => $digest,
			'data-transaction-type'         => $this->payment->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase',
			'data-language'         => $this->payment->get_option( 'form_language' ),
			'data-success-url-override'  => $this->payment->get_return_url( $order ) . '&nocache=1',
			'data-cancel-url-override'   => $order->get_cancel_order_url(),
		];

		$order->add_meta_data( 'monri_transaction_type', $config['data-transaction-type'] );
		$order->save();

		return [
			'result'   => 'success',
			// new checkout will create order but wont redirect if there is no redirect url
			//'redirect' => $order_pay_url,
			'messages' => [],
			'monri_data'=> $config
		];
	}


	/**
	 * Old checkout
	 * @return void
	 */
	public function payment_fields() {
		// Prevents rendering this file multiple times - JS part gets duplicated and executed twice
		if ( isset( $_REQUEST['wc-ajax'] ) && $_REQUEST['wc-ajax'] === "update_order_review" ) {
			wc_get_template( 'lightbox-iframe-form.php', array(
			), basename( MONRI_WC_PLUGIN_PATH ), MONRI_WC_PLUGIN_PATH . 'templates/' );
		}
	}

	/*
	 * New checkout
	 */
	public function prepare_blocks_data() {
		$cart = WC()->cart;
		$total = (float) $cart->get_total( 'edit' );
		$cart_id = $cart->get_cart_hash();
		$currency = get_woocommerce_currency();
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}

		//Generate digest key
		$key   = $this->payment->get_option( 'monri_merchant_key' );
		$token = $this->payment->get_option( 'monri_authenticity_token' );
		$digest = hash( 'sha512', $key . $cart_id . $total . $currency );

		return [
			'lightbox' => [
				'data-authenticity-token' => $token,
				'data-amount'           => $cart->get_total(),
				'data-currency'         => $currency,
				'data-order-number'         => $cart_id,
				'data-order-info'         => 'Monri Lightbox',
				'data-digest'         => $digest,
				'data-transaction-type'         => $this->payment->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase',
				'data-language'         => $this->payment->get_option( 'form_language' ),
			]
		];
	}

	// Xdebug wont pause here after we pay and go to success url??
	public function process_return( $order_id ) {

		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_payment_method() !== $this->payment->id ) {
			return;
		}
		Monri_WC_Logger::log( "Response data: " . sanitize_textarea_field( print_r( $_GET, true ) ), __METHOD__ );

		$requested_order_id = sanitize_text_field( $_GET['order_number'] );
		if ( $this->payment->get_option_bool( 'test_mode' ) ) {
			$requested_order_id = Monri_WC_Utils::resolve_real_order_id( $order_id );
		}

		if ( $order_id != $requested_order_id ) {
			return;
		}

		if ( ! $this->validate_monri_response( $order ) ) {
			return;
		}

		if ( ! in_array( $order->get_status(), [ 'pending', 'failed' ], true ) ) {
			return;
		}

		$response_code    = ! empty( $_GET['response_code'] ) ? sanitize_text_field( $_GET['response_code'] ) : '';
		$transaction_type = $order->get_meta( 'monri_transaction_type' );
		Monri_WC_Logger::log( "Transaction type: " . $order->get_meta( 'monri_transaction_type' ), __METHOD__ );
		if ( $response_code === '0000' ) {
			if ( $transaction_type === 'purchase' ) {
				$order->payment_complete();
			} else {
				$order->update_status( 'on-hold', __( 'Order awaiting payment', 'monri' ) );
			}

			$approval_code = ! empty( $_GET['approval_code'] ) ? sanitize_text_field( $_GET['approval_code'] ) : '';
			if ( $approval_code ) {
				$order->add_order_note( __( 'Monri payment successful<br/>Approval code: ', 'monri' ) . $approval_code );
			}

			$issuer = ! empty( $_GET['issuer'] ) ? sanitize_text_field( $_GET['issuer'] ) : '';
			if ( $issuer ) {
				$order->add_order_note( 'Issuer: ' . $issuer );
			}

			$number_of_installments = ! empty( $_GET['number_of_installments'] ) ? (int) $_GET['number_of_installments'] : 0;
			if ( $number_of_installments > 1 ) {
				$order->add_order_note( __( 'Number of installments: ', 'monri' ) . $number_of_installments );
			}

			WC()->cart->empty_cart();
			$order->update_meta_data( 'monri_order_number', sanitize_key( $_GET['order_number'] ) );
			$order->save();

		} else {
			$order->update_status( 'failed', "Response not authorized - response code is $response_code." );
			//$order->add_order_note( __( 'Transaction Declined: ', 'monri' ) . sanitize_text_field( $_GET['Error'] ) );
		}

	}

	/**
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	private function validate_monri_response( $order ) {

		// validate digest hash format
		if ( empty( $_GET['digest'] ) || ! preg_match( '/^[a-f0-9]{128}$/', $_GET['digest'] ) ) {
			return false;
		}

		$digest = Monri_WC_Utils::sanitize_hash( $_GET['digest'] );

		$calculated_url = $this->payment->get_return_url( $order ); // use current url?
		$calculated_url = strtok( $calculated_url, '?' );

		$arr = explode( '?', $_SERVER['REQUEST_URI'] );

		// If there's more than one '?' shift and join with ?, it's special case of having '?' in success url
		// eg https://test.com/?page_id=6order-recieved?
		if ( count( $arr ) > 2 ) {
			array_shift( $arr );
			$query_string = implode( '?', $arr );
		} else {
			$query_string = end( $arr );
		}

		$calculated_url .= '?' . $query_string;
		$calculated_url = preg_replace( '/&digest=[^&]*/', '', $calculated_url );

		//generate known digest
		$check_digest = hash( 'sha512', $this->payment->get_option( 'monri_merchant_key' ) . $calculated_url );

		return hash_equals( $check_digest, $digest );
	}

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
		$formatted_response = json_decode(json_encode($response), true);
		if ( is_wp_error( $response ) || !(isset( $formatted_response['response-code']) && $formatted_response['response-code'] === '0000')) {
			Monri_WC_Logger::log( $formatted_response, __METHOD__ );
			$order->add_order_note(
				sprintf( __( 'There was an error submitting the capture to Monri.', 'monri' ) )
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
		$formatted_response = json_decode(json_encode($response), true);
		if ( is_wp_error( $response ) || !(isset( $formatted_response['response-code']) && $formatted_response['response-code'] === '0000')) {
			Monri_WC_Logger::log( $formatted_response, __METHOD__ );
			$order->add_order_note(
				sprintf( __( 'There was an error submitting the void to Monri.', 'monri' ) )
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
}