<?php
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

class Monri_WC_Gateway_Webpay_Components_Keks extends WC_Payment_Gateway {
	public const AUTHORIZATION_ENDPOINT_TEST = 'https://ipgtest.monri.com/v2/payment/new';
	public const AUTHORIZATION_ENDPOINT      = 'https://ipg.monri.com/v2/payment/new';

	/**
	 * Supported features
	 *
	 * @var string[]
	 */
	public $supports = array( 'products', 'refunds' );

	/**
	 * Gateway ID
	 *
	 * @var string
	 */
	public $id = 'monri-components-keks';

	/**
	 * Components Keks constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init_settings();
		$this->has_fields  = false;
		$this->title       = __( 'Monri Keks', 'monri' );
		$this->description = __( 'Pay with Monri Keks', 'monri' );

		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'process_components_keks' ) );
	}


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
		// QR code can be rendered only once per order?
//		if ( $order->get_meta( 'keks_qr_code_rendered' ) === 'yes' ) {
//			$order->set_status( 'cancelled', 'Order has been cancelled. Please go to checkout and try again.' );
//		}
//		$order->update_meta_data( 'keks_qr_code_rendered', 'yes' );
//		$order->save();
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Passes config data to template file
	 *
	 * @param $order_id
	 *
	 * @return void
	 */
	public function process_components_keks( $order_id ) {

		wc_get_template(
			'components-keks.php',
			array(
				'config' => array(
					'is_test'            => $this->get_option_bool( 'test_mode' ) ? 'test' : 'prod',
					'client_secret'      => $this->request_authorize( wc_get_order( $order_id ) ),
					'authenticity_token' => $this->get_option( 'monri_authenticity_token' ),
					'locale'             => $this->get_option( 'form_language' ),
				),
			),
			basename( MONRI_WC_PLUGIN_PATH ),
			MONRI_WC_PLUGIN_PATH . 'templates/'
		);
	}

	/**
	 * Generate client secret for Keks QR code
	 *
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	private function request_authorize( $order ) {
		$order_id = $order->get_id();
		if ( $this->get_option_bool( 'test_mode' ) ) {
			$order_id = Monri_WC_Utils::get_test_order_id( $order_id );

		}

		$url = $this->get_option_bool( 'test_mode' ) ?
			self::AUTHORIZATION_ENDPOINT_TEST :
			self::AUTHORIZATION_ENDPOINT;

		if ( is_admin() ) { // admin page editor.
			$order_total = 10;
		} else {
			$order_total = (float) $order->get_total();
		}

		$amount_in_minor_units = (int) round( $order_total * 100 );

		$currency = get_woocommerce_currency();
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}

		$data = array(
			'amount'           => $amount_in_minor_units,
			'order_number'     => $order_id,
			'currency'         => $currency,
			'transaction_type' => $this->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase',
			'order_info'       => 'woocommerce order',
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

		return json_decode( $body, true )['client_secret'];
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
	 * Temporary solution for admin options until keks pay is completely independat of monri components
	 */
	public function admin_options() {
		echo '<p>' . esc_html__( 'This payment method is managed by the main Monri Payments settings.', 'monri' ) . '</p>';
	}
}
