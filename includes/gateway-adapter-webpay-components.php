<?php

require_once __DIR__ . '/api.php';

class Monri_WC_Gateway_Adapter_Webpay_Components {

	/**
	 * Adapter ID
	 */
	public const ADAPTER_ID = 'webpay_components';

	public const TRANSACTION_ENDPOINT_TEST = 'https://ipgtest.monri.com/v2/transaction';
	public const TRANSACTION_ENDPOINT = 'https://ipg.monri.com/v2/transaction';

	public const SCRIPT_ENDPOINT_TEST = 'https://ipgtest.monri.com/dist/components.js';
	public const SCRIPT_ENDPOINT = 'https://ipg.monri.com/dist/components.js';

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
		$this->payment             = $payment;
		$this->payment->has_fields = true;

		// @todo: check if we can use parse_request here in older Woo? Are gateways loaded?
		add_action( 'parse_request', [ $this, 'parse_request' ] );

		// load components.js on frontend checkout
		add_action( 'template_redirect', function () {
			if ( is_checkout() ) {
				$script_url = $this->payment->get_option_bool( 'test_mode' ) ? self::SCRIPT_ENDPOINT_TEST : self::SCRIPT_ENDPOINT;
				wp_enqueue_script( 'monri-components', $script_url, array(), MONRI_WC_VERSION );
			}
		} );

