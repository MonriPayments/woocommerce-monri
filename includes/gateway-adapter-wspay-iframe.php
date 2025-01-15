<?php

require_once __DIR__ . '/gateway-adapter-wspay.php';

class Monri_WC_Gateway_Adapter_Wspay_Iframe extends Monri_WC_Gateway_Adapter_Wspay {

	/**
	 * Adapter ID
	 */
	public const ADAPTER_ID = 'wspay_iframe';

	public const FORM_ENDPOINT_TEST = 'https://formtest.wspay.biz/authorization.aspx';
	public const FORM_ENDPOINT      = 'https://form.wspay.biz/authorization.aspx';

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
	 * Redirect to receipt page
	 *
	 * @param $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		$order_pay_url = $order->get_checkout_payment_url( true );

		// we need to pass token params to url to order-pay page
		if ( $this->tokenization_enabled() && is_checkout() && is_user_logged_in() ) {

			$req = [];

			// use token
			if ( isset( $_POST['wc-monri-payment-token'] ) &&
			     ! in_array( $_POST['wc-monri-payment-token'], [ 'not-selected', 'new', '' ], true )
			) {
				$use_token = sanitize_text_field( $_POST['wc-monri-payment-token'] );
				$tokens    = $this->payment->get_tokens();
				if ( ! isset( $tokens[ $use_token ] ) ) {
					throw new Exception( esc_html( __( 'Token does not exist.', 'monri' ) ) );
				}

				$req['use_token'] = $use_token;

			// new token (save card)
			} else if ( isset( $_POST['wc-monri-new-payment-method'] ) &&
			            in_array( $_POST['wc-monri-new-payment-method'], [ 'true', '1', 1 ], true )
			) {
				$req['use_token'] = -1;
			}

			// add params to query
			if ( $req ) {
				$order_pay_url = add_query_arg( $req, $order_pay_url );
			}
		}

		return [
			'result'   => 'success',
			'redirect' => $order_pay_url
		];
	}

	/**
	 * Iframe on receipt page
	 *
	 * @param int $order_id
	 *
	 * @return void
	 */
	public function process_iframe( $order_id ) {

		$order = wc_get_order( $order_id );

		$order_id = (string) $order->get_id();

		if ( $this->payment->get_option_bool( 'test_mode' ) ) {
			$order_id = Monri_WC_Utils::get_test_order_id( $order_id );
		}

		$req = [];

		if ( $order->get_meta( '_monri_order_token_used' ) ) {
			$order->delete_meta_data( '_monri_order_token_used' );
			$order->save_meta_data();
		}

		if ( $this->tokenization_enabled() && is_checkout() && is_user_logged_in() ) {

			if ( isset( $_GET['use_token'] ) && $_GET['use_token'] === '-1' ) {
				$req['IsTokenRequest'] = '1';
			} elseif ( isset( $_GET['use_token'] ) && is_numeric( $_GET['use_token'] ) ) {
				$token_id = sanitize_text_field( $_GET['use_token'] );
				$tokens   = $this->payment->get_tokens();

				// redirect to cart with error?? should never happen
				if ( ! isset( $tokens[ $token_id ] ) ) {
					echo esc_html( __( 'Token does not exist.', 'monri' ) );
					return;
				}

				/** @var Monri_WC_Payment_Token_Wspay $use_token */
				$token = $tokens[ $token_id ];

				$req['Token']       = $token->get_token();
				$req['TokenNumber'] = $token->get_last4();

				$order->update_meta_data( '_monri_order_token_used', 1 );
				$order->save_meta_data();

				// use different shop_id/secret for tokenization if token is used
				$this->use_tokenization_credentials();
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

		$req['Iframe']               = 'True';
		$req['IframeResponseTarget'] = 'TOP';

		$req = apply_filters( 'monri_wspay_iframe_request', $req );

		$order->add_meta_data( 'monri_wspay_transaction_type', $this->payment->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase' );
		$order->save_meta_data();

		Monri_WC_Logger::log( 'Request data: ' . print_r( $req, true ), __METHOD__ );

		wc_get_template( 'iframe-form.php', [
			'action'  => $this->payment->get_option_bool( 'test_mode' ) ? self::FORM_ENDPOINT_TEST : self::FORM_ENDPOINT,
			'options' => $req,
			'order'   => $order
		], basename( MONRI_WC_PLUGIN_PATH ), MONRI_WC_PLUGIN_PATH . 'templates/' );
	}
}
