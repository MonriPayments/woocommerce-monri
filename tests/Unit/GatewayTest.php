<?php

namespace Monri\Tests\Unit;

use Monri_WC_Gateway;
use Monri_WC_Gateway_Adapter_Webpay_Components;
use Monri_WC_Gateway_Adapter_Webpay_Components_New;
use Monri_WC_Gateway_Adapter_Webpay_Form;
use Monri_WC_Gateway_Adapter_Webpay_Lightbox;
use Monri_WC_Gateway_Adapter_Wspay;
use Monri_WC_Gateway_Adapter_Wspay_Iframe;
use ReflectionProperty;

class GatewayTest extends TestCase {

	/**
	 * Helper to get private adapter property from gateway.
	 */
	private function get_gateway_adapter( Monri_WC_Gateway $gateway ) {
		$ref = new ReflectionProperty( Monri_WC_Gateway::class, 'adapter' );
		if ( PHP_VERSION_ID < 80100 ) {
			$ref->setAccessible( true );
		}
		return $ref->getValue( $gateway );
	}

	/**
	 * @covers Monri_WC_Gateway::__construct
	 */
	public function test_resolves_wspay_form_adapter(): void {
		$this->set_plugin_settings( [
			'title' => 'Monri WSPay',
			'description' => 'Pay via WSPay',
			'monri_payment_gateway_service' => 'monri-ws-pay',
			'monri_ws_pay_integration_type' => 'form',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = $this->get_gateway_adapter( $gateway );

		$this->assertInstanceOf( Monri_WC_Gateway_Adapter_Wspay::class, $adapter );
		$this->assertSame( 'Monri WSPay', $gateway->title );
	}

	/**
	 * @covers Monri_WC_Gateway::__construct
	 */
	public function test_resolves_wspay_iframe_adapter(): void {
		$this->set_plugin_settings( [
			'title' => 'Monri WSPay iFrame',
			'description' => 'Pay via WSPay iFrame',
			'monri_payment_gateway_service' => 'monri-ws-pay',
			'monri_ws_pay_integration_type' => 'iframe',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = $this->get_gateway_adapter( $gateway );

		$this->assertInstanceOf( Monri_WC_Gateway_Adapter_Wspay_Iframe::class, $adapter );
	}

	/**
	 * @covers Monri_WC_Gateway::__construct
	 */
	public function test_resolves_webpay_components_adapter(): void {
		$this->set_plugin_settings( [
			'title' => 'Monri Components',
			'description' => 'Pay via Components',
			'monri_payment_gateway_service' => 'monri-web-pay',
			'monri_web_pay_integration_type' => 'components',
			'monri_web_pay_components_order_creation' => 'after_payment',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = $this->get_gateway_adapter( $gateway );

		$this->assertInstanceOf( Monri_WC_Gateway_Adapter_Webpay_Components::class, $adapter );
	}

	/**
	 * @covers Monri_WC_Gateway::__construct
	 */
	public function test_resolves_webpay_components_new_adapter(): void {
		$this->set_plugin_settings( [
			'title' => 'Monri Components New',
			'description' => 'Pay via Components New',
			'monri_payment_gateway_service' => 'monri-web-pay',
			'monri_web_pay_integration_type' => 'components',
			'monri_web_pay_components_order_creation' => 'before_payment',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = $this->get_gateway_adapter( $gateway );

		$this->assertInstanceOf( Monri_WC_Gateway_Adapter_Webpay_Components_New::class, $adapter );
	}

	/**
	 * @covers Monri_WC_Gateway::__construct
	 */
	public function test_resolves_webpay_lightbox_adapter(): void {
		$this->set_plugin_settings( [
			'title' => 'Monri Lightbox',
			'description' => 'Pay via Lightbox',
			'monri_payment_gateway_service' => 'monri-web-pay',
			'monri_web_pay_integration_type' => 'lightbox',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = $this->get_gateway_adapter( $gateway );

		$this->assertInstanceOf( Monri_WC_Gateway_Adapter_Webpay_Lightbox::class, $adapter );
	}

	/**
	 * @covers Monri_WC_Gateway::__construct
	 */
	public function test_resolves_webpay_form_adapter_default(): void {
		$this->set_plugin_settings( [
			'title' => 'Monri Form Default',
			'description' => 'Pay via WebPay Form',
			'monri_payment_gateway_service' => 'monri-web-pay',
			'monri_web_pay_integration_type' => 'form',
		] );

		$gateway = new Monri_WC_Gateway();
		$adapter = $this->get_gateway_adapter( $gateway );

		$this->assertInstanceOf( Monri_WC_Gateway_Adapter_Webpay_Form::class, $adapter );
	}

	/**
	 * @covers Monri_WC_Gateway::get_option_bool
	 */
	public function test_get_option_bool(): void {
		$this->set_plugin_settings( [
			'description' => 'Pay description',
			'test_mode' => 'yes',
			'paying_in_installments' => '0',
		] );

		$gateway = new Monri_WC_Gateway();

		$this->assertTrue( $gateway->get_option_bool( 'test_mode' ) );
		$this->assertFalse( $gateway->get_option_bool( 'paying_in_installments' ) );
	}
}
