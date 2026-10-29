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

		$transaction_type = $this->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase';

		$data = array(
			'amount'           => $amount_in_minor_units,
			'order_number'     => $order_id,
			'currency'         => $currency,
			'transaction_type' => $transaction_type,
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

		$order->update_meta_data( 'monri_order_number', $order_id );
		$order->update_meta_data( 'monri_transaction_type', $transaction_type );
		//used when checking if current user has permission to get status of this order
		$order_hash = wp_generate_uuid4();
		$order->update_meta_data('order_access_hash', $order_hash);


		$order->save();

		return json_decode( $body, true )['client_secret'] ?? '';
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

	/**
	 * Monri returns on thankyou page
	 *
	 * @param int $order_id
	 *
	 * @return void
	 */
	public function process_return( $order_id ) {

		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_payment_method() !== $this->id ) {
			return;
		}

		$this->sync_order_status( $order );
	}

	/**
	 * Checks order status on Monri and updates order accordingly
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
	public function sync_order_status( $order ) {

		// @todo: not needed?
		$monri_order_number = $order->get_meta( 'monri_order_number' );

		if ( ! $monri_order_number ) {
			return false;
		}

		$response = Monri_WC_Api::instance()->orders_show( $monri_order_number );
		$formatted_response = json_decode( wp_json_encode( $response ), true );
		Monri_WC_Logger::log( $formatted_response, __METHOD__ );

		if ( is_wp_error( $response ) ) {
			$order->add_order_note(
				__( 'There was an error getting the order status', 'monri' )
			);

			return false;
		}

		if ( !isset( $formatted_response['response-code'] ) ) {
			return false;
		}

		if ( Monri_WC_Utils::order_lock( $monri_order_number ) === false ) {
			return true;
		}

		Monri_WC_Order_Processing::process_order_from_show_api( $order, $formatted_response );

		Monri_WC_Utils::order_unlock( $monri_order_number );

		return true;
	}

	/**
	 * Process a refund
	 *
	 * @param int        $order_id
	 * @param float|null $amount Refund amount.
	 * @param string     $reason
	 *
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		$order = wc_get_order( $order_id );
		if ( $order->get_payment_method() !== $this->id ) {
			return false;
		}

		return Monri_WC_Order_Processing::process_order_refund( $order_id, $amount );
	}

	/**
	 * Capture order on Monri side
	 *
	 * @param int    $order_id
	 * @param string $from
	 * @param string $to
	 *
	 * @return bool
	 */
	public function process_capture( $order_id, $from, $to ) {

		if ( ! (
			in_array( $from, [ 'pending', 'on-hold' ], true ) &&
			in_array( $to, wc_get_is_paid_statuses(), true )
		) ) {
			return false;
		}

		$order = wc_get_order( $order_id );
		if ( $order->get_payment_method() !== $this->id ) {
			return false;
		}

		if ( $order->get_meta( 'monri_transaction_type' ) === 'purchase' ) {
			return false;
		}

		return Monri_WC_Order_Processing::process_order_capture( $order_id );
	}

	/**
	 * Void order on Monri side
	 *
	 * @param $order_id
	 * @param string   $from
	 * @param string   $to
	 *
	 * @return bool
	 */
	public function process_void( $order_id, $from, $to ) {

		if ( ! (
			in_array( $from, [ 'pending', 'on-hold' ], true ) &&
			in_array( $to, [ 'cancelled', 'failed' ], true )
		) ) {
			return false;
		}

		$order = wc_get_order( $order_id );
		if ( $order->get_payment_method() !== $this->id ) {
			return false;
		}

		return Monri_WC_Order_Processing::process_order_void( $order_id );
	}

	public function monri_get_transaction_status_rest( $request ) {

		$order_number = sanitize_text_field($request['order_number']);

		$response = Monri_WC_Api::instance()->orders_show($order_number);

		if (is_wp_error($response)) {
			return new WP_Error('order_error', $response->get_error_message(), array('status' => 400));
		}
		$formatted_response = json_decode( wp_json_encode( $response ), true );
		return $formatted_response['status'] === 'approved' && $formatted_response['response-code'] === '0000';
	}

	/**
	 * Check if current user has permission to fetch order status
	 *
	 * @param $request
	 *
	 * @return bool
	 */
	public function monri_transaction_status_permission( $request ) {

		$order_number = $request->get_param('order_number');
		if ( $this->get_option_bool( 'test_mode' ) ) {
			$order_number = Monri_WC_Utils::resolve_real_order_id( $order_number );
		}

		$order = wc_get_order($order_number);
		$request_order_hash = $request->get_param('order_hash');
		if (!$order || !$request_order_hash) {
			return false;
		}

		return $request_order_hash === $order->get_meta('order_access_hash');
	}

}
