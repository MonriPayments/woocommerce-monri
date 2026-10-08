<?php

namespace Monri\Tests\Unit\Components;

use Monri\Tests\Unit\TestCase;
use Monri_WC_Gateway_Webpay_Components_Pay_Cek;

class PayCekGatewayTest extends TestCase {

	/**
	 * @covers Monri_WC_Gateway_Webpay_Components_Pay_Cek::__construct
	 */
	public function test_initialization(): void {
		$this->set_plugin_settings( [
			'title' => 'PayCek',
			'description' => 'Pay with Crypto PayCek',
			'test_mode' => 'yes',
		] );

		$gateway = new Monri_WC_Gateway_Webpay_Components_Pay_Cek();

		$this->assertSame( 'monri_components_pay_cek', $gateway->id );
		$this->assertSame( [ 'products', 'refunds' ], $gateway->supports );
	}

	/**
	 * @covers Monri_WC_Gateway_Webpay_Components_Pay_Cek::process_payment
	 */
	public function test_process_payment_returns_redirect(): void {
		$this->set_plugin_settings( [
			'title' => 'PayCek',
			'description' => 'Pay with Crypto PayCek',
			'test_mode' => 'yes',
		] );

		$gateway = new Monri_WC_Gateway_Webpay_Components_Pay_Cek();
		wc_get_order( 504 );

		$result = $gateway->process_payment( 504 );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'https://example.com/checkout/pay/504', $result['redirect'] );
	}
}
