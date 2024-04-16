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
	private $payment;

	/**
	 * @param Monri_WC_Gateway $payment
	 *
	 * @return void
	 */
	public function init( $payment ) {
		$this->payment = $payment;

		add_action( 'woocommerce_receipt_' . $this->payment->id, [ $this, 'process_redirect' ] );
		add_action( 'woocommerce_before_thankyou', [ $this, 'process_return' ] );

		// load installments fee logic if installments enabled
		if ( $this->payment->get_option( 'paying_in_installments' ) ) {
			require_once __DIR__ . '/installments-fee.php';
			( new Monri_WC_Installments_Fee() )->init();
		}
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
						'label'          => ( $i === 1 ) ? __('No installments', 'monri') : (string) $i,
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
            $order->add_meta_data('monri_installments', $number_of_installments );
            $order->save();
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
			'callback_url_override' => add_query_arg( 'wc-api', 'monri_callback', get_home_url() )
		);

		$number_of_installments = $order->get_meta('monri_installments' ) ? (int) $order->get_meta('monri_installments' ) : 1;
		$number_of_installments = min( max( $number_of_installments, 1 ), 24 );
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

		/**
		 * @note: GET/SERVER params are not sanitized here because values are used for hash compare
		 */
		$digest = $_GET['digest'];

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

		Monri_WC_Logger::log( "Response data: " . print_r( $_GET, true ), __METHOD__ );

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

        if ( ! in_array( $order->get_status(), [ 'pending', 'failed' ] ) ) {
			return;
		}

		$response_code = ! empty( $_GET['response_code'] ) ? sanitize_text_field( $_GET['response_code'] ) : '';

		if ( $response_code === '0000' ) {
			$order->payment_complete();

			$approval_code = ! empty( $_GET['approval_code'] ) ? sanitize_text_field( $_GET['approval_code'] ) : '';
			if ($approval_code) {
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

		} else {
			$order->update_status( 'failed', "Response not authorized - response code is $response_code." );
			//$order->add_order_note( __( 'Transaction Declined: ', 'monri' ) . sanitize_text_field( $_GET['Error'] ) );
		}

	}
}
