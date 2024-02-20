<?php

class Monri_WC_Gateway_Adapter_Wspay {

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
	 * @param Monri_WC_Gateway $payment
	 *
	 * @return void
	 */
	public function init( $payment ) {
		$this->payment = $payment;

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

		// add tokenization support
		if ( $this->tokenization_enabled() ) {
			$this->payment->supports[] = 'tokenization';
		}

		add_action( 'woocommerce_thankyou_monri', [ $this, 'thankyou_page' ] );
		//add_action( 'woocommerce_thankyou', [ $this, 'process_return' ] );

		/*
		add_filter( 'woocommerce_thankyou_order_received_text', function() {
			return '121212';
		}, 10, 2 );
		*/
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

		if ( $this->tokenization_enabled() && is_checkout() ) {
			$this->payment->tokenization_script();
			$this->payment->saved_payment_methods();
		}
	}

	/**
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order        = wc_get_order( $order_id );
		$order_number = (string) $order->get_id();

		$order_number .= '-test' . time();

		$req                   = [];
		$req['shopID']         = $this->shop_id;
		$req['shoppingCartID'] = $order_number;

		$amount = number_format( $order->get_total(), 2, ',', '' );
		$req['totalAmount'] = $amount;

		$req['signature'] = $this->sign_transaction( $order_number, $amount );
		//$req['returnURL'] = site_url() . '/ws-pay-redirect'; // directly to success

		$req['returnURL'] = $order->get_checkout_order_received_url();

		// TODO: implement this in a different way
		//$req['returnErrorURL'] = $order->get_cancel_endpoint();
		//$req['cancelURL']      = $order->get_cancel_endpoint();

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

		// check if user is logged in
		// check if tokenization is enabled on settings
		// TODO: is token request should be depending on if save card for future payments is selected
		if ( isset( $_POST['ws-pay-tokenized-card'] ) ) {
			$tokenized_card = $_POST['ws-pay-tokenized-card'];
		}

		$payment_with_token = ! empty( $tokenized_card ) && ( $tokenized_card !== 'not-selected' );

		if ( $this->tokenization_enabled() && ! $payment_with_token ) {
			$req['IsTokenRequest'] = '1';
		}

		if ( $payment_with_token ) {
			$decoded_card       = json_decode( base64_decode( $tokenized_card ) );
			$req['Token']       = $decoded_card[0];
			$req['TokenNumber'] = $decoded_card[1];
		}

		// After successful transaction WSPayForm redirects to ReturnURL as described in Parameters which
		// WSPayForm returns to web shop - ReturnURL with three additional parameters:
		// Token - unique identifier representing payment type for the single user of the web shop
		// TokenNumber – number that corresponds to the last 4 digits of the credit card
		// TokenExp – presenting expiration date of the credit card (YYMM)

		// Payment using token
		// <input type="hidden" name="Token" value="e32c9607-f77d-44d5-98e8-e58c9f279bfd">
		// <input type="hidden" name="TokenNumber" value="0189">

		$response = $this->api( '/api/create-transaction', $req );

		if ( isset( $response['PaymentFormUrl'] ) ) {
			return [
				'result'   => 'success',
				'redirect' => $response['PaymentFormUrl']
			];

		} else {
			Monri_WC_Logger::log( $response, __METHOD__ );

			return array(
				'result'  => 'failure',
				'message' => __( 'Gateway currently not available.' ),
			);
		}
	}


	public function show_message($message, $class = '') {
		return '<div class="box ' . $class . '-box">' . $message . '</div>';
	}

	/**
	 * @return void
	 */
	public function thankyou_page() {

		//echo $this->show_message('wqewqeqe', 'woocommerce_message woocommerce_error');
		//echo 12345;
		//return;

		$order_id = $_REQUEST['ShoppingCartID']; // is there wp param?

		$order_id = strstr($order_id, '-test', true);

		$order = wc_get_order( $order_id );

		if ( ! $order || $order->get_payment_method() !== $this->payment->id ) {
			return;
		}

		//$order_id = wc_get_order_id_by_order_key($_REQUEST['key']); // load by wp key?

		if ( ! $this->validate_return( $_REQUEST ) ) {
			// throw error? redirect to error?
			return;
		}

		$lang = Monri_WC_i18n::get_translation();

		//wp_enqueue_style('thankyou-page', plugins_url() . '/woocommerce-monri/assets/style/thankyou-page.css');

		if ( $order->get_status() === 'completed' ) {

			$this->msg['message'] = $lang['THANK_YOU_SUCCESS'];
			$this->msg['class']   = 'woocommerce_message';

		} else {

			$success        = $_REQUEST['Success'] ?? '0';
			$approval_code  = $_REQUEST['ApprovalCode'] ?? null;
			$trx_authorized = $success === '1' && ! empty( $approval_code );

			if ( $trx_authorized ) {
				$this->msg['message'] = $lang['THANK_YOU_SUCCESS'];
				$this->msg['class']   = 'woocommerce_message';

				$order->payment_complete();
				$order->add_order_note( $lang['MONRI_SUCCESS'] . $approval_code );
				//$order->add_order_note($this->msg['message']);
				WC()->cart->empty_cart();

			} else {
				$this->msg['class']   = 'woocommerce_error';
				$this->msg['message'] = $lang['THANK_YOU_DECLINED'];

				$order->update_status( 'failed' );
				$order->add_order_note( 'Failed' );
				//$order->add_order_note($message);
			}

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
	 * @param array $request
	 *
	 * @return bool
	 */
	private function validate_return( $request ) {

		if ( ! isset( $request['ShoppingCartID'], $request['Signature'] ) ) {
			return false;
		}

		$order_id      = $request['ShoppingCartID'];
		$digest        = $request['Signature'];
		$success       = $request['Success'] ?? '0';
		$approval_code = $request['ApprovalCode'] ?? '';

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

		return $digest === $check_digest;
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
				'body'      => json_encode( $params ),
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

}
