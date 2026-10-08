<?php

namespace Monri\Tests\Unit\Components;

use Exception;
use Monri\Tests\Unit\TestCase;
use Monri_WC_Gateway_Webpay_Components_Google_Pay;

class GooglePayGatewayTest extends TestCase {

	/**
	 * @covers Monri_WC_Gateway_Webpay_Components_Google_Pay::__construct
	 */
	public function test_initialization(): void {
		$this->set_plugin_settings( [
			'title' => 'Google Pay',
			'description' => 'Pay with Google Pay',
			'test_mode' => 'yes',
		] );

		$gateway = new Monri_WC_Gateway_Webpay_Components_Google_Pay();

		$this->assertSame( 'monri_components_google_pay', $gateway->id );
		$this->assertSame( [ 'products' ], $gateway->supports );
	}

	/**
	 * @covers Monri_WC_Gateway_Webpay_Components_Google_Pay::process_payment
	 * @throws Exception
	 */
	public function test_process_payment_returns_redirect(): void {
		$this->set_plugin_settings( [
			'title' => 'Google Pay',
			'description' => 'Pay with Google Pay',
			'test_mode' => 'yes',
		] );

		$result = ( new Monri_WC_Gateway_Webpay_Components_Google_Pay() )->process_payment( 502 );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'https://example.com/checkout/pay/502', $result['redirect'] );
	}
}
