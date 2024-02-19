<?php

class Monri_WC_Gateway_Adapter_Wspay
{
	public const ADAPTER_ID = 'wspay';

	public const ENDPOINT_TEST = 'https://formtest.wspay.biz';
	public const ENDPOINT = 'https://form.wspay.biz';

	/**
	 * @var Monri_WC_Settings
	 */
	private $settings;

	public $has_fields = false;

	/**
	 * @var Monri_WC_Gateway
	 */
	private $payment;

	public function __construct()
	{
		//$this->settings = Monri_WC_Settings::instance();
	}

	public function init($payment) {
		$this->payment = $payment;

		// add tokenization support
		$this->payment->supports = [ 'products', 'tokenization' ];

		add_action('woocommerce_thankyou_' . $this->payment->id, [$this, 'process_return']);
	}

	public function payment_fields()
	{
		// if tokenization
		$this->payment->tokenization_script();
		$this->payment->saved_payment_methods();
	}

	public function process_payment($order_id)
	{
		$order = wc_get_order( $order_id );

		$url = $this->payment->get_option_bool('test_mode') ? self::ENDPOINT_TEST : self::ENDPOINT;

		$req = [];
		$req["shopID"] = $this->api->api_username();
		$req["shoppingCartID"] = $order->get_order_number();
		$amount = number_format($order->get_total(), 2, ',', '');
		$req["totalAmount"] = $amount;
		$req["signature"] = $this->sign_transaction($this->api->api_password(), $this->api->api_username(), $req["shoppingCartID"], $amount);


		$req['returnURL'] = site_url() . '/ws-pay-redirect'; // directly to success
		// TODO: implement this in a different way
		$req["returnErrorURL"] = $order->get_cancel_endpoint();


		$req["cancelURL"] = $order->get_cancel_endpoint();
		$req["version"] = "2.0";
		$req["customerFirstName"] = $order->get_billing_first_name();
		$req["customerLastName"] = $order->get_billing_last_name();
		$req["customerAddress"] = $order->get_billing_address_1();
		$req["customerCity"] = $order->get_billing_city();
		$req["customerZIP"] = $order->get_billing_postcode();
		$req["customerCountry"] = $order->get_billing_country();
		$req["customerPhone"] = $order->get_billing_phone();
		$req["customerEmail"] = $order->get_billing_email();

		// check if user is logged in
		// check if tokenization is enabled on settings
		// TODO: is token request should be depending on if save card for future payments is selected
		if (isset($_POST['ws-pay-tokenized-card'])) {
			$tokenized_card = $_POST['ws-pay-tokenized-card'];
		}

		$payment_with_token = !empty($tokenized_card) && ($tokenized_card !== 'not-selected');

		if ($this->api->tokenization_enabled() && !$payment_with_token) {
			$req['IsTokenRequest'] = '1';
		}

		if($payment_with_token) {
			$decoded_card = json_decode(base64_decode($tokenized_card));
			$req['Token'] = $decoded_card[0];
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

		$response = $this->curlJSON($url . "/api/create-transaction", $req);

		if (isset($response['PaymentFormUrl'])) {
			return $response['PaymentFormUrl'];
		} else {

			/*
			error_log("Error while generating WsPay payment form");
			error_log("Url: $url");
			error_log("Request: " . print_r($req, true));
			error_log("Response: " . print_r($req, true));
			error_log("Order info: " . print_r($order->get_data(), true));
			return "";
			*/

			return array(
				'result'   => 'failure',
				'message'  => __('Something went wrong'),
			);

		}
	}

	public function process_return() {

		//wp_enqueue_style('thankyou-page', plugins_url() . '/woocommerce-monri/assets/style/thankyou-page.css');
		$order_id = $_REQUEST['ShoppingCartID'];

		if ($order_id != '') {
			try {
				$order = new WC_Order($order_id);

				if ($order->get_status() === 'completed') {
					$this->msg['message'] = $lang["THANK_YOU_SUCCESS"];
					$this->msg['class'] = 'woocommerce_message';
				} else {
					$digest = $_REQUEST['Signature'];
					$success = $_REQUEST['Success'] ?? '0';
					$approval_code = $_REQUEST['ApprovalCode'] ?? null;
					$shop_id = $this->api->api_username();
					$secret_key = $this->api->api_password();
					// ShopID
					// SecretKey
					// ShoppingCartID
					// SecretKey
					// Success
					// SecretKey
					// ApprovalCode
					// SecretKey
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
					$check_digest = hash('sha512', join("", $digest_parts));
					if ($check_digest != $digest) {
						$this->security_error($lang);
					} else {
						$trx_authorized = $success == '1' && !empty($approval_code);
						if ($trx_authorized) {
							$this->msg['message'] = $lang["THANK_YOU_SUCCESS"];
							$this->msg['class'] = 'woocommerce_message';
							$order->payment_complete();
							$order->add_order_note($lang["MONRI_SUCCESS"] . $_REQUEST['approval_code']);
							$order->add_order_note($this->msg['message']);
							$woocommerce->cart->empty_cart();
						} else {
							$this->msg['class'] = 'woocommerce_error';
							$this->msg['message'] = $lang['THANK_YOU_DECLINED'];
							$this->order_failed($order);
						}
					}
				}
			} catch (Exception $e) {
				// $errorOccurred = true;
				$msg = "Error";
			}
		} else {
			$this->msg['class'] = 'woocommerce_error';
			$this->msg['message'] = $lang['THANK_YOU_DECLINED'];
		}
	}

	private function sign_transaction($secretKey, $shopId, $shoppingCartId, $totalAmount) {
		$amount = preg_replace('~\D~', '', $totalAmount);
		return hash("sha512", $shopId . $secretKey . $shoppingCartId . $secretKey . $amount . $secretKey);
	}

	/**
	 * Send POST request to $url with $params as a field
	 *
	 * @param string $url
	 * @param array $params
	 * @return array
	 */
	private function curlJSON($url, $params) {
		$result = wp_remote_post($url, [
				'body' => json_encode($params),
				'headers' => [
					'Accept' =>  'application/json',
					'Content-Type' => 'application/json'
				],
				'method' => 'POST',
				'timeout' => 15,
				'sslverify' => false
			]
		);

		return json_decode($result['body'], true);
	}

}
