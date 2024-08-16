<?php

class Monri_WC_Settings {

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
		$yes_or_no = array(
			'0' => 'No',
			'1' => 'Yes'
		);

		$integration_types = array(
			'form'       => __( 'Form', 'monri' ),
			'components' => __( 'Components (beta)', 'monri' )
		);

		$transaction_type = array(
			'0' => __( 'Purchase', 'monri' ),
			'1' => __( 'Authorize', 'monri' )
		);

		$number_of_allowed_installments = array(
			'36' => '36',
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
				'title'   => __( 'Enable/Disable', 'monri' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Monri', 'monri' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'monri' ),
				'type'        => 'text',
				'description' => __( 'Title which the customer sees during checkout.', 'monri' ),
				'desc_tip'    => true,
				'default'     => __( 'Monri', 'monri' ),
			),
			'description' => array(
				'title'       => __( 'Description', 'monri' ),
				'type'        => 'textarea',
				'description' => __( 'Description which the customer sees during checkout.', 'monri' ),
				'default'     => __( 'Pay quick and easy via Monri', 'monri' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'monri' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'monri' ),
				'default'     => __( 'Instructions for Monri.', 'monri' ),
				'desc_tip'    => true,
			),
			'monri_payment_gateway_service' => array(
				'title'       => __( 'Payment gateway service:', 'monri' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 'monri-web-pay',
				'options'     => $payment_gateway_services,
				'desc_tip'    => true,
			),
			'monri_web_pay_integration_type' => array(
				'title'       => __( 'Integration type', 'monri' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => true,
				'options'     => $integration_types,
				'desc_tip'    => true,
				'custom_attributes' => [
					'data-depends' => '{
						"monri_payment_gateway_service":"monri-web-pay"
					}'
				]
			),
			'monri_merchant_key' => array(
				'title'       => __( 'Key', 'monri' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'default'     => '',
				'custom_attributes' => [
					'data-depends' => '{
						"monri_payment_gateway_service":"monri-web-pay"
					}'
				]
			),
			'monri_authenticity_token' => array(
				'title'       => __( 'Authenticity token', 'monri' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'default'     => '',
				'custom_attributes' => [
					'data-depends' => '{
						"monri_payment_gateway_service":"monri-web-pay"
					}'
				]
			),
			'monri_ws_pay_form_shop_id' => array(
				'title'       => __( 'Shop ID', 'monri' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'default'     => '',
				'custom_attributes' => [
					'data-depends' => '{
						"monri_payment_gateway_service":"monri-ws-pay"
					}'
				]
			),
			'monri_ws_pay_form_secret' => array(
				'title'       => __( 'Secret key', 'monri' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'default'     => '',
				'custom_attributes' => [
					'data-depends' => '{
						"monri_payment_gateway_service":"monri-ws-pay"
					}'
				]
			),
			'monri_ws_pay_form_tokenization_enabled' => array(
				'title'       => __( 'Tokenization Enable/Disable', 'monri' ),
				'type'        => 'checkbox',
				'label'   => __( 'Enable Tokenization', 'monri' ),
				'desc_tip'    => true,
				'default'     => 'no',
				'custom_attributes' => [
					'data-depends' => '{
						"monri_payment_gateway_service":"monri-ws-pay"
					}'
				]
			),
			'monri_ws_pay_form_tokenization_shop_id' => array(
				'title'       => __( 'Tokenization Shop ID', 'monri' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'default'     => '',
				'custom_attributes' => [
					'data-depends' => '{
						"monri_ws_pay_form_tokenization_enabled":"1"
					}'
				]
			),
			'monri_ws_pay_form_tokenization_secret'  => array(
				'title'       => __( 'Tokenization Secret key', 'monri' ),
				'type'        => 'text',
				'description' => '',
				'desc_tip'    => true,
				'default'     => '',
				'custom_attributes' => [
					'data-depends' => '{
						"monri_ws_pay_form_tokenization_enabled":"1"
					}'
				]
			),
			'test_mode' => array(
				'title'       => __( 'Test mode', 'monri' ),
				'description' => __( 'Just test the gateway, no real orders will be placed on the gateway side.', 'monri' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 0,
				'options'     => $yes_or_no,
				'desc_tip'    => true,
			),
			'debug_mode' => array(
				'title'       => __( 'Debug mode', 'monri' ),
				'description' => __( 'Save detailed messages, error messages and API requests to the WooCommerce Status log.', 'monri' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 0,
				'options'     => $yes_or_no,
				'desc_tip'    => true,
			),
			'transaction_type' => array(
				'title'       => __( 'Transaction type', 'monri' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 0,
				'description' => '',
				'options'     => $transaction_type,
				'desc_tip'    => true
			),
			'form_language' => array(
				'title'       => __( 'Form language', 'monri' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 'EN',
				'description' => '',
				'options'     => $form_language,
				'desc_tip'    => true,
			),
			'paying_in_installments' => array(
				'title'       => __( 'Allow paying in installments', 'monri' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 0,
				'description' => '',
				'options'     => $yes_or_no,
				'desc_tip'    => true,
				'custom_attributes' => [
					'data-depends' => '{
						"monri_payment_gateway_service":"monri-web-pay"
					}'
				]
			),
			'number_of_allowed_installments' => array(
				'title'       => __( 'Number of allowed installments', 'monri' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => '12',
				'description' => '',
				'options'     => $number_of_allowed_installments,
				'desc_tip'    => true,
				'custom_attributes' => [
					'data-depends' => '{
						"monri_web_pay_integration_type":"form",
						"paying_in_installments":"1"
					}'
				]
			),
			'bottom_limit' => array(
				'title'       => __( 'Price limit for paying in installments', 'monri' ),
				'type'        => 'price',
				'description' => __( 'This controls the bottom price limit on which the installments can be used.', 'monri' ),
				'desc_tip'    => true,
				'default' => '',
				'custom_attributes' => [
					'data-depends' => '{
						"paying_in_installments":"1"
					}'
				]
			),
			'order_show_transaction_info' => array(
				'title'       => __( 'Transaction info in order', 'monri' ),
				'description' => __( 'Show Monri transaction info on Thank You page. Required by some banks.', 'monri' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => 0,
				'options'     => $yes_or_no,
				'desc_tip'    => true,
				'custom_attributes' => [
					'data-depends' => '{
						"monri_payment_gateway_service":"monri-ws-pay"
					}'
				]
			),
		);

		for ( $i = 2; $i <= 36; $i ++ ) {
			$form_fields["price_increase_$i"] = array(
				/* translators: %d: number of installments */
				'title'       => sprintf(__( 'Price increase when paying in %d installments', 'monri' ), $i),
				'type'        => 'decimal',
				'description' => __( 'This controls the price increase when paying with installments.', 'monri' ),
				'desc_tip'    => true,
				'default'     => __( '0', 'monri' ),
				'custom_attributes' => [
					'data-depends' => '{
						"monri_web_pay_integration_type":"form",
						"paying_in_installments":"1"
					}'
				]
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
