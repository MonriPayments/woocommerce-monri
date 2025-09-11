<?php
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

class Monri_WC_Gateway_Webpay_Components_Keks extends WC_Payment_Gateway {
	public const AUTHORIZATION_ENDPOINT_TEST = 'https://ipgtest.monri.com/v2/payment/new';
	public const AUTHORIZATION_ENDPOINT = 'https://ipg.monri.com/v2/payment/new';

	/**
	 * @var string[]
	 */
	public $supports = [ 'products', 'refunds' ];

	public $id = 'monri-components-keks';

	/**
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init_settings();
		$this->has_fields = false;
		$this->title       = __( 'Monri Keks', 'monri' );
		$this->description = __( 'Pay with Monri Keks', 'monri' );

		add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'process_components_keks' ] );
	}


	/**
	 * @param int $order_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true )
		);
	}

	/**
	 *
	 * @return string
	 */
	public function process_components_keks( $order_id ) {

		wc_get_template( 'components-keks.php', [
			'config' => [
				'is_test'            => $this->get_option_bool( 'test_mode' ) ? 'test' : 'prod',
				'client_secret'      => $this->request_authorize( wc_get_order( $order_id ) ),
				'authenticity_token' => $this->get_option( 'monri_authenticity_token' ),
				'locale'             => $this->get_option( 'form_language' ),
			],
		], basename( MONRI_WC_PLUGIN_PATH ), MONRI_WC_PLUGIN_PATH . 'templates/' );

	}

	private function request_authorize($order) {
		$amount_in_minor_units = (int) round( $order->get_total() * 100 );
		$session_client_secret = $this->get_session_client_secret( $amount_in_minor_units );
		if ( ! empty( $session_client_secret ) ) {
			return $session_client_secret;
		}

		$order_id = $order->get_id();
		if ( $this->get_option_bool( 'test_mode' ) ) {
			$order_id = Monri_WC_Utils::get_test_order_id( $order_id );;
		}

		$url = $this->get_option_bool( 'test_mode' ) ?
			self::AUTHORIZATION_ENDPOINT_TEST :
			self::AUTHORIZATION_ENDPOINT;

		if ( is_admin() ) { // admin page editor
			$order_total = 10;
		} else {
			$order_total = (float) WC()->cart->get_total( 'edit' );
		}

		$amount_in_minor_units = (int) round( $order_total * 100 );

		$currency = get_woocommerce_currency();
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}

		$data = [
			'amount'           => $amount_in_minor_units,
			'order_number'     => $order_id,
			'currency'         => $currency,
			'transaction_type' => $this->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase',
			'order_info'       => 'woocommerce order'
		];


		$data = wp_json_encode( $data );

		$timestamp = time();
		$digest    = hash( 'sha512',
			$this->get_option( 'monri_merchant_key' ) .
			$timestamp .
			$this->get_option( 'monri_authenticity_token' ) .
			$data
		);

		$authorization = "WP3-v2 {$this->get_option( 'monri_authenticity_token' )} $timestamp $digest";

		Monri_WC_Logger::log( $data, __METHOD__ );

		$response = wp_remote_post( $url, [
				'body'      => $data,
				'headers'   => [
					'Content-Type'   => 'application/json',
					'Content-Length' => strlen( $data ),
					'Authorization'  => $authorization
				],
				'timeout'   => 10,
				'sslverify' => true
			]
		);

		Monri_WC_Logger::log( $response, __METHOD__ );

		if ( is_wp_error( $response ) ) {
			$response = [ 'status' => 'error', 'error' => $response->get_error_message() ];
		}

		$body          = wp_remote_retrieve_body( $response );
		$client_secret = json_decode( $body, true )['client_secret'];
		// save data to session so that we can reuse it on site refresh
		WC()->session->set(
			$amount_in_minor_units . '_client_secret_timestamp',
			$amount_in_minor_units . '_' . $client_secret . '_' . time()
		);

		return $client_secret;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function get_option_bool( $key ) {
		return in_array( $this->get_option( $key ), array( 'yes', '1', true ), true );
	}

	public function init_settings() {
		$this->settings = get_option( 'woocommerce_monri_settings', [] );
	}

	/**
	 * Return client secret from session if it is valid
	 *
	 * @param int $amount_in_minor_units
	 *
	 * @return string
	 */
	public function get_session_client_secret( $amount_in_minor_units ) {
		// @todo: find out exact time
		$allowed_time_seconds           = 900;

		$amount_client_secret_timestamp = WC()->session->get( (string) $amount_in_minor_units . '_client_secret_timestamp' );
		if (empty($amount_client_secret_timestamp)) {
			return null;
		}

		$amount_client_secret_timestamp = explode( '_', $amount_client_secret_timestamp );
		if ( count( $amount_client_secret_timestamp ) !== 3 ) {
			return null;
		}

		[ $amount, $client_secret, $timestamp ] = $amount_client_secret_timestamp;

		if (
			empty($amount) ||
			empty($client_secret) ||
			empty($timestamp) ||
			(time() - $timestamp) > $allowed_time_seconds ||
			(int) $amount !== $amount_in_minor_units
		) {
			return null;
		}

		return $client_secret;

	}

}
