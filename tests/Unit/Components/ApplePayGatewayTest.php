<?php

namespace Monri\Tests\Unit\Components;

use Exception;
use Monri\Tests\Unit\TestCase;
use Monri_WC_Gateway_Webpay_Components_Apple_Pay;

class ApplePayGatewayTest extends TestCase {

	/**
	 * @covers Monri_WC_Gateway_Webpay_Components_Apple_Pay::__construct
	 */
	public function test_initialization(): void {
		$this->set_plugin_settings( [
			'title' => 'Apple Pay',
			'description' => 'Pay with Apple Pay',
			'test_mode' => 'yes',
		] );

		$gateway = new Monri_WC_Gateway_Webpay_Components_Apple_Pay();

		$this->assertSame( 'monri_components_apple_pay', $gateway->id );
		$this->assertSame( [ 'products' ], $gateway->supports );
	}

	/**
	 * @covers Monri_WC_Gateway_Webpay_Components_Apple_Pay::process_payment
	 * @throws Exception
	 */
	public function test_process_payment_returns_redirect(): void {
		$this->set_plugin_settings( [
			'title' => 'Apple Pay',
			'description' => 'Pay with Apple Pay',
			'test_mode' => 'yes',
		] );

		$gateway = new Monri_WC_Gateway_Webpay_Components_Apple_Pay();

		$result = $gateway->process_payment( 501 );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'https://example.com/checkout/pay/501', $result['redirect'] );
	}
}