		// load installments fee logic if installments enabled
		if ( $this->payment->get_option( 'paying_in_installments' ) ) {
			require_once __DIR__ . '/installments-fee.php';
			( new Monri_WC_Installments_Fee() )->init();
		}
	}

	/**
	 * @return void
	 */
	// @todo can't we go back to thankyou page right away and regulate there?
	public function parse_request() {

		$uri = wp_parse_url( site_url() . $_SERVER['REQUEST_URI'], PHP_URL_PATH );

		if ( $uri !== '/monri-3ds-payment-result' ) {
			return;
		}

		$payment_token = isset( $_GET['payment_token'] ) ? $_GET['payment_token'] : null;

		if ( ! $payment_token ) {
			return;
		}

		// @todo regulate error below (empty return), redirect to cart/cancel?

		try {
			$arr    = json_decode( $this->base64url_decode( $payment_token ), true );
			$parsed = [
				'authenticity_token' => $arr[0],
				'order_number'       => $arr[1],
				'return_url'         => $arr[2]
			];
		} catch ( Exception $exception ) {
			//error_log("Error while parsing payment token: " . $exception->getMessage());
			return;
		}

		$order_number = $parsed['order_number'];

		/** @var SimpleXmlElement $result */
		$result = Monri_WC_Api::instance()->orders_show( $order_number );

		if ( is_wp_error( $result ) ) {
			return;
		}

		$order = wc_get_order( $order_number );

		if ( isset( $result->status ) && trim( $result->status ) === 'approved' ) {
			// Payment has been successful
			$order->payment_complete();

			// Empty the cart (Very important step)
			WC()->cart->empty_cart();

			//wp_safe_redirect() ??
			wp_redirect( $parsed['return_url'] );

		} else {
			$order->update_status( 'failed' );
			$order->add_order_note( 'Failed' );
			//$order->add_order_note('Thank you for shopping with us. However, the transaction has been declined.');

			wp_redirect( wc_get_cart_url() );
		}

		exit;
	}

	/**
	 * @return void
	 */
	public function payment_fields() {

		$script_url = $this->payment->get_option_bool( 'test_mode' ) ? self::SCRIPT_ENDPOINT_TEST : self::SCRIPT_ENDPOINT;

		wp_enqueue_script( 'monri-components', $script_url, array( 'jquery' ), MONRI_WC_VERSION );
		//wp_enqueue_script('monri-installments', MONRI_WC_PLUGIN_URL . 'assets/js/installments.js', array('jquery'), MONRI_WC_VERSION);

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
						'label'          => (string) $i,
						'value'          => (string) $i,
						'selected'       => ( $selected === $i ),
						'price_increase' => $this->payment->get_option( "price_increase_$i", 0 )
					];
				}

			}

		}

		$radnom_token = wp_generate_uuid4();
		$timestamp    = ( new DateTime() )->format( 'c' );
		$digest       = hash( 'SHA512', $this->payment->get_option( 'monri_merchant_key' ) . $radnom_token . $timestamp );

		wc_get_template( 'components.php', array(
			'config'       => array(
				'authenticity_token' => $this->payment->get_option( 'monri_authenticity_token' ),
				'random_token'       => $radnom_token,
				'digest'             => $digest,
				'timestamp'          => $timestamp,
				'locale'             => $this->payment->get_option( 'form_language' ),
			),
			'installments' => $installments
		), basename( MONRI_WC_PLUGIN_PATH ), MONRI_WC_PLUGIN_PATH . 'templates/' );
	}

	/**
	 * @param int $order_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function process_payment( $order_id ) {

		$monri_token = $_POST['monri-token'] ?? '';

		if ( empty( $monri_token ) ) {
			throw new Exception( esc_html( __( 'Missing Monri token.', 'monri' ) ) );
		}

		$number_of_installments = isset( $_POST['monri-card-installments'] ) ? (int) $_POST['monri-card-installments'] : 1;
		$number_of_installments = min( max( $number_of_installments, 1 ), 24 );

		$order  = wc_get_order( $order_id );
		$amount = $order->get_total();

		//Check transaction type
		$transaction_type = $this->payment->get_option( 'transaction_type' ) ? 'authorize' : 'purchase';

		//Check if paying in installments, if yes set transaction_type to purchase
		if ( $number_of_installments > 1 ) {
			$transaction_type = 'purchase';
		}

		//Convert order amount to number without decimals
		$amount = ceil( $amount * 100 );

		$currency = $order->get_currency();
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}

		//Generate digest key
		$digest = hash( 'sha512', $this->payment->get_option( 'monri_merchant_key' ) . $order->get_id() . $amount . $currency );

		//Array of order information
		$order_number = $order->get_id();

		$params = array(
			'ch_full_name' => $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
			'ch_address'   => $order->get_billing_address_1(),
			'ch_city'      => $order->get_billing_city(),
			'ch_zip'       => $order->get_billing_postcode(),
			'ch_country'   => $order->get_billing_country(),
			'ch_phone'     => $order->get_billing_phone(),
			'ch_email'     => $order->get_billing_email(),

			'order_number' => $order_number,
			'order_info'   => $order_number . '_' . gmdate( 'dmy' ),
			'amount'       => $amount,
			'currency'     => $currency,

			'ip'                    => $_SERVER['REMOTE_ADDR'],
			'language'              => $this->payment->get_option( 'form_language' ),
			'transaction_type'      => $transaction_type,
			'authenticity_token'    => $this->payment->get_option( 'monri_authenticity_token' ),
			'digest'                => $digest,
			'temp_card_id'          => $monri_token,
			'callback_url_override' => add_query_arg( 'wc-api', 'monri_callback', get_home_url() )
		);

		if ( $number_of_installments > 1 ) {
			$params['number_of_installments'] = $number_of_installments;
		}

		Monri_WC_Logger::log( "Request data: " . print_r( $params, true ), __METHOD__ );

		$result = $this->request( $params );

		//check if cc have 3Dsecure validation
		if ( isset( $result['secure_message'] ) ) {
			//this is 3dsecure card
			//show user 3d secure form the
			$result = $result['secure_message'];

			//$order->get_checkout_order_received_url()
			$thank_you_page = $this->payment->get_return_url( $order );

			$payment_token = $this->base64url_encode(
				wp_json_encode( [ $result['authenticity_token'], $order_number, $thank_you_page ] )
			);

			$urlEncode = array(
				'acsUrl'    => $result['acs_url'],
				'pareq'     => $result['pareq'],
				'returnUrl' => site_url() . '/monri-3ds-payment-result?payment_token=' . $payment_token,
				'token'     => $result['authenticity_token']
			);

			// we can use payment page + template here, but it will be refactored anyway
			$redirect = MONRI_WC_PLUGIN_URL . '3dsecure.php?' . http_build_query( $urlEncode );

			return array(
				'result'   => 'success',
				'redirect' => $redirect,
			);

		}

		if ( isset( $result['transaction'] ) && $result['transaction']['status'] === 'approved' ) {

			$transactionResult = $result['transaction'];

			//$order = wc_get_order($transactionResult['order_number']); //@todo: recheck

			//Payment has been successful
			$order->add_order_note( __( 'Monri payment completed.', 'monri' ) );
			$monri_order_amount1 = $transactionResult['amount'] / 100;
			$monri_order_amount2 = number_format( $monri_order_amount1, 2 );
			if ( $monri_order_amount2 != $order->get_total() ) {
				$order->add_order_note( __( 'Monri - Order amount: ', 'monri' ) . $monri_order_amount2, true );
			}
			if ( isset( $params['number_of_installments'] ) && $params['number_of_installments'] > 1 ) {
				$order->add_order_note( __( 'Number of installments: ', 'monri' ) . $params['number_of_installments'] );
			}

			// Mark order as Paid
			$order->payment_complete();

			// Empty the cart (Very important step)
			WC()->cart->empty_cart();

			// Redirect to thank you page
			return array(
				'result'   => 'success',
				'redirect' => $this->payment->get_return_url( $order ),
			);
		}

		throw new Exception(
			isset( $result['errors'] ) && ! empty( $result['errors'] ) ?
				esc_html( implode( '; ', $result['errors'] ) ) :
				esc_html( __( 'Missing Monri token.', 'monri' ) )
		);
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	protected function request( $params ) {
		$url                          = $this->payment->get_option_bool( 'test_mode' ) ? self::TRANSACTION_ENDPOINT_TEST : self::TRANSACTION_ENDPOINT;
		$requestParams['transaction'] = $params;
		$result                       = wp_remote_post( $url, [
				'body'      => wp_json_encode( $requestParams ),
				'headers'   => [
					'Accept'       => 'application/json',
					'Content-Type' => 'application/json'
				],
				'timeout'   => 15,
				'sslverify' => false
			]
		);

		if ( is_array( $result ) ) {
			if ( isset( $result['body'] ) ) {

				$body_response = json_decode( $result['body'], true );

				Monri_WC_Logger::log( "Response body : " . print_r( $body_response, true ), __METHOD__ );
			}

			if ( isset( $result['response'] ) ) {
				Monri_WC_Logger::log( "Response data : " . print_r( $result['response'], true ), __METHOD__ );
			}
		}

		return json_decode( $result['body'], true );
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	private function base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * @param string $data
	 *
	 * @return false|string
	 */
	private function base64url_decode( $data ) {
		return base64_decode( str_pad( strtr( $data, '-_', '+/' ), strlen( $data ) % 4, '=' ) );
	}

}
