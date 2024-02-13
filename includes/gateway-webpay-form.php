<?php

require_once __DIR__ . '/gateway-abstract.php';
class Monri_WC_Gateway_Webpay_Form extends Monri_WC_Gateway //WC_Payment_Gateway
{
	// Setup our Gateway's id, description and other values
	function __construct()
	{
		// The global ID for this Payment method
		$this->id = 'monri'; //monri_webpay_form

		// The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
		$this->method_title = __('Monri', 'monri');

		// The description for this Payment Gateway, shown on the actual Payment options page on the backend
		$this->method_description = __("Monri Payment Gateway Plug-in for WooCommerce", 'monri');

		//$this->title = ;

		// If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
		//$this->icon = null;

		// This basically defines your settings which are then loaded with init_settings()
		$this->init_form_fields();

		// After init_settings() is called, you can get the settings and load them into variables, e.g:
		// $this->title = $this->get_option( 'title' );
		$this->init_settings();

		//$this->init_options();

		/*
		foreach ($this->settings as $setting_key => $value) {
			$this->$setting_key = $value;
		}
		*/

		// Define user set variables.
		// The title to be used for the vertical tabs that can be ordered top to bottom

		$this->title = $this->settings['title'] ?? __('Monri', 'monri');
		$this->description = $this->settings['description'];
		$this->instructions = $this->get_option('instructions');

		//add_option('woocommerce_pay_page_id', $page_id);

		// Lets check for SSL
		//add_action('admin_notices', array($this, 'do_ssl_check'));

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

		//$this->check_monri_response();
		add_action('woocommerce_thankyou_' . $this->id, [$this, 'payment_callback']);
		add_action('woocommerce_receipt_' . $this->id, [$this, 'process_redirect']);

		$this->has_fields = true;
	}

	public function init_form_fields() {
		$this->form_fields =  Monri_WC_Settings::instance()->get_form_fields();
	}

	/**
	 * Process the payment and return the result
	 **/
	function process_payment($order_id)
	{
		$order = wc_get_order($order_id);

		$validation = $this->validate_form_fields($order);

		if (!empty($validation)) {
			wc_add_notice($validation[0], 'error');
			return;
		}

		return array(
			'result' => 'success',
			'redirect' => add_query_arg(
				'order',
				$order->get_id(),
				add_query_arg('key', $order->order_key, wc_get_checkout_url())
			)
		);
	}

	/**
	 * Validate WooCommerce Form fields
	 **/
	function validate_form_fields($order)
	{
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
	function process_redirect($order_id)
	{
		$order = wc_get_order($order_id);
		$order_info = $order_id . '_' . date("dmy");

		$transaction_type = $this->transaction_type ? 'authorize' : 'purchase';

		// Check test mode
		if ($this->test_mode) {
			$url = 'https://ipgtest.monri.com/v2/form';
		} else {
			$url = 'https://ipg.monri.com/v2/form';
		}

		//Convert order amount to number without decimals
		$order_total = $order->order_total * 100;

		$currency = $order->get_currency();
		if ($currency === 'KM') {
			$currency = 'BAM';
		}

		//Generate digest key
		$digest = hash('sha512', $this->api->api_password() . $order->get_id() . $order_total . $currency);

		//Combine first and last name in one string
		$full_name = $order->billing_first_name . " " . $order->billing_last_name;

		//Array of order information
		$args = array(
			'ch_full_name' => $full_name,
			'ch_address' => $order->billing_address_1,
			'ch_city' => $order->billing_city,
			'ch_zip' => $order->billing_postcode,
			'ch_country' => $order->billing_country,
			'ch_phone' => $order->billing_phone,
			'ch_email' => $order->billing_email,

			'order_info' => $order_info,
			'order_number' => $order->get_order_number(),
			'amount' => $order_total,
			'currency' => $currency,
			'original_amount' => $order->order_total,

			'language' => $this->form_language,
			'transaction_type' => $transaction_type,
			'authenticity_token' => $this->api->api_username(),
			'digest' => $digest

		);

		if ($success_url_override = $this->get_option('success_url_override')) {
			$args['success_url_override'] = $success_url_override;
		}

		if ($cancel_url_override = $this->get_option('cancel_url_override')) {
			$args['cancel_url_override'] = $cancel_url_override;
		}

		if ($callback_url_override = $this->get_option('callback_url_override')) {
			$args['callback_url_override'] = $callback_url_override;
		}

		wc_get_template('templates/redirect-form.php', [
			'action' => $url,
			'options' => $args
		], 'woocommerce-monri' );
	}

	/**
	 * In some cases page url is rewritten and it contains page path and query string.
	 * @return string
	 */
	private function get_query_string()
	{
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
	public function check_monri_response() //process_response
	{
		global $woocommerce;

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
			$check_digest = hash('sha512', $this->api->api_password() . $calculated_url);
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

	function showMessage($content)
	{
		return '<div class="box ' . $this->msg['class'] . '-box">' . $this->msg['message'] . '</div>' . $content;
	}

	/**
	 * @param $lang
	 * @return void
	 */
	public function security_error($lang)
	{
		$this->msg['class'] = 'error';
		$this->msg['message'] = $lang['SECURITY_ERROR'];
	}

}
