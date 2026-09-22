<?php

namespace Monri\Tests\Integration;

use Monri_WC_Gateway;
use Monri_WC_Settings;

class GatewayLifecycleTest extends TestCase {

	/**
	 * Test that Monri gateway is registered in WooCommerce gateways filter.
	 */
	public function test_gateway_registered_in_filter(): void {
		$gateways = apply_filters( 'woocommerce_payment_gateways', [] );
		$this->assertContains( Monri_WC_Gateway::class, $gateways );
	}

	/**
	 * Test that settings option is persisted in the database.
	 */
	public function test_settings_persistence_in_db(): void {
		$test_settings = [
			'enabled' => 'yes',
			'title' => 'Monri Integration DB Test',
			'monri_merchant_key' => 'db_key_123',
			'test_mode' => 'yes',
		];

		update_option( Monri_WC_Settings::SETTINGS_KEY, $test_settings );

		$retrieved = get_option( Monri_WC_Settings::SETTINGS_KEY );
		$this->assertIsArray( $retrieved );
		$this->assertSame( 'yes', $retrieved['enabled'] );
		$this->assertSame( 'Monri Integration DB Test', $retrieved['title'] );
		$this->assertSame( 'db_key_123', $retrieved['monri_merchant_key'] );
	}

	/**
	 * Test action links filter contains settings URL.
	 */
	public function test_action_links(): void {
		$plugin_file = plugin_basename( MONRI_WC_PLUGIN_INDEX );
		$links = apply_filters( 'plugin_action_links_' . $plugin_file, [] );

		$this->assertNotEmpty( $links );
		$this->assertStringContainsString(
			'admin.php?page=wc-settings&amp;tab=checkout&amp;section=monri',
			htmlspecialchars( $links[0] )
		);
	}
}
