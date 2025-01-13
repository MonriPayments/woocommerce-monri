<?php

class Monri_WC_Callback {

	public function init() {
		add_action( 'woocommerce_api_monri_callback', array( $this, 'handle_callback' ) );
	}

	/**
	 * Prints the error message and exits the process with a given HTTP Status Code.
	 *
	 * @param $message
	 * @param array   $status
	 */
	private function error( $message, array $status = array() ) {
		status_header( $status[0], $status[1] );
		header( 'Content-Type: text/plain' );

		echo esc_html( $message );
		exit( (int) $status[0] );
	}

	/**
	 * Forward callback request based on selected payment gateway service.
	 */
	public function handle_callback() {
		$payment_gateway_service = Monri_WC_Settings::instance()->get_option( 'monri_payment_gateway_service' );
		switch ( $payment_gateway_service ) {
			case 'monri-web-pay':
				$this->handle_monri_webpay_callback();
				break;
			case 'monri-ws-pay':
				$this->handle_monri_wspay_callback();
				break;
		}
	}

	/**
	 * Handles the given URL `$callback` as the callback URL for Monri Payment Gateway.
	 * This endpoint accepts only POST requests which have their payload in the PHP Input Stream.
	 * The payload must be a valid JSON.
	 */
	private function handle_monri_webpay_callback() {

		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			$this->error( 'Invalid request method.', array( 400, 'Bad Request' ) );
		}

		if ( empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$this->error( 'Authorization header missing.', array( 400, 'Bad Request' ) );
		}

		$authorization = sanitize_text_field( $_SERVER['HTTP_AUTHORIZATION'] );
		// Strip-out the 'WP3-callback' part from the Authorization header.
		$authorization = trim( str_replace( 'WP3-callback', '', $authorization ) );

		$merchant_key = Monri_WC_Settings::instance()->get_option( 'monri_merchant_key' );

		if ( empty( $merchant_key ) ) {
			$this->error( 'Merchant key not provided.', array( 404, 'Not found' ) );
		}

		$bad_request_header = array( 400, 'Bad Request' );

		// json post comming from Monri webhook, validated by header, not user input but trusted data
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$json = file_get_contents( 'php://input' );

		Monri_WC_Logger::log( 'Request data: ' . sanitize_textarea_field( $json ), __METHOD__ );

		// Calculating the digest...
		$digest = hash( 'sha512', $merchant_key . $json );

		// ... and comparing it with one from the headers.
		if ( $digest !== $authorization ) {
			$this->error( 'Invalid Authorization header', $bad_request_header );
		}

		try {
			$payload = json_decode( $json, true );
		} catch ( \Throwable $e ) {
			$this->error( 'Invalid request content', $bad_request_header );

		}

		if ( ! isset( $payload['order_number'] ) || ! isset( $payload['status'] ) ) {
			$this->error( 'Order information not found in request content.', $bad_request_header );
		}

		$order_number = $payload['order_number'];
		if ( Monri_WC_Settings::instance()->get_option( 'test_mode' ) ) {
			$order_number = Monri_WC_Utils::resolve_real_order_id( $order_number );
		}

