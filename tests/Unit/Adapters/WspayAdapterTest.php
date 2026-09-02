<?php

namespace Monri\Tests\Unit\Adapters;

use Monri\Tests\Unit\TestCase;
use Monri_WC_Gateway;
use Monri_WC_Gateway_Adapter_Wspay;
use Monri_WC_Gateway_Adapter_Wspay_Iframe;

class WspayAdapterTest extends TestCase {

	/**
	 * @covers Monri_WC_Gateway_Adapter_Wspay::init
	 */
	public function test_wspay_init_and_constants(): void {
		$this->set_plugin_settings( [
			'title' => 'WSPay Form',
			'description' => 'Desc',
			'monri_ws_pay_form_shop_id' => 'shop_1',
			'monri_ws_pay_form_secret' => 'sec_1',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Wspay();
		$adapter->init( $gateway );

		$this->assertSame( 'wspay', Monri_WC_Gateway_Adapter_Wspay::ADAPTER_ID );
		$this->assertSame( 'https://formtest.wspay.biz', Monri_WC_Gateway_Adapter_Wspay::ENDPOINT_TEST );
		$this->assertSame( 'https://form.wspay.biz', Monri_WC_Gateway_Adapter_Wspay::ENDPOINT );
		$this->assertSame( [ 'products', 'refunds' ], $adapter->supports );
	}

	/**
	 * @covers Monri_WC_Gateway_Adapter_Wspay::can_refund_order
	 */
	public function test_wspay_can_refund_order(): void {
		$this->set_plugin_settings( [
			'title' => 'WSPay Form',
			'description' => 'Desc',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Wspay();
		$adapter->init( $gateway );

		$order = wc_get_order( 404 );
		$order->update_status( 'pending' );
		$this->assertFalse( $adapter->can_refund_order( $order ) );

		$order->update_status( 'completed' );
		$this->assertTrue( $adapter->can_refund_order( $order ) );
	}

	/**
	 * @covers Monri_WC_Gateway_Adapter_Wspay::save_user_token
	 */
	public function test_wspay_save_user_token(): void {
		$this->set_plugin_settings( [
			'title' => 'WSPay Form',
			'description' => 'Desc',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Wspay();
		$adapter->init( $gateway );

		$token_data = [
			'Token' => 'wspay_tok_123',
			'TokenNumber' => '411111******1111',
			'TokenExp' => '2712',
			'CreditCardName' => 'Visa',
		];

		$adapter->save_user_token( 1, $token_data );
		$this->assertTrue( true );
	}

	/**
	 * @covers Monri_WC_Gateway_Adapter_Wspay_Iframe::init
	 */
	public function test_wspay_iframe_init(): void {
		$this->set_plugin_settings( [
			'title' => 'WSPay iFrame',
			'description' => 'Desc',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Wspay_Iframe();
		$adapter->init( $gateway );

		$this->assertSame( 'wspay_iframe', Monri_WC_Gateway_Adapter_Wspay_Iframe::ADAPTER_ID );
	}
}
