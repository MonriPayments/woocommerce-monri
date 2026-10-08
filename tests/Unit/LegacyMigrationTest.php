<?php

namespace Monri\Tests\Unit;

use Monri_WC_Settings;

class LegacyMigrationTest extends TestCase {

	public function test_migration_skipped_if_monri_settings_already_exist(): void {
		$this->set_plugin_settings( [ 'enabled' => 'yes' ] );
		$this->set_wp_option( 'woocommerce_pikpay_settings', [ 'enabled' => 'no', 'title' => 'Old' ] );

		monri_legacy_migrate();

		$current = $this->get_wp_option( Monri_WC_Settings::SETTINGS_KEY );
		$this->assertSame( 'yes', $current['enabled'] );
		$this->assertArrayNotHasKey( 'title', $current );
	}

	public function test_migration_skipped_if_no_old_pikpay_settings(): void {
		monri_legacy_migrate();

		$current = $this->get_wp_option( Monri_WC_Settings::SETTINGS_KEY );
		$this->assertFalse( $current );
	}

	public function test_migration_maps_fields_and_sets_form_integration(): void {
		$old_settings = [
			'enabled' => 'yes',
			'title' => 'PikPay Payments',
			'description' => 'Pay with PikPay',
			'instructions' => 'Follow steps',
			'pikpaykey' => 'pik_key_123',
			'pikpayauthtoken' => 'pik_token_456',
			'test_mode' => 'yes',
			'transaction_type' => '0',
			'form_language' => 'en',
			'paying_in_installments' => '1',
			'number_of_allowed_installments' => '12',
			'bottom_limit' => '100',
			'price_increase_2' => '2.5',
			'price_increase_12' => '10.0',
			'pickpay_methods' => false,
		];

		$this->set_wp_option( 'woocommerce_pikpay_settings', $old_settings );

		monri_legacy_migrate();

		$saved_value = $this->get_wp_option( Monri_WC_Settings::SETTINGS_KEY );

		$this->assertIsArray( $saved_value );
		$this->assertSame( 'yes', $saved_value['enabled'] );
		$this->assertSame( 'pik_key_123', $saved_value['monri_merchant_key'] );
		$this->assertSame( 'pik_token_456', $saved_value['monri_authenticity_token'] );
		$this->assertSame( '2.5', $saved_value['price_increase_2'] );
		$this->assertSame( '10.0', $saved_value['price_increase_12'] );
		$this->assertSame( 'monri-web-pay', $saved_value['monri_payment_gateway_service'] );
		$this->assertSame( 'form', $saved_value['monri_web_pay_integration_type'] );
	}

	public function test_migration_sets_components_integration_when_pickpay_methods_truthy(): void {
		$old_settings = [
			'enabled' => 'yes',
			'pikpaykey' => 'key',
			'pikpayauthtoken' => 'token',
			'pickpay_methods' => true,
		];

		$this->set_wp_option( 'woocommerce_pikpay_settings', $old_settings );

		monri_legacy_migrate();

		$saved_value = $this->get_wp_option( Monri_WC_Settings::SETTINGS_KEY );

		$this->assertSame( 'components', $saved_value['monri_web_pay_integration_type'] );
		$this->assertSame( 'monri-web-pay', $saved_value['monri_payment_gateway_service'] );
	}
}
