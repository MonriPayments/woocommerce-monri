<?php

namespace Monri\Tests\Unit;

use Monri_WC_Settings;

class SettingsTest extends TestCase {

	/**
	 * @covers Monri_WC_Settings::instance
	 */
	public function test_instance_returns_singleton(): void {
		$instance1 = Monri_WC_Settings::instance();
		$instance2 = Monri_WC_Settings::instance();

		$this->assertInstanceOf( Monri_WC_Settings::class, $instance1 );
		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * @covers Monri_WC_Settings::get_form_fields
	 */
	public function test_get_form_fields_returns_expected_fields(): void {
		$settings = Monri_WC_Settings::instance();
		$fields = $settings->get_form_fields();

		$this->assertIsArray( $fields );
		$this->assertArrayHasKey( 'enabled', $fields );
		$this->assertArrayHasKey( 'title', $fields );
		$this->assertArrayHasKey( 'monri_merchant_key', $fields );
		$this->assertArrayHasKey( 'monri_authenticity_token', $fields );
		$this->assertArrayHasKey( 'monri_payment_gateway_service', $fields );
		$this->assertArrayHasKey( 'monri_web_pay_integration_type', $fields );
		$this->assertArrayHasKey( 'paying_in_installments', $fields );
		$this->assertArrayHasKey( 'number_of_allowed_installments', $fields );

		// Check installment price increases (2..36)
		for ( $i = 2; $i <= 36; $i++ ) {
			$this->assertArrayHasKey( "price_increase_$i", $fields );
		}
	}

	/**
	 * @covers Monri_WC_Settings::get_option
	 */
	public function test_get_option_from_saved_settings(): void {
		$this->set_plugin_settings( [
			'title' => 'Custom Monri Title',
			'test_mode' => 'yes',
		] );

		$settings = Monri_WC_Settings::instance();
		$this->assertSame( 'Custom Monri Title', $settings->get_option( 'title' ) );
		$this->assertSame( 'yes', $settings->get_option( 'test_mode' ) );
	}

	/**
	 * @covers Monri_WC_Settings::get_option
	 */
	public function test_get_option_falls_back_to_form_field_default(): void {
		$this->set_plugin_settings( [] );

		$settings = Monri_WC_Settings::instance();
		$this->assertSame( 'Monri', $settings->get_option( 'title' ) );
		$this->assertSame( 'monri-web-pay', $settings->get_option( 'monri_payment_gateway_service' ) );
	}

	/**
	 * @covers Monri_WC_Settings::get_option
	 */
	public function test_get_option_falls_back_to_passed_default_when_not_in_form_fields(): void {
		$this->set_plugin_settings( [] );

		$settings = Monri_WC_Settings::instance();
		$this->assertSame( 'custom_fallback', $settings->get_option( 'non_existent_key', 'custom_fallback' ) );
	}

	/**
	 * @covers Monri_WC_Settings::get_option_bool
	 */
	public function test_get_option_bool_evaluates_correctly(): void {
		$settings = Monri_WC_Settings::instance();

		$this->set_plugin_settings( [ 'test_mode' => 'yes' ] );
		$this->assertTrue( $settings->get_option_bool( 'test_mode' ) );

		$this->set_plugin_settings( [ 'test_mode' => '1' ] );
		$this->assertTrue( $settings->get_option_bool( 'test_mode' ) );

		$this->set_plugin_settings( [ 'test_mode' => true ] );
		$this->assertTrue( $settings->get_option_bool( 'test_mode' ) );

		$this->set_plugin_settings( [ 'test_mode' => 'no' ] );
		$this->assertFalse( $settings->get_option_bool( 'test_mode' ) );

		$this->set_plugin_settings( [ 'test_mode' => '0' ] );
		$this->assertFalse( $settings->get_option_bool( 'test_mode' ) );

		$this->set_plugin_settings( [ 'test_mode' => false ] );
		$this->assertFalse( $settings->get_option_bool( 'test_mode' ) );
	}

	/**
	 * @covers Monri_WC_Settings::update_options
	 */
	public function test_update_options_merges_and_updates(): void {
		$this->set_plugin_settings( [
			'title' => 'Original Title',
			'test_mode' => 'no',
		] );

		$settings = Monri_WC_Settings::instance();
		$res = $settings->update_options( [
			'title' => 'New Title',
			'monri_merchant_key' => 'secret',
		] );

		$this->assertTrue( $res );

		$updated = $this->get_wp_option( Monri_WC_Settings::SETTINGS_KEY );
		$this->assertSame( 'New Title', $updated['title'] );
		$this->assertSame( 'no', $updated['test_mode'] );
		$this->assertSame( 'secret', $updated['monri_merchant_key'] );
	}

	/**
	 * @covers Monri_WC_Settings::modify_monri_sanitized_fields
	 */
	public function test_modify_monri_sanitized_fields_unsets_callback_url(): void {
		$settings = Monri_WC_Settings::instance();
		$input = [
			'title' => 'Monri',
			'monri_ws_pay_callback_url' => 'https://example.com/callback',
		];
		$output = $settings->modify_monri_sanitized_fields( $input );

		$this->assertArrayNotHasKey( 'monri_ws_pay_callback_url', $output );
		$this->assertSame( 'Monri', $output['title'] );
	}

	/**
	 * @covers Monri_WC_Settings::include_components_keks
	 * @covers Monri_WC_Settings::include_components_google_pay
	 * @covers Monri_WC_Settings::include_components_apple_pay
	 * @covers Monri_WC_Settings::include_components_pay_cek
	 */
	public function test_include_components_flags(): void {
		$settings = Monri_WC_Settings::instance();

		// Case 1: Service is WSPay (should all be false)
		$this->set_plugin_settings( [
			'monri_payment_gateway_service' => 'monri-ws-pay',
			'monri_web_pay_integration_type' => 'components',
			'monri_web_pay_supported_payment_methods' => [ 'keks-pay', 'google-pay', 'apple-pay', 'pay-cek' ],
		] );
		$this->assertFalse( $settings->include_components_keks() );
		$this->assertFalse( $settings->include_components_google_pay() );
		$this->assertFalse( $settings->include_components_apple_pay() );
		$this->assertFalse( $settings->include_components_pay_cek() );

		// Case 2: Service is WebPay, but type is form
		$this->set_plugin_settings( [
			'monri_payment_gateway_service' => 'monri-web-pay',
			'monri_web_pay_integration_type' => 'form',
			'monri_web_pay_supported_payment_methods' => [ 'keks-pay', 'google-pay', 'apple-pay', 'pay-cek' ],
		] );
		$this->assertFalse( $settings->include_components_keks() );
		$this->assertFalse( $settings->include_components_google_pay() );
		$this->assertFalse( $settings->include_components_apple_pay() );
		$this->assertFalse( $settings->include_components_pay_cek() );

		// Case 3: Service is WebPay, type is components, selected methods enabled
		$this->set_plugin_settings( [
			'monri_payment_gateway_service' => 'monri-web-pay',
			'monri_web_pay_integration_type' => 'components',
			'monri_web_pay_supported_payment_methods' => [ 'keks-pay-hr', 'apple-pay' ],
		] );
		$this->assertTrue( $settings->include_components_keks() );
		$this->assertTrue( $settings->include_components_apple_pay() );
		$this->assertFalse( $settings->include_components_google_pay() );
		$this->assertFalse( $settings->include_components_pay_cek() );
	}
}
