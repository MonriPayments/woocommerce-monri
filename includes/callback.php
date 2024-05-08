<?php

class Monri_WC_Callback {

	public function init() {
		add_action( 'woocommerce_api_monri_callback', [ $this, 'handle_callback' ] );
	}

	/**
	 * Prints the error message and exits the process with a given HTTP Status Code.
	 *
	 * @param $message
	 * @param array $status
	 */
	private function error( $message, array $status = array() ) {
		status_header($status[0], $status[1]);
		header( 'Content-Type: text/plain' );

		echo esc_html($message);
		exit( (int) $status[0] );
	}

	/**
	 * Handles the given URL `$callback` as the callback URL for Monri Payment Gateway.
	 * This endpoint accepts only POST requests which have their payload in the PHP Input Stream.
	 * The payload must be a valid JSON.
	 *
	 */
	public function handle_callback() {

		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			$this->error( 'Invalid request method.', [ 400, 'Bad Request' ] );
		}

		if ( empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$this->error( 'Authorization header missing.', [ 400, 'Bad Request' ] );
		}

		$authorization = sanitize_text_field( $_SERVER['HTTP_AUTHORIZATION'] );
		// Strip-out the 'WP3-callback' part from the Authorization header.
		$authorization = trim( str_replace( 'WP3-callback', '', $authorization ) );

		$merchant_key = Monri_WC_Settings::instance()->get_option( 'monri_merchant_key' );

		if ( empty( $merchant_key ) ) {
			$this->error( 'Merchant key not provided.', [ 404, 'Not found' ] );
		}

		$bad_request_header = [ 400, 'Bad Request' ];

		// json post comming from Monri webhook, validated by header, not user input but trusted data
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$json = file_get_contents( 'php://input' );

		Monri_WC_Logger::log( "Request data: " . sanitize_textarea_field( $json ), __METHOD__ );

		// Calculating the digest...
		$digest = hash( 'sha512', $merchant_key . $json );

		// ... and comparing it with one from the headers.
		if ( $digest !== $authorization ) {
			$this->error( 'Invalid Authorization header', $bad_request_header );
		}

		try {
			$payload = json_decode( $json, true );
		} catch ( \Throwable $e ) {
			$this->error( 'Invalid request content', $bad_request_header );;
		}

		if ( ! isset( $payload['order_number'] ) || ! isset( $payload['status'] ) ) {
			$this->error( 'Order information not found in request content.', $bad_request_header );
		}

		$order_number = $payload['order_number'];
		if (Monri_WC_Settings::instance()->get_option( 'test_mode' )) {
			$order_number = Monri_WC_Utils::resolve_real_order_id($order_number);
		}

		try {
			$order = wc_get_order( $order_number );

			if ( $order->get_status() !== 'pending' ) {
				return;
			}

            $valid_response_code = isset( $payload['response_code'] ) && $payload['response_code'] === "0000";

			if ( $payload['status'] === 'approved' && $valid_response_code ) {
				$order->payment_complete();
			} else {
				$order->update_status( 'cancelled' );
			}

		} catch ( \Exception $e ) {
			$message = sprintf( 'Order ID: %s not found or does not exist.', $order_number );
			$this->error( $message, array( 404, 'Not Found' ) );
		}

	}
}
