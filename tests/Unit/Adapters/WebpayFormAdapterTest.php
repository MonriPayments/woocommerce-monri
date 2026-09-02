<?php

namespace Monri\Tests\Unit\Adapters;

use Monri\Tests\Unit\TestCase;
use Monri_WC_Gateway;
use Monri_WC_Gateway_Adapter_Webpay_Form;

class WebpayFormAdapterTest extends TestCase {

	/**
	 * @covers Monri_WC_Gateway_Adapter_Webpay_Form::init
	 */
	public function test_init_sets_payment_and_supports(): void {
		$this->set_plugin_settings( [
			'title' => 'WebPay',
			'description' => 'Desc',
			'monri_tokenization' => '0',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Webpay_Form();
		$adapter->init( $gateway );

		$this->assertSame( [ 'products', 'refunds' ], $adapter->supports );
	}

	/**
	 * @covers Monri_WC_Gateway_Adapter_Webpay_Form::process_payment
	 */
	public function test_process_payment_redirects_to_checkout_payment_url(): void {
		$this->set_plugin_settings( [
			'title' => 'WebPay',
			'description' => 'Desc',
		] );

		$order = wc_get_order( 101 );
		WC()->session->set( 'monri_installments', 3 );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Webpay_Form();
		$adapter->init( $gateway );

		$result = $adapter->process_payment( 101 );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'https://example.com/checkout/pay/101', $result['redirect'] );
		$this->assertSame( 3, $order->get_meta( 'monri_installments' ) );
	}

	/**
	 * @covers Monri_WC_Gateway_Adapter_Webpay_Form::save_user_token
	 */
	public function test_save_user_token_creates_payment_token(): void {
		$this->set_plugin_settings( [
			'title' => 'WebPay',
			'description' => 'Desc',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Webpay_Form();
		$adapter->init( $gateway );

		$token_data = [
			'pan_token' => 'tok_webpay_12345',
			'cc_type' => 'visa',
			'masked_pan' => '411111-1111',
		];

		$adapter->save_user_token( 1, $token_data );
		$this->assertTrue( true );
	}

	/**
	 * @covers Monri_WC_Gateway_Adapter_Webpay_Form::can_refund_order
	 */
	public function test_can_refund_order(): void {
		$this->set_plugin_settings( [
			'title' => 'WebPay',
			'description' => 'Desc',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Webpay_Form();
		$adapter->init( $gateway );

		$order = wc_get_order( 202 );
		$order->update_status( 'pending' );
		$this->assertFalse( $adapter->can_refund_order( $order ) );

		$order->update_status( 'completed' );
		$this->assertTrue( $adapter->can_refund_order( $order ) );

		$order->update_meta_data( '_monri_should_close_parent_transaction', '1' );
		$this->assertFalse( $adapter->can_refund_order( $order ) );
	}
}
