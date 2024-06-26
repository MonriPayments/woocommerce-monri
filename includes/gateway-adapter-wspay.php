<?php

class Monri_WC_Gateway_Adapter_Wspay {

	/**
	 * Adapter ID
	 */
	public const ADAPTER_ID = 'wspay';

	public const ENDPOINT_TEST = 'https://formtest.wspay.biz';
	public const ENDPOINT = 'https://form.wspay.biz';

	/**
	 * @var Monri_WC_Gateway
	 */
	private $payment;

	/**
	 * @var string
	 */
	private $shop_id;

	/**
	 * @var string
	 */
	private $secret;

	/**
	 * @var string[]
	 */
	private $transaction_info_map = [
		'WsPayOrderId' => 'Transaction ID',
		'ApprovalCode' => 'Approval code',
		'PaymentType'  => 'Credit cart type',
		'PaymentPlan'  => 'Payment plan',
		'DateTime'     => 'Date/Time',
        'STAN'         => 'STAN',
        'Amount'       => 'Amount',
	];

	/**
	 * @param Monri_WC_Gateway $payment
	 *
	 * @return void
	 */
	public function init( $payment ) {
		$this->payment = $payment;

		$this->shop_id = $this->payment->get_option(
			'monri_ws_pay_form_shop_id'
		);
		$this->secret  = $this->payment->get_option(
			'monri_ws_pay_form_secret'
		);

		// add tokenization support
		if ( $this->tokenization_enabled() ) {
			$this->payment->supports[] = 'tokenization';

			require_once __DIR__ . '/payment-token-wspay.php';

			add_filter( 'woocommerce_payment_token_class', function ( $value, $type ) {
				if ( $type === 'Monri_Wspay' ) {
					return Monri_WC_Payment_Token_Wspay::class;
				}

				return $value;
			}, 0, 2 );
		}

		add_action( 'woocommerce_before_thankyou', [ $this, 'process_return' ] );
		add_action( 'woocommerce_thankyou_monri', [ $this, 'thankyou_page' ] );
        add_action( 'woocommerce_order_status_changed', [ $this, 'process_capture' ], null, 3 );
        add_action( 'woocommerce_order_status_changed', [ $this, 'process_void' ], null, 3 );
	}

	/**
	 * @return void
	 */
	public function use_tokenization_credentials() {
		$this->shop_id = $this->payment->get_option(
			$this->tokenization_enabled() ?
				'monri_ws_pay_form_tokenization_shop_id' :
				'monri_ws_pay_form_shop_id'
		);
		$this->secret  = $this->payment->get_option(
			$this->tokenization_enabled() ?
				'monri_ws_pay_form_tokenization_secret' :
				'monri_ws_pay_form_secret'
		);
	}

	/**
	 * @return bool
	 */
	public function tokenization_enabled() {
		return $this->payment->get_option_bool( 'monri_ws_pay_form_tokenization_enabled' );
	}

	/**
	 * @return void
	 */
	public function payment_fields() {

		if ( $this->tokenization_enabled() && is_checkout() && is_user_logged_in() ) {
			$this->payment->tokenization_script();
			$this->payment->saved_payment_methods();
			$this->payment->save_payment_method_checkbox();
		}
	}

