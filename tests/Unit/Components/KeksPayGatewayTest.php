<?php

namespace Monri\Tests\Unit\Components;

use Monri\Tests\Unit\TestCase;
use Monri_WC_Gateway_Webpay_Components_Keks_Pay;

class KeksPayGatewayTest extends TestCase {

	/**
	 * @covers Monri_WC_Gateway_Webpay_Components_Keks_Pay::__construct
	 */
	public function test_initialization(): void {
		$this->set_plugin_settings( [
			'title' => 'KEKS Pay',
			'description' => 'Pay with KEKS Pay',
			'test_mode' => 'yes',
		] );

		$gateway = new Monri_WC_Gateway_Webpay_Components_Keks_Pay();

		$this->assertSame( 'monri_components_keks_pay', $gateway->id );
		$this->assertSame( [ 'products' ], $gateway->supports );
	}

	/**
	 * @covers Monri_WC_Gateway_Webpay_Components_Keks_Pay::process_payment
	 */
	public function test_process_payment_returns_redirect(): void {
		$this->set_plugin_settings( [
			'title' => 'KEKS Pay',
			'description' => 'Pay with KEKS Pay',
			'test_mode' => 'yes',
		] );

		$gateway = new Monri_WC_Gateway_Webpay_Components_Keks_Pay();
		wc_get_order( 503 );

		$result = $gateway->process_payment( 503 );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'https://example.com/checkout/pay/503', $result['redirect'] );
	}
}
