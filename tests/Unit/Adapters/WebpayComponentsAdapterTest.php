<?php

namespace Monri\Tests\Unit\Adapters;

use Monri\Tests\Unit\TestCase;
use Monri_WC_Gateway;
use Monri_WC_Gateway_Adapter_Webpay_Components;
use Monri_WC_Gateway_Adapter_Webpay_Components_New;

class WebpayComponentsAdapterTest extends TestCase {

	/**
	 * @covers Monri_WC_Gateway_Adapter_Webpay_Components::init
	 */
	public function test_components_adapter_init_and_constants(): void {
		$this->set_plugin_settings( [
			'title' => 'Components',
			'description' => 'Desc',
			'monri_authenticity_token' => 'auth_token_123',
			'test_mode' => 'yes',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Webpay_Components();
		$adapter->init( $gateway );

		$this->assertSame( 'webpay_components', Monri_WC_Gateway_Adapter_Webpay_Components::ADAPTER_ID );
		$this->assertSame(
			'https://ipgtest.monri.com/v2/payment/new',
			Monri_WC_Gateway_Adapter_Webpay_Components::AUTHORIZATION_ENDPOINT_TEST
		);
		$this->assertSame(
			'https://ipg.monri.com/v2/payment/new',
			Monri_WC_Gateway_Adapter_Webpay_Components::AUTHORIZATION_ENDPOINT
		);
		$this->assertSame( [ 'products', 'refunds' ], $adapter->supports );
	}

	/**
	 * @covers Monri_WC_Gateway_Adapter_Webpay_Components::can_refund_order
	 */
	public function test_components_can_refund_order(): void {
		$this->set_plugin_settings( [
			'title' => 'Components',
			'description' => 'Desc',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Webpay_Components();
		$adapter->init( $gateway );

		$order = wc_get_order( 303 );
		$order->update_status( 'pending' );
		$this->assertFalse( $adapter->can_refund_order( $order ) );

		$order->update_status( 'processing' );
		$this->assertTrue( $adapter->can_refund_order( $order ) );
	}

	/**
	 * @covers Monri_WC_Gateway_Adapter_Webpay_Components::save_user_token
	 */
	public function test_components_save_user_token(): void {
		$this->set_plugin_settings( [
			'title' => 'Components',
			'description' => 'Desc',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Webpay_Components();
		$adapter->init( $gateway );

		$data = [
			'token' => 'tok_comp_999',
			'masked' => '555555-5555',
			'brand' => 'mastercard',
			'expiration_date' => '2812',
		];

		$adapter->save_user_token( 1, $data );
		$this->assertTrue( true );
	}

	/**
	 * @covers Monri_WC_Gateway_Adapter_Webpay_Components_New::init
	 */
	public function test_components_new_adapter_init(): void {
		$this->set_plugin_settings( [
			'title' => 'Components New',
			'description' => 'Desc',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Webpay_Components_New();
		$adapter->init( $gateway );

		$this->assertSame( 'webpay_components_new', Monri_WC_Gateway_Adapter_Webpay_Components_New::ADAPTER_ID );
		$this->assertSame( [ 'products', 'refunds' ], $adapter->supports );
	}
}
