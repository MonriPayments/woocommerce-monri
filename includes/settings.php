<?php

class Monri_WC_Settings
{

	// process admin options here?

	const CODE = 'monri';

	const SETTINGS_KEY = 'woocommerce_monri_settings';

	/**
	 * @var Monri_WC_Settings
	 */
	private static $instance;

	/**
	 * @return Monri_WC_Settings
	 */
	public static function instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return array[]
	 */
	public function get_form_fields() {

		$yes_or_no = array(
			"0" => 'No',
			"1" => 'Yes'
		);

		$integration_types = array(
			"form" => 'Form',
			"components" => 'Components'
		);

		$transaction_type = array(
			"0" => "Purchase",
			"1" => "Authorize"
		);

		$number_of_allowed_installments = array(
			"24" => "24",
			"12" => "12",
			"6" => "6"
		);

		$form_language = array(
			"en" => "English",
			"de" => "German",
			"ba-hr" => "Bosanski",
			"hr" => "Hrvatski",
			"sr" => "Srpski"
		);

		$payment_gateway_services = array(
			"monri-web-pay" => "Monri WebPay",
			"monri-ws-pay" => "Monri WSPay"
		);

		$form_id = 'monri';

		$form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', $form_id),
				'type' => 'checkbox',
				'label' => __('Enable Monri', $form_id),
				'default' => 'no'
			),
			'monri_payment_gateway_service' => array(
				'title' => __('Payment Gateway Service:', $form_id),
				'type' => 'select',
				'class' => 'chosen_select',
				'css' => 'width: 450px;',
				'default' => 'monri-web-pay',
				'description' => __('', $form_id),
				'options' => $payment_gateway_services,
				'desc_tip' => true,
			),
			'title' => array(
				'title' => __('Title', $form_id),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', $form_id),
				'desc_tip' => true,
				'default' => __('Monri', $form_id)
			),
			'description' => array(
				'title' => __('Description', $form_id),
				'type' => 'textarea',
				'description' => __('This controls the description which the user sees during checkout.', $form_id),
				'default' => __('Description for Monri', $form_id)
			),
			'instructions' => array(
				'title' => __('Instructions', $form_id),
				'type' => 'textarea',
				'description' => __('Instructions that will be added to the thank you page.', $form_id),
				'default' => __('Instructions for Monri.', $form_id)
			),
			'thankyou_page' => array(
				'title' => __('Success page', $form_id),
				'type' => 'text',
				'description' => __('Success URL potrebno je kopirati u Monri Account na predviđeno mjesto! ', $form_id),
				'desc_tip' => true,
				'default' => __(wc_get_checkout_url() . get_option('woocommerce_checkout_order_received_endpoint', 'order-received'), $form_id)
			),
			'callback_url_endpoint' => array(
				'title' => __('Callback URL endpoint', 'wcwcGpg1'),
				'type' => 'text',
				'description' => __('Monri Callback URL endpoint koji će primati POST zahtjev sa Monri Gateway-a.', $form_id),
				'desc_tip' => true,
				'default' => '/monri-callback',
				$form_id,
			),
			'success_url_override' => array(
				'title' => __('Success URL override', 'wcwcGpg1'),
				'type' => 'text',
				'description' => __('Success URL koji želite koristiti pri svakoj transakciji. (HTTPS)', $form_id),
				'desc_tip' => true,
				'default' => '',
				$form_id,
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'cancel_url_override' => array(
				'title' => __('Cancel URL override', 'wcwcGpg1'),
				'type' => 'text',
				'description' => __('Cancel URL koji želite koristiti pri svakoj transakciji. (HTTPS)', $form_id),
				'desc_tip' => true,
				'default' => '',
				$form_id,
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'callback_url_override' => array(
				'title' => __('Callback URL override', 'wcwcGpg1'),
				'type' => 'text',
				'description' => __('Callback URL koji želite koristiti pri svakoj transakciji. (HTTPS)', $form_id),
				'desc_tip' => true,
				'default' => '',
				$form_id,
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'monri_merchant_key' => array(
				'title' => __('Monri Key', $form_id),
				'type' => 'text',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => __('', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'monri_authenticity_token' => array(
				'title' => __('Monri authenticity token', $form_id),
				'type' => 'text',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => __('', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'monri_ws_pay_form_shop_id' => array(
				'title' => __('Monri WsPay Form ShopId', $form_id),
				'type' => 'text',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => __('', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_secret' => array(
				'title' => __('Monri WsPay Form Secret', $form_id),
				'type' => 'text',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => __('', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_tokenization_enabled' => array(
				'title' => __('Monri WsPay Form Tokenization Enabled', $form_id),
				'type' => 'checkbox',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => 'no',
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_tokenization_shop_id' => array(
				'title' => __('Monri WsPay Form Tokenization ShopId', $form_id),
				'type' => 'text',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => __('', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_tokenization_secret' => array(
				'title' => __('Monri WsPay Form Tokenization Secret', $form_id),
				'type' => 'text',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => __('', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			//            'monri_ws_pay_components_shop_id' => array(
//                'title' => __('Monri WsPay Components ShopId', $form_id),
//                'type' => 'text',
//                'description' => __('', $form_id),
//                'desc_tip' => true,
//                'default' => __('', $form_id),
//                'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
//            ),
//            'monri_ws_pay_components_secret' => array(
//                'title' => __('Monri WsPay Components Secret', $form_id),
//                'type' => 'text',
//                'description' => __('', $form_id),
//                'desc_tip' => true,
//                'default' => __('', $form_id),
//                'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
//            ),
			'monri_web_pay_integration_type' => array(
				'title' => __('Integration type:', $form_id),
				'type' => 'select',
				'class' => 'chosen_select woocommerce-monri-dynamic-option monri-web-pay-option',
				'css' => 'width: 450px;',
				'default' => true,
				'description' => __('', $form_id),
				'options' => $integration_types,
				'desc_tip' => true,
			),
			'test_mode' => array(
				'title' => __('Test mode enabled:', $form_id),
				'type' => 'select',
				'class' => 'chosen_select',
				'css' => 'width: 450px;',
				'default' => 0,
				'description' => __('', $form_id),
				'options' => $yes_or_no,
				'desc_tip' => true,
			),
			'transaction_type' => array(
				'title' => __('Transaction type:', $form_id),
				'type' => 'select',
				'class' => 'chosen_select woocommerce-monri-dynamic-option monri-web-pay-option',
				'css' => 'width: 450px;',
				'default' => 0,
				'description' => __('', $form_id),
				'options' => $transaction_type,
				'desc_tip' => true
			),
			'form_language' => array(
				'title' => __('Form language:', $form_id),
				'type' => 'select',
				'class' => 'chosen_select',
				'css' => 'width: 450px;',
				'default' => 'EN',
				'description' => __('', $form_id),
				'options' => $form_language,
				'desc_tip' => true,
			),
			'paying_in_installments' => array(
				'title' => __('Allow paying in installments', $form_id),
				'type' => 'select',
				'class' => 'chosen_select woocommerce-monri-dynamic-option monri-web-pay-option',
				'css' => 'width: 450px;',
				'default' => 0,
				'description' => __('', $form_id),
				'options' => $yes_or_no,
				'desc_tip' => true,
			),
			'number_of_allowed_installments' => array(
				'title' => __('Number of allowed installments', $form_id),
				'type' => 'select',
				'class' => 'chosen_select woocommerce-monri-dynamic-option monri-web-pay-option',
				'css' => 'width: 450px;',
				'default' => 0,
				'description' => __('', $form_id),
				'options' => $number_of_allowed_installments,
				'desc_tip' => true,
			),
			'bottom_limit' => array(
				'title' => __('Price limit for paying in installments:', $form_id),
				'type' => 'text',
				'description' => __('This controls the bottom price limit on which the installments can be used.', $form_id),
				'desc_tip' => true,
				'default' => __('0', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			)
		);

		for ($i = 2; $i <= 24; $i++) {
			$form_fields["price_increase_$i"] = array(
				'title' => sprintf(__('Price increase when paying in %d installments:', $form_id), $i),
				'type' => 'text',
				'description' => __('This controls the price increase when paying with installments.', $form_id),
				'desc_tip' => true,
				'default' => __('0', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			);
		}

		return $form_fields;
	}

	/**
	 * @param $key
	 * @param mixed $default
	 * @return mixed|null
	 */
	public function get_option( $key, $default = null )
	{
		$settings = get_option( self::SETTINGS_KEY, [] );

		if ( isset( $settings[$key] ) ) {
			return $settings[$key];
		}

		if ( isset( $this->get_form_fields()[$key]['default'] ) ) {
			return $this->get_form_fields()[$key]['default'];
		}

		return $default;
	}

	/**
	 * @param $options
	 * @return bool
	 */
	public function update_options( $options )
	{
		$settings = get_option( self::SETTINGS_KEY, [] );
		$settings = array_merge( $settings, $options );

		return update_option( self::SETTINGS_KEY, $settings );
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function get_option_bool( $key )
	{
		return in_array( $this->get_option( $key ),  array( 'yes', '1', true ), true );
	}

	/**
	 * @return bool
	 */
	public function is_enabled()
	{
		return $this->get_option_bool('enabled') &&
			$this->get_option('client_id') &&
			$this->get_option('client_secret');
	}

}
