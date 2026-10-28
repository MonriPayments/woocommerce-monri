<?php

class Monri_WC_Order_Processing {

	/**
	 * @param WC_Order $order
	 * @param array $payload Sanitized Payload should enter here, if sanitization is needed
	 *
	 * @return void
	 */
	public static function process_order( WC_Order $order, $payload ) {

		if ( ! in_array( $order->get_status(), array( 'pending', 'failed' ), true ) ) {
			return;
		}

		// @todo: remove sanitize here if we sanitized before

		$response_code    = ! empty( $payload['response_code'] ) ? sanitize_text_field( $payload['response_code'] ) : '';
		$transaction_type = $payload['transaction_type'] ?? $order->get_meta( 'monri_transaction_type' );
		//Monri_WC_Logger::log( 'Transaction type: ' . $order->get_meta( 'monri_transaction_type' ), __METHOD__ );

		// $payload['status'] === 'approved' - callback is currently checking this while others are not !!

		if ( $response_code === '0000' ) {
			if ( $transaction_type === 'purchase' ) {
				$order->payment_complete();
			} else {
				$order->update_status( 'on-hold', __( 'Order awaiting payment', 'monri' ) );
			}

			// @todo: notes text changed, need translations

			$note = [
				__( 'Monri payment successful.', 'monri' )
			];

			$approval_code = ! empty( $payload['approval_code'] ) ? sanitize_text_field( $payload['approval_code'] ) : '';
			if ( $approval_code ) {
				$note[] = sprintf( __( 'Approval code: %s', 'monri' ), $approval_code );
			}

			$issuer = ! empty( $payload['issuer'] ) ? sanitize_text_field( $payload['issuer'] ) : '';
			if ( $issuer ) {
				$note[] = sprintf( __( 'Issuer: %s', 'monri' ), $issuer );
			}

			$number_of_installments = ! empty( $payload['number_of_installments'] ) ? (int) $payload['number_of_installments'] : 0;
			if ( $number_of_installments > 1 ) {
				$note[] = sprintf( __( 'Number of installments: %s', 'monri' ), $number_of_installments );
			}

			$order->add_order_note( implode( '<br/>', $note ) );

			// @todo: used only for old components and "test-" orders, not needed hre
			//if ( ! empty( $payload['order_number'] ) && $payload['order_number'] != $order->get_id() ) {
			//	$order->update_meta_data( 'monri_order_number', sanitize_key( $payload['order_number'] ) );
			//	$order->save();
			//}
			//

			// save token if needed
			if ( $order->get_user_id() &&
			     Monri_WC_Settings::instance()->get_option_bool( 'monri_web_pay_tokenization_enabled' )
			) {
				$token_data = [];
				foreach ( [ 'cc_type', 'masked_pan', 'pan_token' ] as $key ) {
					if ( isset( $payload[ $key ] ) ) {
						$token_data[ $key ] = sanitize_text_field( $payload[ $key ] );
					}
				}
				self::save_user_token( $order->get_user_id(), $token_data );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

		} else if ( $response_code === '1050' ) { // only in new components, is this always when Canceled ??

				//if ( $order->get_status() === 'pending' ) { // not needed?
					$order->update_status( 'cancelled' );
				//}

		} else {
			$order->update_status( 'failed', "Response not authorized - response code is $response_code." );
			// $order->add_order_note( __( 'Transaction Declined: ', 'monri' ) . sanitize_text_field( $_GET['Error'] ) );
		}

	}

	/**
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public static function process_order_from_request( $order ) {
		// sanitize here? possible?
		$payload = $_GET;
		$key_fields = [ 'order_number' ]; // specify your keys here

		array_walk_recursive( $payload, function ( &$value, $key ) use ( $key_fields ) {
			if ( is_string( $value ) ) {
				$value = in_array( $key, $key_fields, true )
					? sanitize_key( $value )
					: sanitize_text_field( $value );
			}
		} );

		self::process_order( $order, $payload );
	}

	/**
	 * @param WC_Order $order
	 * @param array $payload
	 *
	 * @return void
	 */
	public static function process_order_from_callback( $order, $payload ) {
		// $payload['status'] === 'approved' - callback is currently checking this while others are not !!
		self::process_order( $order, $payload );
	}

	/**
	 * @param WC_Order $order
	 * @param array $payload
	 *
	 * @return void
	 */
	public static function process_order_from_show_api( $order, $payload ) {
		// replace "-" to "_"
		$payload = array_combine(
			str_replace( '-', '_', array_keys( $payload ) ),
			array_values( $payload )
		);

		self::process_order( $order, $payload );
	}

	/**
	 * Process a refund
	 *
	 * @param int $order_id
	 * @param float|null $amount
	 *
	 * @return bool
	 */
	public static function process_order_refund( $order_id, $amount = null ) {

		$order = wc_get_order( $order_id );
		$monri_order_id = $order->get_meta( 'monri_order_number' );

		if ( empty( $monri_order_id ) ) {
			$order->add_order_note(
				__( 'There was an error submitting the refund to Monri.', 'monri' )
			);

			return false;
		}

		$currency = $order->get_currency();
		if ( ! $amount ) {
			$amount = $order->get_total();
		}

		$response = Monri_WC_Api::instance()->refund( $monri_order_id, $amount * 100, $currency );
		$formatted_response = json_decode(wp_json_encode($response), true);

		if ( is_wp_error( $response ) ||
		     !( isset( $formatted_response['response-code']) && $formatted_response['response-code'] === '0000' )
		) {
			Monri_WC_Logger::log( $formatted_response, __METHOD__ );
			$order->add_order_note(
				__( 'There was an error submitting the refund to Monri.', 'monri' )
			);

			return false;
		}

		// flag to prevent further refunds of this order since Monri Webpay can only refund once
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
	 * Capture order on Monri side
	 *
	 * @param int $order_id
	 * @param string $from
	 * @param string $to
	 *
	 * @return bool
	 */
	public static function process_order_capture( $order_id ) {

		$order = wc_get_order( $order_id );
		//if ($order->get_payment_method() !== $this->payment->id ) {
		//	return;
		//}

		$monri_order_id = $order->get_meta( 'monri_order_number' );
		if ( empty( $monri_order_id ) ) {
			return false;
		}
		$currency = $order->get_currency();
		$amount   = $order->get_total() - (float)$order->get_total_refunded();

		if ( $amount < 0.01 ) {
			return false;
		}

		$response = Monri_WC_Api::instance()->capture( $monri_order_id, $amount * 100, $currency );
		$formatted_response = json_decode(wp_json_encode($response), true);
		if ( is_wp_error( $response ) ||
		     !( isset( $formatted_response['response-code'] ) && $formatted_response['response-code'] === '0000' )
		) {
			Monri_WC_Logger::log( $formatted_response, __METHOD__ );
			$order->add_order_note(
				__( 'There was an error submitting the capture to Monri.', 'monri' )
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
	 * @param $order_id
	 *
	 * @return bool
	 */
	public static function process_order_void( $order_id ) {

		$order = wc_get_order( $order_id );
		//if ($order->get_payment_method() !== $this->payment->id ) {
		//	return;
		//}

		$monri_order_id = $order->get_meta( 'monri_order_number' );
		if ( empty( $monri_order_id ) ) {
			return false;
		}
		$amount   = $order->get_total() - (float)$order->get_total_refunded();
		$currency = $order->get_currency();
		if ( $amount < 0.01 ) {
			return false;
		}

		$response = Monri_WC_Api::instance()->void( $monri_order_id, $amount * 100, $currency );
		$formatted_response = json_decode(wp_json_encode($response), true);

		if ( is_wp_error( $response ) ||
		     !( isset( $formatted_response['response-code'] ) && $formatted_response['response-code'] === '0000' )
		) {
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

	// just an idea, call show api here and process_order_from_show_api() afterwards
	public static function process_order_sync() {
	}

	/**
	 * @param int $user_id
	 * @param array $data
	 *
	 * @return void
	 */
	public static function save_user_token( $user_id, $data ) {

		if ( ! isset( $data['pan_token'], $data['cc_type'], $data['masked_pan'] ) ) {
			return;
		}
		if (self::check_if_token_already_exists($user_id, $data['masked_pan'])) {
			return;
		}

		$wc_token = new Monri_WC_Payment_Token_Webpay();

		$wc_token->set_gateway_id( 'monri' /*$this->payment->id*/ );
		$wc_token->set_token( $data['pan_token'] );
		$wc_token->set_user_id( $user_id );

		$masked_pan_array = explode("-", $data['masked_pan']);
		$wc_token->set_last4( end( $masked_pan_array ) );
		$ccType = $data['cc_type'] ?? null;
		$wc_token->set_card_type( $ccType );

		$wc_token->save();
	}

	/**
	 * Check if a payment token already exists to avoid making duplicates
	 *
	 * @param $user_id
	 * @param $masked_pan
	 *
	 * @return bool
	 */
	private static function check_if_token_already_exists( $user_id, $masked_pan ) {

		$masked_pan_array = explode("-", $masked_pan);
		$last4 = end( $masked_pan_array );

		$user_tokens = WC_Payment_Tokens::get_customer_tokens( $user_id );
		foreach ($user_tokens as $user_token) {
			if (
				// Not every child of WC_Payment_Token is guaranteed to have get_last4() method.
				method_exists( $user_token, 'get_last4' )
				&& $user_token->get_last4() === $last4
			) {
				return true;
			}
		}
		return false;
	}
}
