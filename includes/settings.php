<?php

class Monri_WC_Settings {

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
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return array[]
	 */
	public function get_form_fields() {

		$domain = 'monri';

		$yes_or_no = array(
			'0' => 'No',
			'1' => 'Yes'
		);

		$integration_types = array(
			'form'       => __( 'Form', $domain ),
			'components' => __( 'Components', $domain )
		);

		$transaction_type = array(
			'0' => __( 'Purchase', $domain ),
			'1' => __( 'Authorize', $domain )
		);

		$number_of_allowed_installments = array(
			'24' => '24',
			'12' => '12',
			'6'  => '6'
		);

		$form_language = array(
			'en'    => 'English',
			'de'    => 'German',
			'ba-hr' => 'Bosanski',
			'hr'    => 'Hrvatski',
			'sr'    => 'Srpski'
		);

		$payment_gateway_services = array(
			'monri-web-pay' => 'Monri WebPay',
			'monri-ws-pay'  => 'Monri WSPay'
		);

		$form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', $domain ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Monri', $domain ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', $domain ),
				'type'        => 'text',
				'description' => __( 'Title which the customer sees during checkout.', $domain ),
				'desc_tip'    => true,
				'default'     => __( 'Monri', $domain )
			),
			'description' => array(
				'title'       => __( 'Description', $domain ),
				'type'        => 'textarea',
				'description' => __( 'Description which the customer sees during checkout.', $domain ),
				'default'     => __( 'Description for Monri', $domain )
			),
			'instructions' => array(
				'title'       => __( 'Instructions', $domain ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', $domain ),
				'default'     => __( 'Instructions for Monri.', $domain )
			),
			'thankyou_page' => array(
				'title' => __('Success page', $domain ),
				'type' => 'text',
				'description' => __('Suceess URL must be copied to Monri Account in responding field!', $domain ),
				'desc_tip' => true,
				'default' => __(wc_get_checkout_url() . get_option('woocommerce_checkout_order_received_endpoint', 'order-received'), $domain)
			),
			'callback_url_endpoint' => array(
				'title' => __('Callback URL endpoint', $domain),
				'type' => 'text',
				'description' => __('Callback URL endpoint which receives POST request from Monri Gateway.', $domain),
				'desc_tip' => true,
				'default' => '/monri-callback',
                $domain,
			),
			'success_url_override' => array(
				'title' => __('Success URL override', 'wcwcGpg1'),
				'type' => 'text',
				'description' => __('Success URL you would like to use for transaction. (HTTPS)', $domain),
				'desc_tip' => true,
				'default' => '',
                $domain,
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'cancel_url_override' => array(
				'title' => __('Cancel URL override', 'wcwcGpg1'),
				'type' => 'text',
				'description' => __('Cancel URL you would like to use for transaction. (HTTPS)', $domain),
				'desc_tip' => true,
				'default' => '',
                $domain,
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'callback_url_override' => array(
				'title' => __('Callback URL override', 'wcwcGpg1'),
				'type' => 'text',
				'description' => __('Callback URL you would like to use for transaction. (HTTPS)', $domain),
				'desc_tip' => true,
				'default' => '',
                $domain,
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
            ),

			'monri_payment_gateway_service' => array(
				'title'       => __( 'Payment gateway service:', $domain ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 'monri-web-pay',
				'options'     => $payment_gateway_services,
				'desc_tip'    => true,
			),
			'monri_web_pay_integration_type' => array(
				'title'       => __( 'Integration type', $domain ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select woocommerce-monri-dynamic-option monri-web-pay-option',
				'default'     => true,
				'options'     => $integration_types,
				'desc_tip'    => true,
			),
			'monri_merchant_key' => array(
				'title'       => __( 'Key', $domain ),
				'type'        => 'text',
				'desc_tip'    => true,
				'default'     => '',
				'class'       => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'monri_authenticity_token' => array(
				'title'       => __( 'Authenticity token', $domain ),
				'type'        => 'text',
				'desc_tip'    => true,
				'default'     => '',
				'class'       => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'monri_ws_pay_form_shop_id' => array(
				'title'       => __( 'Shop ID', $domain ),
				'type'        => 'text',
				'desc_tip'    => true,
				'default'     => '',
				'class'       => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_secret' => array(
				'title'       => __( 'Secret key', $domain ),
				'type'        => 'text',
				'desc_tip'    => true,
				'default'     => '',
				'class'       => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_tokenization_enabled' => array(
				'title'       => __( 'Tokenization Enable/Disable', $domain ),
				'type'        => 'checkbox',
				'label'   => __( 'Enable Tokenization', $domain ),
				'desc_tip'    => true,
				'default'     => 'no',
				'class'       => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_tokenization_shop_id' => array(
				'title'       => __( 'Tokenization Shop ID', $domain ),
				'type'        => 'text',
				'desc_tip'    => true,
				'default'     => '',
				'class'       => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_tokenization_secret'  => array(
				'title'       => __( 'Tokenization Secret key', $domain ),
				'type'        => 'text',
				'description' => '',
				'desc_tip'    => true,
				'default'     => '',
				'class'       => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'test_mode' => array(
				'title'       => __( 'Test mode', $domain ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 0,
				'description' => '',
				'options'     => $yes_or_no,
				'desc_tip'    => true,
			),
			'transaction_type' => array(
				'title'       => __( 'Transaction type', $domain ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 0,
				'description' => '',
				'options'     => $transaction_type,
				'desc_tip'    => true
			),
			'form_language' => array(
				'title'       => __( 'Form language', $domain ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 'EN',
				'description' => '',
				'options'     => $form_language,
				'desc_tip'    => true,
			),
			'paying_in_installments' => array(
				'title'       => __( 'Allow paying in installments', $domain ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select woocommerce-monri-dynamic-option monri-web-pay-option',
				'default'     => 0,
				'description' => '',
				'options'     => $yes_or_no,
				'desc_tip'    => true,
			),
			'number_of_allowed_installments' => array(
				'title'       => __( 'Number of allowed installments', $domain ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select woocommerce-monri-dynamic-option monri-web-pay-option',
				'default'     => 0,
				'description' => '',
				'options'     => $number_of_allowed_installments,
				'desc_tip'    => true,
			),
			'bottom_limit' => array(
				'title'       => __( 'Price limit for paying in installments:', $domain ),
				'type'        => 'text',
				'description' => __( 'This controls the bottom price limit on which the installments can be used.', $domain ),
				'desc_tip'    => true,
				'default' => '0',
				'class'       => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			)
		);

		for ( $i = 2; $i <= 24; $i ++ ) {
			$form_fields["price_increase_$i"] = array(
				'title'       => __( "Price increase when paying in $i installments", $domain ),
				'type'        => 'text',
				'description' => __( 'This controls the price increase when paying with installments.', $domain ),
				'desc_tip'    => true,
				'default'     => __( '0', $domain ),
				'class'       => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			);
		}

		return $form_fields;
	}

	/**
	 * @param $key
	 * @param mixed $default
	 *
	 * @return mixed|null
	 */
	public function get_option( $key, $default = null ) {
		$settings = get_option( self::SETTINGS_KEY, [] );

		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		if ( isset( $this->get_form_fields()[ $key ]['default'] ) ) {
			return $this->get_form_fields()[ $key ]['default'];
		}

		return $default;
	}

	/**
	 * @param $options
	 *
	 * @return bool
	 */
	public function update_options( $options ) {
		$settings = get_option( self::SETTINGS_KEY, [] );
		$settings = array_merge( $settings, $options );

		return update_option( self::SETTINGS_KEY, $settings );
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function get_option_bool( $key ) {
		return in_array( $this->get_option( $key ), array( 'yes', '1', true ), true );
	}
}
