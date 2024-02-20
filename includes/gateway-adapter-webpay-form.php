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
        add_action('woocommerce_thankyou', [$this, 'process_return']);
	}

	/**
	 * Process the payment and redirect to
	 **/
	public function process_payment($order_id) {
		$order = wc_get_order($order_id);

		return [
			'result' => 'success',
			'redirect' => $order->get_checkout_payment_url(true)
		];
	}

	public function validate_fields() {

        $lang = Monri_WC_i18n::get_translation();
        $post_data = wc()->checkout()->get_posted_data();

        if (empty($post_data['billing_first_name']) || strlen($post_data['billing_first_name']) < 3 || strlen($post_data['billing_first_name']) > 11) {
            throw new Exception($lang['FIRST_NAME_ERROR']);
        }
        if (empty($post_data['billing_last_name']) || strlen($post_data['billing_last_name']) < 3 || strlen($post_data['billing_last_name']) > 18) {
            throw new Exception($lang['LAST_NAME_ERROR']);
        }
        if (empty($post_data['billing_address_1']) || strlen($post_data['billing_address_1']) < 3 || strlen($post_data['billing_address_1']) > 300) {
            throw new Exception($lang['ADDRESS_ERROR']);
        }
        if (empty($post_data['billing_city']) || strlen($post_data['billing_city']) < 3 || strlen($post_data['billing_city']) > 30) {
            throw new Exception($lang['CITY_ERROR']);
        }
        if (empty($post_data['billing_postcode']) || strlen($post_data['billing_postcode']) < 3 || strlen($post_data['billing_postcode']) > 9) {
            throw new Exception($lang['ZIP_ERROR']);
        }
        if (empty($post_data['billing_phone']) || strlen($post_data['billing_phone']) < 3 || strlen($post_data['billing_phone']) > 30) {
            throw new Exception($lang['PHONE_ERROR']);
        }
        if (empty($post_data['billing_email']) || strlen($post_data['billing_email']) < 3 || strlen($post_data['billing_email']) > 100) {
            throw new Exception($lang['EMAIL_ERROR']);
        }

		return true;
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
			'success_url_override' => $this->payment->get_return_url($order), // from
			'cancel_url_override' => $order->get_cancel_order_url(),
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

        Monri_WC_Logger::log("Request data: " . print_r($args, true), __METHOD__);

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
		$lang = Monri_WC_i18n::get_translation();

        Monri_WC_Logger::log("Response data: " . print_r($_REQUEST, true), __METHOD__);
        $order_id = $_REQUEST['order_number'];

        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);

        if ($order->get_payment_method() !== $this->payment->id) {
            return;
        }

        if ($order->get_status() === 'completed') {
            return;
        }

        if (empty($_REQUEST['approval_code']) || empty($_REQUEST['digest'])) {
            $order->update_status('failed');
            return;
		}

		//wp_enqueue_style('thankyou-page', plugins_url() . '/woocommerce-monri/assets/style/thankyou-page.css');

		try {

			$digest = $_REQUEST['digest'];
			$response_code = $_REQUEST['response_code'];

            $thankyou_page = $this->payment->get_return_url($order);
			$url = strtok($thankyou_page, '?');

			$query_string = $this->get_query_string();
			$full_url = $url . '?' . $query_string;

			$calculated_url = preg_replace('/&digest=[^&]*/', '', $full_url);
			//Generate digest
			$check_digest = hash('sha512', $this->settings->get_option('monri_merchant_key') . $calculated_url);

            if ($digest !== $check_digest) {
                $order->update_status('failed', 'Mismatch between digest and calculated digest');
                return;
            }

            if ($response_code == "0000") {

                if ($order->get_status() !== 'processing') {
                    $order->payment_complete();
                    $order->add_order_note($lang["MONRI_SUCCESS"] . $_REQUEST['approval_code']);
                    $order->add_order_note($lang["THANK_YOU_SUCCESS"]);
                    $order->add_order_note("Issuer: " . $_REQUEST['issuer']);

                    if ($_REQUEST['number_of_installments'] > 1) {
                        $order->add_order_note($lang['NUMBER_OF_INSTALLMENTS'] . ": " . $_REQUEST['number_of_installments']);
                    }

                    WC()->cart->empty_cart();
                }

            } else if ($response_code == "pending") {
                $order->add_order_note($lang['MONRI_PENDING'] . $_REQUEST['approval_code']);
                $order->add_order_note($lang["THANK_YOU_PENDING"]);
                $order->add_order_note("Issuer: " . $_REQUEST['issuer']);

                if ($_REQUEST['number_of_installments'] > 1) {
                    $order->add_order_note($lang['NUMBER_OF_INSTALLMENTS'] . ": " . $_REQUEST['number_of_installments']);
                }

                $order->update_status('on-hold');
                WC()->cart->empty_cart();

            } else {
                $order->update_status('failed', 'Response not authorized');
                $order->add_order_note($lang['THANK_YOU_DECLINED_NOTE'] . $_REQUEST['Error']);
            }

		} catch (Exception $e) {
            Monri_WC_Logger::log("Error while processing response for order $order_id: " . $e->getMessage(),
                __METHOD__
            );

            $order->update_status('failed', 'Error while checking form response');
		}

	}
}
