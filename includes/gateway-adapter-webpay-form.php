<?php

class Monri_WC_Gateway_Adapter_Webpay_Form {

	/**
	 * Adapter ID
	 */
	public const ADAPTER_ID = 'webpay_form';

	public const ENDPOINT_TEST = 'https://ipgtest.monri.com/v2/form';
	public const ENDPOINT = 'https://ipg.monri.com/v2/form';

	/**
	 * @var Monri_WC_Gateway
	 */
	protected $payment;

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
		$this->payment = $payment;

		add_action( 'woocommerce_receipt_' . $this->payment->id, [ $this, 'process_redirect' ] );
		add_action( 'woocommerce_before_thankyou', [ $this, 'process_return' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'process_capture' ], null, 4 );
		add_action( 'woocommerce_order_status_changed', [ $this, 'process_void' ], null, 4 );

		// load installments fee logic if installments enabled
		if ( $this->payment->get_option( 'paying_in_installments' ) ) {
			require_once __DIR__ . '/installments-fee.php';
			( new Monri_WC_Installments_Fee() )->init();
		}

		// add tokenization support
		if ( $this->tokenization_enabled() ) {
			$this->supports[] = 'tokenization';

			require_once __DIR__ . '/payment-token-webpay.php';

			add_filter( 'woocommerce_payment_token_class', function ( $value, $type ) {
				if ( $type === 'Monri_Webpay' ) {
					return Monri_WC_Payment_Token_Webpay::class;
				}

				return $value;
			}, 0, 2 );
		}
	}

	/**
	 * @return bool
	 */
	public function tokenization_enabled() {
		return $this->payment->get_option_bool( 'monri_web_pay_tokenization_enabled' );
	}

	/**
	 * @return void
	 */
	public function payment_fields() {

		$order_total = (float) WC()->cart->get_total( 'edit' );

		// installments key/value array for template
		$installments = array();

		if ( $this->payment->get_option_bool( 'paying_in_installments' ) ) {

			$bottom_limit           = (float) $this->payment->get_option( 'bottom_limit', 0 );
			$bottom_limit_satisfied = ( $bottom_limit < 0.01 ) || ( $order_total >= $bottom_limit );

			if ( $bottom_limit_satisfied ) {

				$selected = (int) WC()->session->get( 'monri_installments' );

				for ( $i = 1; $i <= (int) $this->payment->get_option( 'number_of_allowed_installments', 12 ); $i ++ ) {
					$installments[] = [
						'label'          => ( $i === 1 ) ? __( 'No installments', 'monri' ) : (string) $i,
						'value'          => (string) $i,
						'selected'       => ( $selected === $i ),
						'price_increase' => $this->payment->get_option( "price_increase_$i", 0 )
					];
				}

			}

			wc_get_template( 'installments.php', array(
				'installments' => $installments
			), basename( MONRI_WC_PLUGIN_PATH ), MONRI_WC_PLUGIN_PATH . 'templates/' );

		}

		if ( $this->tokenization_enabled() && is_checkout() && is_user_logged_in() ) {
			$this->payment->tokenization_script();
			$this->payment->saved_payment_methods();
			$this->payment->save_payment_method_checkbox();
		}
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

		$number_of_installments = WC()->session->get( 'monri_installments' );

		if ( isset( $number_of_installments ) ) {
			$order->add_meta_data( 'monri_installments', $number_of_installments );
			$order->save();
		}

		//Since we are redirecting in Monri Webpay the post data of the current request won't persist
		if ( isset( $_POST['wc-monri-new-payment-method'] ) ) {
			WC()->session->set( 'wc-monri-new-payment-method', $_POST['wc-monri-new-payment-method'] );
		}

		if ( isset( $_POST['wc-monri-payment-token'] ) ) {
			WC()->session->set( 'wc-monri-payment-token', $_POST['wc-monri-payment-token'] );
		}

		return [
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true )
		];
	}

	/**
	 * Redirect form on receipt page
	 *
	 * @param int $order_id
	 *
	 * @return void
	 */
	public function process_redirect( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( $this->payment->get_option_bool( 'test_mode' ) ) {
			$order_id = Monri_WC_Utils::get_test_order_id( $order_id );
		}

		$key   = $this->payment->get_option( 'monri_merchant_key' );
		$token = $this->payment->get_option( 'monri_authenticity_token' );

		//Convert order amount to number without decimals
		$order_total = $order->get_total() * 100;

		$currency = $order->get_currency();
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}

		//Generate digest key
		$digest = hash( 'sha512', $key . $order_id . $order_total . $currency );

		//Combine first and last name in one string
		$full_name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
		$supported_payment_methods = 'card';


		//Array of order information
		$args = array(
			'ch_full_name' => wc_trim_string( $full_name, 30, '' ),
			'ch_address'   => wc_trim_string( $order->get_billing_address_1(), 100, '' ),
			'ch_city'      => wc_trim_string( $order->get_billing_city(), 30, '' ),
			'ch_zip'       => wc_trim_string( $order->get_billing_postcode(), 9, '' ),
			'ch_country'   => $order->get_billing_country(),
			'ch_phone'     => wc_trim_string( $order->get_billing_phone(), 30, '' ),
			'ch_email'     => wc_trim_string( $order->get_billing_email(), 100, '' ),

			'order_number'    => $order_id,
			'order_info'      => $order_id . '_' . gmdate( 'dmy' ),
			'amount'          => $order_total,
			'currency'        => $currency,
			'original_amount' => $order->get_total(),

			'language'              => $this->payment->get_option( 'form_language' ),
			'transaction_type'      => $this->payment->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase',
			'authenticity_token'    => $token,
			'digest'                => $digest,
			'success_url_override'  => $this->payment->get_return_url( $order ),
			'cancel_url_override'   => $order->get_cancel_order_url(),
			'callback_url_override' => add_query_arg( 'wc-api', 'monri_callback', get_home_url() ),
			'supported_payment_methods' => $supported_payment_methods
		);

		if ( $this->tokenization_enabled() && is_checkout() && is_user_logged_in() ) {

			$use_token = null;
			if ( !empty( WC()->session->get( 'wc-monri-payment-token' ) ) &&
			     ! in_array( WC()->session->get( 'wc-monri-payment-token' ), [ 'not-selected', 'new', '' ], true )
			) {
				$token_id = sanitize_text_field( WC()->session->get( 'wc-monri-payment-token' ) );
				WC()->session->set( 'wc-monri-payment-token', null );

				$tokens   = $this->payment->get_tokens();

				if ( ! isset( $tokens[ $token_id ] ) ) {
					throw new Exception( esc_html( __( 'Token does not exist.', 'monri' ) ) );
				}

				/** @var Monri_WC_Payment_Token_Webpay $use_token */
				$use_token = $tokens[ $token_id ];
			}

			$new_token = !empty( WC()->session->get( 'wc-monri-new-payment-method') ) &&
			             in_array( WC()->session->get( 'wc-monri-new-payment-method') , [ 'true', '1', 1 ], true );

			// paying with tokenized card
			if ( $use_token ) {

				$args['supported_payment_methods'] = $use_token->get_token();

			} else {
				// tokenize/save new card
				if ( $new_token ) {
					$args['tokenize_pan'] = 1;
					WC()->session->set( 'wc-monri-new-payment-method', false);
				}

			}

		}


		$order->add_meta_data( 'monri_transaction_type', $args['transaction_type'] );
		$order->save();
		$number_of_installments = $order->get_meta( 'monri_installments' ) ? (int) $order->get_meta( 'monri_installments' ) : 1;
		$number_of_installments = min( max( $number_of_installments, 1 ), 36 );
		if ( $number_of_installments > 1 ) {
			$args['number_of_installments'] = $number_of_installments;
		}

		$args = apply_filters( 'monri_webpay_form_request', $args );

		Monri_WC_Logger::log( "Request data: " . print_r( $args, true ), __METHOD__ );

		wc_get_template( 'redirect-form.php', [
			'action'  => $this->payment->get_option_bool( 'test_mode' ) ? self::ENDPOINT_TEST : self::ENDPOINT,
			'options' => $args,
			'order'   => $order
		], basename( MONRI_WC_PLUGIN_PATH ), MONRI_WC_PLUGIN_PATH . 'templates/' );
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

	/**
	 * Monri returns on thankyou page
	 *
	 * @param int $order_id
	 *
	 * @return void
	 */
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

			// save token if needed
			if ( $this->tokenization_enabled() && $order->get_user_id() ) {
				$token_data = [];
				foreach ( [ 'cc_type', 'masked_pan', 'pan_token' ] as $key ) {
					if ( isset( $_GET[ $key ] ) ) {
						$token_data[ $key ] = sanitize_text_field( $_GET[ $key ] );
					}
				}
				$this->save_user_token( $order->get_user_id(), $token_data );
			}

		} else {
			$order->update_status( 'failed', "Response not authorized - response code is $response_code." );
			//$order->add_order_note( __( 'Transaction Declined: ', 'monri' ) . sanitize_text_field( $_GET['Error'] ) );
		}

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
		if ($order->get_payment_method() !== $this->payment->id ) {
			return false;
		}

		$monri_order_id = $order->get_meta( 'monri_order_number' );
		$currency       = $order->get_currency();

		if ( empty( $monri_order_id ) ) {
			$order->add_order_note( sprintf( __( 'There was an error submitting the refund to Monri.', 'monri' ) ) );

			return false;
		}

		$response = Monri_WC_Api::instance()->refund( $monri_order_id, $amount * 100, $currency );

		$formatted_response = json_decode(json_encode($response), true);
		if ( is_wp_error( $response ) || !(isset( $formatted_response['response-code']) && $formatted_response['response-code'] === '0000')) {
			Monri_WC_Logger::log( $formatted_response, __METHOD__ );
			$order->add_order_note(
				sprintf( __( 'There was an error submitting the refund to Monri.', 'monri' ) )
			);

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
		if ($order->get_payment_method() !== $this->payment->id ) {
			return false;
		}

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
		if ($order->get_payment_method() !== $this->payment->id ) {
			return false;
		}

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

	/**
	 * @param int $user_id
	 * @param $data
	 *
	 * @return void
	 */
	public function save_user_token( $user_id, $data ) {

		if ( ! isset( $data['pan_token'], $data['cc_type'], $data['masked_pan'] ) ) {
			return null;
		}
		if ($this->check_if_token_already_exists($user_id, $data['masked_pan'])) {
			return null;
		}

		$wc_token = new Monri_WC_Payment_Token_Webpay();

		$wc_token->set_gateway_id( $this->payment->id );
		$wc_token->set_token( $data['pan_token'] );
		$wc_token->set_user_id( $user_id );

		$masked_pan_array = explode("-", $data['masked_pan']);
		$wc_token->set_last4( end( $masked_pan_array ) );
		$ccType = $data['cc_type'] ?? null;
		$wc_token->set_card_type( $ccType );

		$wc_token->save();
	}

	/**
	 * Check if payment token already exists to avoid making duplicates
	 * @param $user_id
	 * @param $masked_pan
	 *
	 * @return bool
	 */
	private function check_if_token_already_exists($user_id, $masked_pan) {
		$masked_pan_array = explode("-", $masked_pan);
		$last4 = end( $masked_pan_array );

		$user_tokens = WC_Payment_Tokens::get_customer_tokens( $user_id );
		foreach ($user_tokens as $user_token) {
			if ($user_token->get_last4() === $last4) {
				return true;
			}
		}
		return false;
	}
}
