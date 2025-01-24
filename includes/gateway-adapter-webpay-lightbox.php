<?php

require_once __DIR__ . '/gateway-adapter-webpay-form.php';

class Monri_WC_Gateway_Adapter_Webpay_Lightbox extends Monri_WC_Gateway_Adapter_Webpay_Form {

	/**
	 * Adapter ID
	 */
	public const ADAPTER_ID = 'webpay_lightbox';

	public const ENDPOINT_TEST = 'https://ipgtest.monri.com/dist/lightbox.js';
	public const ENDPOINT = 'https://ipg.monri.com/dist/lightbox.js';


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
		parent::init( $payment );

		// load iframe resizer on receipt page
		add_action(
			'template_redirect',
			function () {
				if ( is_checkout_pay_page() ) {
					wp_enqueue_script(
						'monri-iframe-resizer',
						MONRI_WC_PLUGIN_URL . 'assets/js/iframe-resizer.parent.js',
						[],
						MONRI_WC_VERSION,
						false
					);
				}
			}
		);

		add_action( 'woocommerce_receipt_' . $this->payment->id, [ $this, 'process_iframe' ] );
	}


	/**
	 * Iframe on receipt page
	 *
	 * @param int $order_id
	 *
	 * @return void
	 */
	public function process_iframe( $order_id ) {

		if ( $this->payment->get_option( 'monri_web_pay_integration_type' ) !== 'lightbox') {
			return;
		}
		$order = wc_get_order( $order_id );

		$order_id = (string) $order->get_id();

		if ( $this->payment->get_option_bool( 'test_mode' ) ) {
			$order_id = Monri_WC_Utils::get_test_order_id( $order_id );
			//$order_id .= time();
		}

		//Convert order amount to number without decimals
		$order_total = $order->get_total() * 100;

		$currency = $order->get_currency();
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}

		//Combine first and last name in one string
		$full_name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
		//Generate digest key
		$key   = $this->payment->get_option( 'monri_merchant_key' );
		$token = $this->payment->get_option( 'monri_authenticity_token' );
		$digest = hash( 'sha512', $key . $order_id . $order_total . $currency );
		$req = [];

		$req['data-authenticity-token']         = $token;
		$req['data-amount'] = $order_total;
		$req['data-currency'] = $currency;
		$req['data-order-number'] = $order_id;
		$req['data-order-info'] = 'Monri Lightbox';
		$req['data-digest'] = $digest;
		$req['data-transaction-type'] = $this->payment->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase';
		$req['data-ch-full-name'] = $full_name;
		$req['data-ch-zip'] = $order->get_billing_postcode();
		$req['data-ch-phone'] = $order->get_billing_phone();
		$req['data-ch-email'] = $order->get_billing_email();
		$req['data-ch-address'] = $order->get_billing_address_1();
		$req['data-ch-city'] = $order->get_billing_city();
		$req['data-ch-country'] = $order->get_billing_country();
		$req['data-language'] = $this->payment->get_option( 'form_language' );
		$req['data-success-url-override']  = $this->payment->get_return_url( $order );
		$req['data-cancel-url-override']  = $order->get_cancel_order_url();

		$req = apply_filters( 'monri_lightbox_iframe_request', $req );

		$order->add_meta_data( 'monri_transaction_type', $req['data-transaction-type'] );
		$order->save_meta_data();

		Monri_WC_Logger::log( 'Request data: ' . print_r( $req, true ), __METHOD__ );

		wc_get_template( 'lightbox-iframe-form.php', [
			'src'  => $this->payment->get_option_bool( 'test_mode' ) ? self::ENDPOINT_TEST : self::ENDPOINT,
			'action' => $this->payment->get_return_url( $order ),
			'options' => $req,
			'order'   => $order
		], basename( MONRI_WC_PLUGIN_PATH ), MONRI_WC_PLUGIN_PATH . 'templates/' );
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

		$order_pay_url = $order->get_checkout_payment_url( true );

		return [
			'result'   => 'success',
			'redirect' => $order_pay_url
		];
	}
}