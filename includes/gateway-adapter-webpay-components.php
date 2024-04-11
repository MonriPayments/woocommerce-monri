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

	public function request_authorize( ) {

		$order_total = (float)WC()->cart->get_total( 'edit' );

		/*
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}
		*/

		$data = [
			'amount' => (int)round($order_total * 100),
			'order_number' => wp_generate_uuid4(), //uniqid('woocommerce-', true),
			'currency' => get_woocommerce_currency(),
			'transaction_type' => 'purchase',
			'order_info' => 'woocommerce order',
			//'scenario' => 'charge'
		];

		//$x = wp_generate_uuid4();

		$body_as_string = json_encode($data);

		$base_url = 'https://ipgtest.monri.com'; // parametrize this value

		$ch = curl_init($base_url . '/v2/payment/new');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body_as_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		$timestamp = time();
		$digest = hash('sha512',
			$this->payment->get_option( 'monri_merchant_key' ) .
			$timestamp .
			$this->payment->get_option( 'monri_authenticity_token' ) .
			$body_as_string
		);
		$authorization = "WP3-v2 {$this->payment->get_option( 'monri_authenticity_token' )} $timestamp $digest";

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($body_as_string),
				'Authorization: ' . $authorization
			)
		);

		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			$response = ['status' => 'declined', 'error' => curl_error($ch)];
		} else {
			$response = json_decode($result, true);
		}
		curl_close($ch);

		return $response;
	}

	/**
	 * @return void
	 */
	public function payment_fields() {

		$initialize = $this->request_authorize();

		//if ($initialize['client_secret'])

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
						'label'          => ( $i === 1 ) ? __('No installments', 'monri') : (string) $i,
						'value'          => (string) $i,
						'selected'       => ( $selected === $i ),
						'price_increase' => $this->payment->get_option( "price_increase_$i", 0 )
					];
				}

			}

		}

		wc_get_template( 'components.php', array(
			'config' => array(
				'authenticity_token' => $this->payment->get_option( 'monri_authenticity_token' ),
				'client_secret' => $initialize['client_secret'],
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

		/*
		$monri_token = $_POST['monri-token'] ?? '';

		if ( empty( $monri_token ) ) {
			throw new Exception( esc_html( __( 'Missing Monri token.', 'monri' ) ) );
		}
		*/

		// monri-transaction + validate order_number
		// min that needs to be saved here is _monri_components_order_number -> callback needs to load by that meta

		//Monri_WC_Logger::log( "Request data: " . print_r( $params, true ), __METHOD__ );

		$order  = wc_get_order( $order_id );
		$amount = $order->get_total();

		//Payment has been successful
		/*
		$order->add_order_note( __( 'Monri payment completed.', 'monri' ) );
		$monri_order_amount1 = $transactionResult['amount'] / 100;
		$monri_order_amount2 = number_format( $monri_order_amount1, 2 );
		if ( $monri_order_amount2 != $order->get_total() ) {
			$order->add_order_note( __( 'Monri - Order amount: ', 'monri' ) . $monri_order_amount2, true );
		}
		if ( isset( $params['number_of_installments'] ) && $params['number_of_installments'] > 1 ) {
			$order->add_order_note( __( 'Number of installments: ', 'monri' ) . $params['number_of_installments'] );
		}
		*/

		// Mark order as Paid
		//$order->payment_complete();

		// Empty the cart (Very important step)
		WC()->cart->empty_cart();

		// Redirect to thank you page
		return array(
			'result'   => 'success',
			'redirect' => $this->payment->get_return_url( $order ),
		);


			/*
		throw new Exception(
			isset( $result['errors'] ) && ! empty( $result['errors'] ) ?
				esc_html( implode( '; ', $result['errors'] ) ) :
				esc_html( __( 'Missing Monri token.', 'monri' ) )
		);
			*/
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