		try {
			$order = wc_get_order( $order_number );

			if ( $order->get_status() !== 'pending' ) {
				return;
			}

			$valid_response_code = isset( $payload['response_code'] ) && $payload['response_code'] === '0000';
			$transaction_type    = $order->get_meta( 'monri_transaction_type' );
			if ( $payload['status'] === 'approved' && $valid_response_code ) {
				if ( $transaction_type === 'purchase' ) {
					$order->payment_complete();
				} else {
					$order->update_status( 'on-hold', __( 'Order awaiting payment', 'monri' ) );
				}

				$order->update_meta_data( 'monri_order_number', $payload['order_number'] ?? '' );
				$order->save();
			} else {
				$order->update_status( 'cancelled' );
			}
		} catch ( \Exception $e ) {
			$message = sprintf( 'Order ID: %s not found or does not exist.', $order_number );
			$this->error( $message, array( 404, 'Not Found' ) );
		}
	}

	/**
	 * Handles Monri WSPay callback. Must be a POST request
	 */
	private function handle_monri_wspay_callback() {
		$bad_request = array( 400, 'Bad Request' );
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			$this->error( 'Invalid request method.', $bad_request );
		}

		$json = file_get_contents( 'php://input' );
		Monri_WC_Logger::log( 'Request data: ' . sanitize_textarea_field( $json ), __METHOD__ );

		try {
			$payload = json_decode( $json, true );
		} catch ( \Throwable $e ) {
			$this->error( 'Invalid request content', $bad_request );

		}

		if ( ! $this->validate_monri_wspay_callback( $payload ) ) {
			$this->error( 'Invalid signature.', $bad_request );
		}

		if ( ! in_array( $this->get_monri_wspay_callback_action( $payload ), array( 'Refunded', 'Voided', 'Completed', 'Authorized' ) ) ) {
			$this->error( 'Invalid action.', $bad_request );
		}

		if ( ! isset( $payload['ShoppingCartID'] ) ) {
			$this->error( 'Order information not found in request content.', $bad_request );
		}

		$order_number = sanitize_text_field( $payload['ShoppingCartID'] );
		if ( Monri_WC_Settings::instance()->get_option( 'test_mode' ) ) {
			$order_number = Monri_WC_Utils::resolve_real_order_id( $order_number );
		}

		$order = wc_get_order( $order_number );
		if ( ! $order ) {
			$this->error( 'Payment order not found.', $bad_request );
		}

		try {
			$transaction_info = $order->get_meta( '_monri_transaction_info' );

			$valid_response_code = isset( $payload['ActionSuccess'] ) && $payload['ActionSuccess'] === '1';

			if ( $valid_response_code ) {
				if ( isset( $payload['Voided'] ) && $payload['Voided'] === '1' && $order->get_status() !== 'cancelled' ) {
					$order->update_status( 'cancelled' );
					return;
				}
				if ( empty( $transaction_info ) && isset( $payload['Authorized'] ) && $payload['Authorized'] === '1' && in_array( $order->get_status(), array( 'pending', 'on-hold' ) ) ) {
					$order->update_meta_data( '_monri_transaction_info', $this->get_monri_wspay_transaction_data( $payload ) );
					$order->update_status( 'on-hold', __( 'Order awaiting payment', 'monri' ) );
					$order->save_meta_data();
				}
				if ( isset( $payload['Completed'] ) && $payload['Completed'] === '1' && ! in_array( $order->get_status(), array( 'completed', 'refunded' ) ) ) {
					$order->payment_complete();
					return;
				}

				if ( $order->get_user_id() && isset( $payload['Token'], $payload['TokenNumber'], $payload['ExpirationDate'] ) ) {

					$token_data = [
						'Token' => sanitize_text_field($payload['Token']),
						'TokenNumber' => sanitize_text_field($payload['TokenNumber']),
						'TokenExp' => sanitize_text_field($payload['ExpirationDate']),
						'CreditCardName' => sanitize_text_field($payload['CreditCardName']) ?? null,
					];

					$wspay = new Monri_WC_Gateway_Adapter_Wspay();
					$wspay->init( new Monri_WC_Gateway() );
					$wspay->save_user_token( $order->get_user_id(), $token_data );
				}

			} else {
				$order->update_status( 'cancelled' );
			}
		} catch ( \Exception $e ) {
			$message = sprintf( 'Order ID: %s not found or does not exist.', $order_number );
			$this->error( $message, array( 404, 'Not Found' ) );
		}
	}

	/**
	 * Validate the Monri WSPay callback request
	 *
	 * @param string[] $payload
	 *
	 * @return bool
	 */
	private function validate_monri_wspay_callback( $payload ) {
		$is_tokenization = $payload['ShopID'] === Monri_WC_Settings::instance()->get_option( 'monri_ws_pay_form_tokenization_shop_id' );
		$shop_id         = $is_tokenization ? Monri_WC_Settings::instance()->get_option( 'monri_ws_pay_form_tokenization_shop_id' ) :
			Monri_WC_Settings::instance()->get_option( 'monri_ws_pay_form_shop_id' );
		$secret_key      = $is_tokenization ? Monri_WC_Settings::instance()->get_option( 'monri_ws_pay_form_tokenization_secret' ) :
			Monri_WC_Settings::instance()->get_option( 'monri_ws_pay_form_secret' );
		$action_success  = sanitize_text_field( $payload['ActionSuccess'] ?? '' );
		$approval_code   = sanitize_text_field( $payload['ApprovalCode'] ?? '' );
		$wspay_order_id  = sanitize_text_field( $payload['WsPayOrderId'] ?? '' );

		$signature =
			$shop_id . $secret_key .
			$action_success .
			$approval_code .
			$secret_key . $shop_id .
			$approval_code .
			$wspay_order_id;

		$payload_signature = $payload['Signature'] ?? '';
		return $payload_signature === hash( 'sha512', $signature );
	}

	/**
	 *  Get callback action name
	 *
	 * @param string[] $payload
	 *
	 * @return string
	 */
	private function get_monri_wspay_callback_action( $payload ) {
		$ordered_actions = array( 'Refunded', 'Voided', 'Completed', 'Authorized' );
		$result          = 'Unknown';
		foreach ( $ordered_actions as $action ) {
			if ( isset( $payload[ $action ] ) && $payload[ $action ] === '1' ) {
				$result = $action;
				break;
			}
		}

		return $result;
	}

	/**
	 * Get order transaction data. Used for WSPay API calls
	 *
	 * @param string[] $payload
	 *
	 * @return array
	 */
	private function get_monri_wspay_transaction_data( $payload ) {
		$transaction_data     = array();
		$transaction_info_map = array(
			'WsPayOrderId' => 'Transaction ID',
			'ApprovalCode' => 'Approval code',
			'PaymentPlan'  => 'Payment plan',
			'STAN'         => 'STAN',
			'Amount'       => 'Amount',
		);

		foreach ( array_keys( $transaction_info_map ) as $key ) {
			if ( isset( $payload[ $key ] ) ) {
				$transaction_data[ $key ] = sanitize_text_field( $payload[ $key ] );
			}
		}
		return $transaction_data;
	}
}