	/**
	 * @param int $order_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		$order_id = (string) $order->get_id();

		if ( $this->payment->get_option_bool( 'test_mode' ) ) {
			$order_id = Monri_WC_Utils::get_test_order_id( $order_id );
		}

		$req = [];

		if ( $this->tokenization_enabled() && is_checkout() && is_user_logged_in() ) {

			$use_token = null;
			if ( isset( $_POST['wc-monri-payment-token'] ) &&
			     ! in_array( $_POST['wc-monri-payment-token'], [ 'not-selected', 'new', '' ], true )
			) {
				$token_id = sanitize_text_field( $_POST['wc-monri-payment-token'] );
				$tokens   = $this->payment->get_tokens();

				if ( ! isset( $tokens[ $token_id ] ) ) {
					throw new Exception( esc_html( __( 'Token does not exist.', 'monri' ) ) );
				}

				/** @var Monri_WC_Payment_Token_Wspay $use_token */
				$use_token = $tokens[ $token_id ];
			}

			$new_token = isset( $_POST['wc-monri-new-payment-method'] ) &&
			             in_array( $_POST['wc-monri-new-payment-method'], [ 'true', '1', 1 ], true );

			// paying with tokenized card
			if ( $use_token ) {

				$req['Token']       = $use_token->get_token();
				$req['TokenNumber'] = $use_token->get_last4();

				$order->update_meta_data( '_monri_order_token_used', 1 );
				$order->save_meta_data();

				// use different shop_id/secret for tokenization
				$this->use_tokenization_credentials();

			} else {

				// tokenize/save new card
				if ( $new_token ) {
					$req['IsTokenRequest'] = '1';
				}

				if ( $order->get_meta( '_monri_order_token_used' ) ) {
					$order->delete_meta_data( '_monri_order_token_used' );
					$order->save_meta_data();
				}
			}

		}

		$req['shopID']         = $this->shop_id;
		$req['shoppingCartID'] = $order_id;

		$amount             = number_format( $order->get_total(), 2, ',', '' );
		$req['totalAmount'] = $amount;

		$req['signature'] = $this->sign_transaction( $order_id, $amount );

		$req['returnURL']      = $order->get_checkout_order_received_url();
		$cancel_url            = str_replace( '&amp;', '&', $order->get_cancel_order_url() );
		$req['returnErrorURL'] = $cancel_url;
		$req['cancelURL']      = $cancel_url;

		$req['version']           = '2.0';
		$req['customerFirstName'] = $order->get_billing_first_name();
		$req['customerLastName']  = $order->get_billing_last_name();
		$req['customerAddress']   = $order->get_billing_address_1();
		$req['customerCity']      = $order->get_billing_city();
		$req['customerZIP']       = $order->get_billing_postcode();
		$req['customerCountry']   = $order->get_billing_country();
		$req['customerPhone']     = $order->get_billing_phone();
		$req['customerEmail']     = $order->get_billing_email();

		$req = apply_filters( 'monri_wspay_request', $req );
        $order->add_meta_data('transaction_type', $this->payment->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase');
        $order->save();
		Monri_WC_Logger::log( "Request data: " . print_r( $req, true ), __METHOD__ );
		$response = $this->api( '/api/create-transaction', $req );
		Monri_WC_Logger::log( $response, __METHOD__ );

		if ( isset( $response['PaymentFormUrl'] ) ) {
			return [
				'result'   => 'success',
				'redirect' => $response['PaymentFormUrl']
			];
		}

		throw new Exception( esc_html( __( 'Gateway currently not available.', 'monri' ) ) );
	}

	/**
	 * @param int $order_id
	 *
	 * @return void
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || $order->get_payment_method() !== $this->payment->id ) {
			return;
		}

		if ( ! $this->payment->get_option_bool( 'order_show_transaction_info' ) ) {
			return;
		}

		wc_get_template( 'transaction-info.php', [
			'order'            => $order,
			'transaction_info' => $this->get_transaction_info_formatted( $order )
		], basename( MONRI_WC_PLUGIN_PATH ), MONRI_WC_PLUGIN_PATH . 'templates/' );
	}

	/**
	 * Monri returns on thankyou page
	 *
	 * @param int $order_id
	 *
	 * @return void
	 */
	public function process_return( $order_id ) {

		if ( ! isset( $_GET['ShoppingCartID'] ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_payment_method() !== $this->payment->id ) {
			return;
		}

		Monri_WC_Logger::log( "Response data: " . sanitize_textarea_field( print_r( $_GET, true ) ), __METHOD__ );

		$requested_order_id = sanitize_text_field( $_GET['ShoppingCartID'] );
		if ( $this->payment->get_option_bool( 'test_mode' ) ) {
			$requested_order_id = Monri_WC_Utils::resolve_real_order_id( $order_id );
		}

		if ( $order_id != $requested_order_id ) {
			return;
		}

		$is_tokenization = $order->get_meta( '_monri_order_token_used', true );
		if ( $is_tokenization ) {
			$this->use_tokenization_credentials();
		}

		if ( ! $this->validate_return() ) {
			return;
		}

		if ( ! in_array( $order->get_status(), [ 'pending', 'failed' ], true ) ) {
			return;
		}

		$success       = ( isset( $_GET['Success'] ) && $_GET['Success'] === '1' ) ? '1' : '0';
		$approval_code = ! empty( $_GET['ApprovalCode'] ) ? sanitize_text_field( $_GET['ApprovalCode'] ) : '';

		$trx_authorized = ( $success === '1' ) && ! empty( $approval_code );

		if ( $trx_authorized ) {

            $transaction_type = $order->get_meta('transaction_type');
            // save transaction info
            $transaction_data = [];
            foreach ( array_keys( $this->transaction_info_map ) as $key ) {
                if ( isset( $_GET[ $key ] ) ) {
                    $transaction_data[ $key ] = sanitize_text_field( $_GET[ $key ] );
                }
            }
            $order->update_meta_data( '_monri_transaction_info', $transaction_data );
            $order->save_meta_data();
            if ( $transaction_type === 'purchase' || $is_tokenization) {
                $order->update_status( 'processing' );
            }
            else {
                $order->update_status('on-hold', __('Order awaiting payment', 'monri'));
            }


			$order->add_order_note( __( 'Monri payment successful<br/>Approval code: ', 'monri' ) . $approval_code );

			WC()->cart->empty_cart();

			// save token if needed
			if ( $this->tokenization_enabled() && $order->get_user_id() ) {
				$token_data = [];
				foreach ( [ 'Token', 'TokenNumber', 'TokenExp', 'PaymentType', 'CreditCardName' ] as $key ) {
					if ( isset( $_GET[ $key ] ) ) {
						$token_data[ $key ] = sanitize_text_field( $_GET[ $key ] );
					}
				}
				$this->save_user_token( $order->get_user_id(), $token_data );
			}
		} else {
			$order->update_status( 'failed' );
		}

	}

	/**
	 * @param string $shoppingCartId
	 * @param string $totalAmount
	 *
	 * @return string
	 */
	private function sign_transaction( $shoppingCartId, $totalAmount ) {
		$shopId    = $this->shop_id;
		$secretKey = $this->secret;
		$amount    = preg_replace( '~\D~', '', $totalAmount );

		return hash( 'sha512', $shopId . $secretKey . $shoppingCartId . $secretKey . $amount . $secretKey );
	}

	/**
	 * Validates that return came from Monri
	 *
	 * @return bool
	 */
	private function validate_return() {

		if ( ! isset( $_GET['ShoppingCartID'], $_GET['Signature'] ) ) {
			return false;
		}

		$order_id      = sanitize_text_field( $_GET['ShoppingCartID'] );
		$digest        = Monri_WC_Utils::sanitize_hash( $_GET['Signature'] );
		$success       = ( isset( $_GET['Success'] ) && $_GET['Success'] === '1' ) ? '1' : '0';
		$approval_code = isset( $_GET['ApprovalCode'] ) ? sanitize_text_field( $_GET['ApprovalCode'] ) : '';

		$shop_id    = $this->shop_id;
		$secret_key = $this->secret;

		$digest_parts = array(
			$shop_id,
			$secret_key,
			$order_id,
			$secret_key,
			$success,
			$secret_key,
			$approval_code,
			$secret_key,
		);
		$check_digest = hash( 'sha512', implode( '', $digest_parts ) );

		return hash_equals( $check_digest, $digest );
	}

	/**
	 * Send POST request to $url with $params as a field
	 *
	 * @param string $path
	 * @param array $params
	 *
	 * @return array
	 */
	private function api( $path, $params ) {

		$url = $this->payment->get_option_bool( 'test_mode' ) ? self::ENDPOINT_TEST : self::ENDPOINT;
		$url .= $path;

		$result = wp_remote_post( $url, [
				'body'      => wp_json_encode( $params ),
				'headers'   => [
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json'
				],
				'method'    => 'POST',
				'timeout'   => 15,
				'sslverify' => false
			]
		);

		if ( is_wp_error( $result ) || ! isset( $result['body'] ) ) {
			return [];
		}

		return json_decode( $result['body'], true );
	}

	/**
	 * @param int $user_id
	 * @param $data
	 *
	 * @return void
	 */
	private function save_user_token( $user_id, $data ) {

		if ( ! isset( $data['Token'], $data['TokenNumber'], $data['TokenExp'] ) ) {
			return null;
		}

		$wc_token = new Monri_WC_Payment_Token_Wspay();

		$wc_token->set_gateway_id( $this->payment->id );
		$wc_token->set_token( $data['Token'] );
		$wc_token->set_user_id( $user_id );

		$wc_token->set_last4( $data['TokenNumber'] );
		$ccType = $data['PaymentType'] ?? ( $data['CreditCardName'] ?? '' );
		$wc_token->set_card_type( $ccType );
		$wc_token->set_expiry_year( substr( $data['TokenExp'], 0, 2 ) );
		$wc_token->set_expiry_month( substr( $data['TokenExp'], 2, 2 ) );

		$wc_token->save();
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	private function get_transaction_info_formatted( $order ) {
		$transaction_info = $order->get_meta( '_monri_transaction_info' );
		if ( ! $transaction_info || ! is_array( $transaction_info ) ) {
			return [];
		}

		$formatted = [];
		foreach ( $this->transaction_info_map as $key => $value ) {
			if ( ! empty( $transaction_info[ $key ] ) ) {
				$formatted[ $key ] = [
					'label' => $value,
					'value' => $transaction_info[ $key ]
				];
			}
		}

		return $formatted;
	}

    /**
     * Process a refund
     *
     * @param int $order_id
     * @param float $amount
     *
     * @return bool
     */
    public function process_refund( $order_id, $amount = null) {

        $order = wc_get_order( $order_id );
        $transaction_info = $order->get_meta( '_monri_transaction_info' );
        $is_tokenization = $order->get_meta( '_monri_order_token_used' );
        $wspay_order_id = isset($transaction_info['WsPayOrderId']) ? sanitize_text_field($transaction_info[ 'WsPayOrderId' ]) : null;
        $STAN = isset($transaction_info['STAN']) ? sanitize_text_field($transaction_info[ 'STAN' ]) : null;
        $approval_code = isset($transaction_info['ApprovalCode']) ? sanitize_text_field($transaction_info[ 'ApprovalCode' ]) : null;

        if ( empty( $wspay_order_id ) ) {
            $order->add_order_note( sprintf( __( 'There was an error submitting the refund to Monri.', 'monri' ) ) );
            return false;
        }
        $response = Monri_WSPay_WC_Api::instance()->refund($STAN, $approval_code, $wspay_order_id, $amount * 100, $is_tokenization);

        if ( is_wp_error($response) || ( isset($response['ActionSuccess']) && $response['ActionSuccess'] ==! '1') ) {
            $order->add_order_note( sprintf( __( 'There was an error submitting the refund to Monri.', 'monri' ) ) );
            $order->save();
            Monri_WC_Logger::log( $response, __METHOD__ );
            return false;
        }
        if ( $order->get_total() - $order->get_total_refunded() < 0.01 ) {
            $order->update_meta_data('should_close_parent_transaction', '1');
        }
        $order->add_order_note(sprintf(
            __( 'Refund of %s successfully sent to Monri.', 'monri' ),
            wc_price( $amount, array( 'currency' => $order->get_currency() ) )
        ) );
        $order->save();
        return true;
    }

    /**
     * Capture order on Monri side
     *
     * @param int $order_id
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function process_capture( $order_id, $from, $to ) {

        if ( ! ( in_array( $from, [ 'pending', 'on-hold' ] ) && in_array( $to, wc_get_is_paid_statuses() ) ) ) {
            return false;
        }
        $order = wc_get_order( $order_id );
        $transaction_info = $order->get_meta( '_monri_transaction_info' );
        $is_tokenization = $order->get_meta( '_monri_order_token_used' );

        if (empty($transaction_info) || $is_tokenization) {
            return false;
        }
        $wspay_order_id = isset($transaction_info['WsPayOrderId']) ? sanitize_text_field($transaction_info[ 'WsPayOrderId' ]) : null;
        $STAN = isset($transaction_info['STAN']) ? sanitize_text_field($transaction_info[ 'STAN' ]) : null;
        $approval_code = isset($transaction_info['ApprovalCode']) ? sanitize_text_field($transaction_info[ 'ApprovalCode' ]) : null;
        $amount = $order->get_total() - $order->get_total_refunded();

        if ($amount < 0.01) {
            return false;
        }

        $response = Monri_WSPay_WC_Api::instance()->capture($STAN, $approval_code, $wspay_order_id, $amount * 100, $is_tokenization);

        if ( is_wp_error($response) || ( isset($response['ActionSuccess']) && $response['ActionSuccess'] ==! '1') ) {
            Monri_WC_Logger::log( $response, __METHOD__ );
            $order->add_order_note(
                sprintf( __( 'There was an error submitting the capture to Monri.', 'monri' ) )
            );
            $order->save();
            return false;
        }

        $order->payment_complete( $wspay_order_id );
        $order->add_order_note(sprintf(
            __( 'Capture of %s successfully sent to Monri.', 'monri' ),
            wc_price( $amount, array( 'currency' => $order->get_currency() ) )
        ) );

        return true;
    }

    /**
     * Void order on Monri WSPay side
     *
     * @param $order_id
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function process_void( $order_id, $from, $to ) {

        if ( ! ( in_array( $from, [ 'pending', 'on-hold' ] ) && in_array( $to, [ 'cancelled', 'failed' ] ) ) ) {
            return false;
        }

        $order = wc_get_order( $order_id );
        $transaction_info = $order->get_meta( '_monri_transaction_info' ) ;
        $is_tokenization = $order->get_meta( '_monri_order_token_used' );
        if (empty($transaction_info)) {
            return false;
        }
        $wspay_order_id = isset($transaction_info['WsPayOrderId']) ? sanitize_text_field($transaction_info[ 'WsPayOrderId' ]) : null;
        $STAN = isset($transaction_info['STAN']) ? sanitize_text_field($transaction_info[ 'STAN' ]) : null;
        $approval_code = isset($transaction_info['ApprovalCode']) ? sanitize_text_field($transaction_info[ 'ApprovalCode' ]) : null;
        $amount = $order->get_total() - $order->get_total_refunded();
        if ($amount < 0.01) {
            return false;
        }
        $response = Monri_WSPay_WC_Api::instance()->void($STAN, $approval_code, $wspay_order_id, $amount * 100, $is_tokenization);

        if ( is_wp_error($response) || ( isset($response['ActionSuccess']) && $response['ActionSuccess'] ==! '1') ) {
            Monri_WC_Logger::log( $response, __METHOD__ );
            $order->add_order_note(
                sprintf( __( 'There was an error submitting the void to Monri.', 'monri' ) )
            );
            return false;
        }

        $order->add_order_note(sprintf(
            __( 'Void of %s successfully sent to Monri.', 'monri' ),
            wc_price( $amount, array( 'currency' => $order->get_currency() ) )
        ) );
        return true;
    }

    /**
     * Can the order be refunded
     *
     * @param  WC_Order $order
     * @return bool
     */
    public function can_refund_order( $order ) {
        return $order && in_array( $order->get_status(), wc_get_is_paid_statuses() ) &&
            !$order->get_meta( 'should_close_parent_transaction' );
    }
}
