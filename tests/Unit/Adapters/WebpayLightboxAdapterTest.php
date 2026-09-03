<?php

namespace Monri\Tests\Unit\Adapters;

use Monri\Tests\Unit\TestCase;
use Monri_WC_Gateway;
use Monri_WC_Gateway_Adapter_Webpay_Lightbox;

class WebpayLightboxAdapterTest extends TestCase {

	/**
	 * @covers Monri_WC_Gateway_Adapter_Webpay_Lightbox::init
	 */
	public function test_init_and_constants(): void {
		$this->set_plugin_settings( [
			'title' => 'Lightbox',
			'description' => 'Desc',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Webpay_Lightbox();
		$adapter->init( $gateway );

		$this->assertSame( 'webpay_lightbox', Monri_WC_Gateway_Adapter_Webpay_Lightbox::ADAPTER_ID );
		$this->assertSame( 'https://ipgtest.monri.com/dist/lightbox.js', Monri_WC_Gateway_Adapter_Webpay_Lightbox::ENDPOINT_TEST );
		$this->assertSame( 'https://ipg.monri.com/dist/lightbox.js', Monri_WC_Gateway_Adapter_Webpay_Lightbox::ENDPOINT );
		$this->assertSame( [ 'products', 'refunds' ], $adapter->supports );
	}

	/**
	 * @covers Monri_WC_Gateway_Adapter_Webpay_Lightbox::process_payment
	 */
	public function test_process_payment_returns_lightbox_config(): void {
		$this->set_plugin_settings( [
			'title' => 'Lightbox',
			'description' => 'Desc',
			'monri_merchant_key' => 'm_key',
			'monri_authenticity_token' => 'auth_token',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = new Monri_WC_Gateway_Adapter_Webpay_Lightbox();
		$adapter->init( $gateway );

		$result = $adapter->process_payment( 102 );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'auth_token', $result['data-authenticity-token'] );
		$this->assertSame( '102', (string) $result['data-order-number'] );
	}
}
