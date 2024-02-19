<?php

class Monri_WC_Gateway_Adapter_Webpay_Form
{
	public const ADAPTER_ID = 'webpay_form';

	public const ENDPOINT_TEST =  'https://ipgtest.monri.com/v2/form';
	public const ENDPOINT = 'https://ipg.monri.com/v2/form';

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
		//$this->id = 'monri';

		// treba settingse/optione, treba id

		$this->settings = Monri_WC_Settings::instance();
	}

	public function init($payment) {
		$this->payment = $payment;
		//$this->payment->id

		//$this->check_monri_response();
		add_action('woocommerce_receipt_monri', [$this, 'process_redirect']);
		add_action('woocommerce_thankyou_monri', [$this, 'process_return']);
	}

	/**
	 * Process the payment and redirect to
	 **/
	public function process_payment($order_id) {
		$order = wc_get_order($order_id);

		//@todo validate in validate_fields() ??
		$validation = $this->validate_form_fields($order);

		// @todo regulate error, empty return !!
		/*
		return array(
			'result'   => 'failure',
			'redirect' => $order->get_checkout_payment_url( true ),
			'message'  => $e->getMessage(),
		);
		*/

		if (!empty($validation)) {
			wc_add_notice($validation[0], 'error');
			return;
		}
		//

		return [
			'result' => 'success',
			'redirect' => $order->get_checkout_payment_url(true)
		];

		/*
		return array(
			'result' => 'success',
			'redirect' => add_query_arg(
				'order',
				$order->get_id(),
				add_query_arg('key', $order->get_order_key(), wc_get_checkout_url())
			)
		);
		*/
	}

	public function validate_fields() {
		return false;
		//$post_data = wc()->checkout()->get_posted_data(); // use this or $_POST
		throw new Exception('lol'); // throw on error
		return;
	}

	/**
	 * Validate WooCommerce Form fields
	 * @todo refactor to validate_fields()
	 **/
	public function validate_form_fields($order) {
		$lang = Monri_WC_i18n::get_translation();
		$validation = [];

		if (strlen($order->billing_first_name) < 3 || strlen($order->billing_first_name) > 11) {
			$validation[] = $lang['FIRST_NAME_ERROR'];
		}
		if (strlen($order->billing_last_name) < 3 || strlen($order->billing_last_name) > 18) {
			$validation[] = $lang['LAST_NAME_ERROR'];
		}
		if (strlen($order->billing_address_1) < 3 || strlen($order->billing_address_1) > 300) {
			$validation[] = $lang['ADDRESS_ERROR'];
		}
		if (strlen($order->billing_city) < 3 || strlen($order->billing_city) > 30) {
			$validation[] = $lang['CITY_ERROR'];
		}
		if (strlen($order->billing_postcode) < 3 || strlen($order->billing_postcode) > 9) {
			$validation[] = $lang['ZIP_ERROR'];
		}
		if (strlen($order->billing_phone) < 3 || strlen($order->billing_phone) > 30) {
			$validation[] = $lang['PHONE_ERROR'];
		}
		if (strlen($order->billing_email) < 3 || strlen($order->billing_email) > 100) {
			$validation[] = $lang['EMAIL_ERROR'];
		}
		return $validation;
	}

	/**
	 *Form integration
	 **/
	function process_redirect($order_id) {
		$order = wc_get_order($order_id);

		$key = $this->settings->get_option('monri_merchant_key');
		$token = $this->settings->get_option('monri_authenticity_token');

		//Convert order amount to number without decimals
		$order_total = $order->get_total() * 100;

		$currency = $order->get_currency();
		if ($currency === 'KM') {
			$currency = 'BAM';
		}

		//Generate digest key
		$digest = hash('sha512', $key . $order_id . $order_total . $currency);

		//Combine first and last name in one string
		$full_name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();

		//Array of order information
		$args = array(
			'ch_full_name' => $full_name,
			'ch_address' => $order->get_billing_address_1(),
			'ch_city' => $order->get_billing_city(),
			'ch_zip' => $order->get_billing_postcode(),
			'ch_country' => $order->get_billing_country(),
			'ch_phone' => $order->get_billing_phone(),
			'ch_email' => $order->get_billing_email(),

			'order_info' => $order_id . '_' . date('dmy'),
			'order_number' => $order_id,
			'amount' => $order_total,
			'currency' => $currency,
			'original_amount' => $order->get_total(),

			'language' => $this->settings->get_option('form_language'),
			'transaction_type' => $this->settings->get_option_bool('transaction_type') ? 'authorize' : 'purchase',
			'authenticity_token' => $token,
			'digest' => $digest,
			'success_url_override' => $this->payment->get_return_url() . '&status=success', // from
			'cancel_url_override' => $this->payment->get_return_url() . '&status=cancel',
		);

		/*
		if ($success_url_override = $this->get_option('success_url_override')) {
			$
		}

		if ($cancel_url_override = $this->get_option('cancel_url_override')) {
			$args['cancel_url_override'] = $cancel_url_override;
		}

		if ($callback_url_override = $this->get_option('callback_url_override')) {
			$args['callback_url_override'] = $callback_url_override;
		}
		*/

		wc_get_template('redirect-form.php', [
			'action' => $this->settings->get_option_bool('test_mode') ? self::ENDPOINT_TEST : self::ENDPOINT,
			'options' => $args,
			'order' => $order
		], basename(MONRI_WC_PLUGIN_PATH), MONRI_WC_PLUGIN_PATH . 'templates/' );
	}

	/**
	 * In some cases page url is rewritten and it contains page path and query string.
	 * @return string
	 */
	private function get_query_string() {
		$arr = explode('?', $_SERVER['REQUEST_URI']);
		// If there's more than one '?' shift and join with ?, it's special case of having '?' in success url
		// eg http://testiranjeintegracija.net/?page_id=6order-recieved?

		if (count($arr) > 2) {
			array_shift($arr);
			return implode('?', $arr);
		}

		return end($arr);
	}

	/**
	 * Check for valid monri server callback
	 **/
	public function process_return() {
		global $woocommerce;

		$lang = Monri_WC_i18n::get_translation();

		if (isset($_REQUEST['approval_code']) && isset($_REQUEST['digest'])) {

		}

		wp_enqueue_style('thankyou-page', plugins_url() . '/woocommerce-monri/assets/style/thankyou-page.css');

		$order_id = $_REQUEST['order_number'];

		if (!$order_id) {
			return;
		}

		try {
			$order = new WC_Order($order_id);

			$digest = $_REQUEST['digest'];
			$response_code = $_REQUEST['response_code'];

			$thankyou_page = wc_get_checkout_url() . get_option('woocommerce_checkout_order_received_endpoint', 'order-received');
			$url = strtok($thankyou_page, '?');

			$query_string = $this->get_query_string();
			$full_url = $url . '?' . $query_string;

			$calculated_url = preg_replace('/&digest=[^&]*/', '', $full_url);
			//Generate digest
			$check_digest = hash('sha512', $this->settings->get_option('monri_authenticity_token') . $calculated_url);

			$trx_authorized = false;
			if ($order->status !== 'completed') {
				if ($digest == $check_digest) {
					if ($response_code == "0000") {
						$trx_authorized = true;
						$this->msg['message'] = $lang["THANK_YOU_SUCCESS"];
						$this->msg['class'] = 'woocommerce_message';

						if ($order->status == 'processing') {

						} else {
							$order->payment_complete();
							$order->add_order_note($lang["MONRI_SUCCESS"] . $_REQUEST['approval_code']);
							$order->add_order_note($this->msg['message']);
							$order->add_order_note("Issuer: " . $_REQUEST['issuer']);
							if ($_REQUEST['number_of_installments'] > 1) {
								$order->add_order_note($lang['NUMBER_OF_INSTALLMENTS'] . ": " . $_REQUEST['number_of_installments']);
							}
							$woocommerce->cart->empty_cart();
						}
					} else if ($response_code == "pending") {
						$this->msg['message'] = $lang["THANK_YOU_PENDING"];
						$this->msg['class'] = 'woocommerce_message woocommerce_message_info';
						$order->add_order_note($lang['MONRI_PENDING'] . $_REQUEST['approval_code']);
						$order->add_order_note($this->msg['message']);
						$order->add_order_note("Issuer: " . $_REQUEST['issuer']);
						if ($_REQUEST['number_of_installments'] > 1) {
							$order->add_order_note($lang['NUMBER_OF_INSTALLMENTS'] . ": " . $_REQUEST['number_of_installments']);
						}
						$order->update_status('on-hold');
						$woocommerce->cart->empty_cart();
					} else {
						$this->msg['class'] = 'woocommerce_error';
						$this->msg['message'] = $lang['THANK_YOU_DECLINED'];
						$order->add_order_note($lang['THANK_YOU_DECLINED_NOTE'] . $_REQUEST['Error']);
					}
				} else {
					$this->security_error($lang);

				}
				if ($trx_authorized == false) {
					$order->update_status('failed');
					$order->add_order_note('Failed');
					$order->add_order_note($this->msg['message']);
				}

				add_action('the_content', array(&$this, 'showMessage'));
			}
		} catch (Exception $e) {
			// $errorOccurred = true;
			$msg = "Error";
		}

	}

	function showMessage($content) {
		return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
	}

	/**
	 * @param $lang
	 * @return void
	 */
	public function security_error($lang) {
		$this->msg['class'] = 'error';
		$this->msg['message'] = $lang['SECURITY_ERROR'];
	}

}
