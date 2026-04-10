<?php

require_once __DIR__ . '/gateway-adapter-webpay-form.php';

class Monri_WC_Gateway_Adapter_Webpay_Lightbox extends Monri_WC_Gateway_Adapter_Webpay_Form {

	/**
	 * Adapter ID
	 */
	public const ADAPTER_ID = 'webpay_lightbox';

	public const ENDPOINT_TEST = 'https://ipgtest.monri.com/dist/lightbox.js';
	public const ENDPOINT      = 'https://ipg.monri.com/dist/lightbox.js';


	/**
	 * @var string[]
	 */
	public $supports = array( 'products', 'refunds' );


	/**
	 * @param Monri_WC_Gateway $payment
	 *
	 * @return void
	 */
	public function init( $payment ) {
		parent::init( $payment );

		add_action( 'woocommerce_before_thankyou', array( $this, 'process_return' ) );
		add_action( 'woocommerce_order_edit_status', array( $this, 'process_capture' ), 10, 2 );
		add_action( 'woocommerce_order_edit_status', array( $this, 'process_void' ), 10, 2 );
		add_action( 'woocommerce_receipt_' . $this->payment->id, array( $this, 'process_payment' ) );
	}


	/**
	 * Redirect to receipt page
	 *
	 * @param $order_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $this->payment->get_option_bool( 'test_mode' ) ) {
			$order_id = Monri_WC_Utils::get_test_order_id( $order_id );
		}

		$order_total = $order->get_total() * 100;
		$currency    = get_woocommerce_currency();
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}

		// Generate digest key
		$key    = $this->payment->get_option( 'monri_merchant_key' );
		$token  = $this->payment->get_option( 'monri_authenticity_token' );
		$digest = hash( 'sha512', $key . $order_id . $order_total . $currency );

		// Combine first and last name in one string
		$full_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

		$config = array(
			'src'                        => $this->payment->get_option_bool( 'test_mode' ) ? self::ENDPOINT_TEST : self::ENDPOINT,
			'data-authenticity-token'    => $token,
			'data-amount'                => $order_total,
			'data-currency'              => $currency,
			'data-order-number'          => $order_id,
			'data-order-info'            => $order_id . '_' . gmdate( 'dmy' ),
			'data-digest'                => $digest,
			'data-transaction-type'      => $this->payment->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase',
			'data-language'              => $this->payment->get_option( 'form_language' ),
			'data-ch-full-name'          => wc_trim_string( $full_name, 30, '' ),
			'data-ch-address'            => wc_trim_string( $order->get_billing_address_1(), 100, '' ),
			'data-ch-city'               => wc_trim_string( $order->get_billing_city(), 30, '' ),
			'data-ch-zip'                => wc_trim_string( $order->get_billing_postcode(), 9, '' ),
			'data-ch-country'            => $order->get_billing_country(),
			'data-ch-phone'              => wc_trim_string( $order->get_billing_phone(), 30, '' ),
			'data-ch-email'              => wc_trim_string( $order->get_billing_email(), 100, '' ),
			'result'                     => 'success',
			'messages'                   => '',
			'data-ip'                    => $order->get_customer_ip_address(),
			'data-supported-payment-methods' => 'card',
		);

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

				/** @var Monri_WC_Payment_Token_Webpay $use_token */
				$use_token = $tokens[ $token_id ];
			}

			$new_token = isset( $_POST['wc-monri-new-payment-method'] ) &&
			             in_array( $_POST['wc-monri-new-payment-method'], [ 'true', '1', 1 ], true );

			// paying with tokenized card
			if ( $use_token ) {
				$config['data-supported-payment-methods'] = $use_token->get_token();

			} else {
				// tokenize/save new card
				if ( $new_token ) {
					$config['data-tokenize-pan'] = 1;
				}

			}

		}

		$number_of_installments = WC()->session->get( 'monri_installments' );
		if ( $number_of_installments > 1 ) {
			$config['data-number-of-installments'] = $number_of_installments;
		}

		$order->add_meta_data( 'monri_transaction_type', $config['data-transaction-type'] );
		$order->add_meta_data( 'monri_order_number', $order_id );
		$order->save();

		return $config;
	}

	/**
	 * @return bool
	 */
	public function tokenization_enabled() {
		return $this->payment->get_option_bool( 'monri_web_pay_tokenization_enabled' );
	}

	/**
	 * Old checkout
	 *
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

				for ( $i = 1; $i <= (int) $this->payment->get_option( 'number_of_allowed_installments', 12 ); $i++ ) {
					$installments[] = array(
						'label'          => ( $i === 1 ) ? __( 'No installments', 'monri' ) : (string) $i,
						'value'          => (string) $i,
						'selected'       => ( $selected === $i ),
						'price_increase' => $this->payment->get_option( "price_increase_$i", 0 ),
					);
				}
			}

			// Prevents rendering this file multiple times - JS part gets duplicated and executed twice
			if ( isset( $_REQUEST['wc-ajax'] ) && $_REQUEST['wc-ajax'] === 'update_order_review' ) {
				wc_get_template(
					'installments.php',
					array(
						'installments' => $installments,
					),
					basename( MONRI_WC_PLUGIN_PATH ),
					MONRI_WC_PLUGIN_PATH . 'templates/'
				);
			}

		}

		if ( isset( $_REQUEST['wc-ajax'] ) && $_REQUEST['wc-ajax'] === 'update_order_review' ) {
			wc_get_template(
				'lightbox-iframe-form.php',
				array(),
				basename( MONRI_WC_PLUGIN_PATH ),
				MONRI_WC_PLUGIN_PATH . 'templates/'
			);
		}

		if ( $this->tokenization_enabled() && is_checkout() && is_user_logged_in() ) {
			$this->payment->tokenization_script();
			$this->payment->saved_payment_methods();
			$this->payment->save_payment_method_checkbox();
		}
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
		$monri_order_number = $order->get_meta( 'monri_order_number' );
		// Since lightbox success url no longer has digest, we can't verify the parameters.
		// Instead, we get order details from Monri API.
		$response           = Monri_WC_Api::instance()->orders_show( $monri_order_number );
		$formatted_response = json_decode( wp_json_encode( $response ), true );

		if ( ! in_array( $order->get_status(), array( 'pending', 'failed' ), true ) ) {
			return;
		}

		$response_code    = ! empty( $formatted_response['response-code'] ) ? sanitize_text_field( $formatted_response['response-code'] ) : '';
		$transaction_type = $order->get_meta( 'monri_transaction_type' );
		Monri_WC_Logger::log( 'Transaction type: ' . $order->get_meta( 'monri_transaction_type' ), __METHOD__ );
		if ( $response_code === '0000' ) {
			if ( $transaction_type === 'purchase' ) {
				$order->payment_complete();
			} else {
				$order->update_status( 'on-hold', __( 'Order awaiting payment', 'monri' ) );
			}

			$approval_code = ! empty( $formatted_response['approval-code'] ) ? sanitize_text_field( $formatted_response['approval-code'] ) : '';
			if ( $approval_code ) {
				$order->add_order_note( __( 'Monri payment successful<br/>Approval code: ', 'monri' ) . $approval_code );
			}

			$issuer = ! empty( $formatted_response['issuer'] ) ? sanitize_text_field( $formatted_response['issuer'] ) : '';
			if ( $issuer ) {
				$order->add_order_note( 'Issuer: ' . $issuer );
			}

			$number_of_installments = ! empty( $formatted_response['number-of-installments'] ) ? (int) $formatted_response['number-of-installments'] : 0;
			if ( $number_of_installments > 1 ) {
				$order->add_order_note( __( 'Number of installments: ', 'monri' ) . $number_of_installments );
			}

			WC()->cart->empty_cart();
			$order->save();

			// save token if needed
			if ( $this->tokenization_enabled() && $order->get_user_id() ) {
				$token_data = [];
				foreach ( [ 'cc-type', 'masked-pan', 'pan-token' ] as $key ) {
					if ( isset( $formatted_response[ $key ] ) ) {
						$token_data[ $key ] = sanitize_text_field( $formatted_response[ $key ] );
					}
				}
				$this->save_user_token( $order->get_user_id(), $token_data );
			}
		} else {
			$order->update_status( 'failed', "Response not authorized - response code is $response_code." );
			// $order->add_order_note( __( 'Transaction Declined: ', 'monri' ) . sanitize_text_field( $_GET['Error'] ) );
		}
	}

	/**
	 * Capture order on Monri side
	 *
	 * @param $order_id
	 * @param string   $from
	 * @param string   $to
	 *
	 * @return bool
	 */
	public function process_capture( $order_id, $from = '', $to = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		// woocommerce_order_edit_status sends (order_id, new_status). Normalize to (from, to).
		if ( $to === '' ) {
			$to   = (string) $from;
			$from = $order->get_status();
		}

		if ($order->get_payment_method() !== $this->payment->id ) {
			return false;
		}

		if ( ! ( in_array( $from, array( 'pending', 'on-hold' ) ) && in_array( $to, wc_get_is_paid_statuses() ) ) ) {
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

		$response           = Monri_WC_Api::instance()->capture( $monri_order_id, $amount * 100, $currency );
		$formatted_response = json_decode( json_encode( $response ), true );
		if ( is_wp_error( $response ) || ! ( isset( $formatted_response['response-code'] ) && $formatted_response['response-code'] === '0000' ) ) {
			Monri_WC_Logger::log( $formatted_response, __METHOD__ );
			$order->add_order_note(
				sprintf( __( 'There was an error submitting the capture to Monri.', 'monri' ) )
			);

			return false;
		}

		$order->payment_complete( $monri_order_id );
		$order->add_order_note(
			sprintf(
			/* translators: %s: amount which was successfully captured */
				__( 'Capture of %s successfully sent to Monri.', 'monri' ),
				wc_price( $amount, array( 'currency' => $order->get_currency() ) )
			)
		);

		return true;
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
	public function process_void( $order_id, $from = '', $to = '' ) {

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		// woocommerce_order_edit_status sends (order_id, new_status). Normalize to (from, to).
		if ( $to === '' ) {
			$to   = (string) $from;
			$from = $order->get_status();
		}

		if ($order->get_payment_method() !== $this->payment->id ) {
			return false;
		}

		if ( ! ( in_array( $from, array( 'pending', 'on-hold' ) ) && in_array( $to, array( 'cancelled', 'failed' ) ) ) ) {
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

		$response           = Monri_WC_Api::instance()->void( $monri_order_id, $amount * 100, $currency );
		$formatted_response = json_decode( json_encode( $response ), true );
		if ( is_wp_error( $response ) || ! ( isset( $formatted_response['response-code'] ) && $formatted_response['response-code'] === '0000' ) ) {
			Monri_WC_Logger::log( $formatted_response, __METHOD__ );
			$order->add_order_note(
				sprintf( __( 'There was an error submitting the void to Monri.', 'monri' ) )
			);

			return false;
		}

		$order->add_order_note(
			sprintf(
			/* translators: %s: amount which was successfully voided */
				__( 'Void of %s successfully sent to Monri.', 'monri' ),
				wc_price( $amount, array( 'currency' => $order->get_currency() ) )
			)
		);

		return true;
	}
}
