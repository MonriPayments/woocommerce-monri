<?php

namespace Monri\Tests\Unit\Blocks;

use Monri\Tests\Unit\TestCase;
use Monri_WC_Blocks_Support;
use Monri_WC_Components_Apple_Pay_Blocks_Support;
use Monri_WC_Components_Google_Pay_Blocks_Support;
use Monri_WC_Components_Keks_Pay_Blocks_Support;
use Monri_WC_Components_Pay_Cek_Blocks_Support;

class BlocksSupportTest extends TestCase {

	/**
	 * @covers Monri_WC_Blocks_Support::initialize
	 * @covers Monri_WC_Blocks_Support::get_name
	 * @covers Monri_WC_Blocks_Support::is_active
	 */
	public function test_blocks_support_initialization_and_active(): void {
		$this->set_plugin_settings( [
			'enabled' => 'yes',
			'title' => 'Monri Blocks Title',
			'description' => 'Blocks Desc',
			'monri_payment_gateway_service' => 'monri-web-pay',
			'monri_web_pay_integration_type' => 'form',
		] );

		$blocks = new Monri_WC_Blocks_Support();
		$blocks->initialize();

		$this->assertSame( 'monri', $blocks->get_name() );
		$this->assertTrue( $blocks->is_active() );
	}

	/**
	 * @covers Monri_WC_Blocks_Support::get_payment_method_data
	 */
	public function test_get_payment_method_data_webpay_form(): void {
		$this->set_plugin_settings( [
			'enabled' => 'yes',
			'title' => 'Monri WebPay',
			'description' => 'Pay securely',
			'monri_payment_gateway_service' => 'monri-web-pay',
			'monri_web_pay_integration_type' => 'form',
			'paying_in_installments' => '1',
			'number_of_allowed_installments' => '12',
		] );

		$blocks = new Monri_WC_Blocks_Support();
		$blocks->initialize();

		$data = $blocks->get_payment_method_data();

		$this->assertSame( 'Monri WebPay', $data['title'] );
		$this->assertSame( 'Pay securely', $data['description'] );
		$this->assertSame( 'monri-web-pay', $data['service'] );
		$this->assertSame( 'form', $data['integration_type'] );
		$this->assertSame( '12', $data['installments'] );
		$this->assertFalse( $data['tokenization'] );
	}

	/**
	 * @covers Monri_WC_Blocks_Support::get_payment_method_data
	 */
	public function test_get_payment_method_data_webpay_components(): void {
		$this->set_plugin_settings( [
			'enabled' => 'yes',
			'title' => 'Monri Components',
			'description' => 'Pay via Components',
			'monri_payment_gateway_service' => 'monri-web-pay',
			'monri_web_pay_integration_type' => 'components',
			'monri_web_pay_components_order_creation' => 'before_payment',
			'paying_in_installments' => '1',
			'monri_web_pay_tokenization_enabled' => 'yes',
		] );

		$blocks = new Monri_WC_Blocks_Support();
		$blocks->initialize();

		$data = $blocks->get_payment_method_data();

		$this->assertSame( 'monri-web-pay', $data['service'] );
		$this->assertSame( 'components', $data['integration_type'] );
		$this->assertSame( 'before_payment', $data['order_creation'] );
		$this->assertTrue( $data['installments'] );
		$this->assertTrue( $data['tokenization'] );
	}

	/**
	 * @covers Monri_WC_Components_Apple_Pay_Blocks_Support
	 * @covers Monri_WC_Components_Google_Pay_Blocks_Support
	 * @covers Monri_WC_Components_Keks_Pay_Blocks_Support
	 * @covers Monri_WC_Components_Pay_Cek_Blocks_Support
	 */
	public function test_component_blocks_support_classes(): void {
		$this->set_plugin_settings( [
			'enabled' => 'yes',
			'title' => 'Monri',
			'description' => 'Desc',
			'monri_payment_gateway_service' => 'monri-web-pay',
			'monri_web_pay_integration_type' => 'components',
			'monri_web_pay_supported_payment_methods' => [ 'apple-pay', 'google-pay', 'keks-pay-hr', 'pay-cek' ],
		] );

		$apple = new Monri_WC_Components_Apple_Pay_Blocks_Support();
		$apple->initialize();
		$this->assertSame( 'monri_components_apple_pay', $apple->get_name() );
		$this->assertTrue( $apple->is_active() );

		$google = new Monri_WC_Components_Google_Pay_Blocks_Support();
		$google->initialize();
		$this->assertSame( 'monri_components_google_pay', $google->get_name() );
		$this->assertTrue( $google->is_active() );

		$keks = new Monri_WC_Components_Keks_Pay_Blocks_Support();
		$keks->initialize();
		$this->assertSame( 'monri_components_keks_pay', $keks->get_name() );
		$this->assertTrue( $keks->is_active() );

		$pay_cek = new Monri_WC_Components_Pay_Cek_Blocks_Support();
		$pay_cek->initialize();
		$this->assertSame( 'monri_components_pay_cek', $pay_cek->get_name() );
		$this->assertTrue( $pay_cek->is_active() );
	}
}
