<?php

namespace Monri\Tests\Unit;

use Brain\Monkey\Actions;
use Monri_WC_Callback;
use ReflectionException;
use ReflectionMethod;

class CallbackTest extends TestCase {

	/**
	 * @covers Monri_WC_Callback::init
	 */
	public function test_init_registers_callback_action(): void {
		Actions\expectAdded( 'woocommerce_api_monri_callback' );

		$callback = new Monri_WC_Callback();
		$callback->init();

		$this->assertTrue( has_action( 'woocommerce_api_monri_callback' ) );
	}

	/**
	 * @covers Monri_WC_Callback::validate_monri_wspay_callback
	 * @throws ReflectionException
	 */
	public function test_validate_monri_wspay_callback_non_tokenization(): void {
		$this->set_plugin_settings( [
			'monri_ws_pay_form_shop_id' => 'shop_123',
			'monri_ws_pay_form_secret' => 'secret_xyz',
			'monri_ws_pay_form_tokenization_shop_id' => 'tok_shop',
			'monri_ws_pay_form_tokenization_secret' => 'tok_sec',
		] );

		$payload = [
			'ShopID' => 'shop_123',
			'ActionSuccess' => '1',
			'ApprovalCode' => '123456',
			'WsPayOrderId' => 'ORD-100',
		];

		// Signature formula: $shop_id.$secret_key.$action_success.$approval_code.$secret_key.$shop_id.$approval_code.$wspay_order_id
		$raw_sig = 'shop_123' . 'secret_xyz' . '1' . '123456' . 'secret_xyz' . 'shop_123' . '123456' . 'ORD-100';
		$payload['Signature'] = hash( 'sha512', $raw_sig );

		$callback = new Monri_WC_Callback();
		$ref = new ReflectionMethod( Monri_WC_Callback::class, 'validate_monri_wspay_callback' );
		if ( PHP_VERSION_ID < 80100 ) {
			$ref->setAccessible( true );
		}

		$this->assertTrue( $ref->invoke( $callback, $payload ) );

		// Invalid signature
		$payload['Signature'] = 'invalid_signature_hash';
		$this->assertFalse( $ref->invoke( $callback, $payload ) );
	}

	/**
	 * @covers Monri_WC_Callback::validate_monri_wspay_callback
	 * @throws ReflectionException
	 */
	public function test_validate_monri_wspay_callback_tokenization(): void {
		$this->set_plugin_settings( [
			'monri_ws_pay_form_shop_id' => 'shop_123',
			'monri_ws_pay_form_secret' => 'secret_xyz',
			'monri_ws_pay_form_tokenization_shop_id' => 'tok_shop_777',
			'monri_ws_pay_form_tokenization_secret' => 'tok_sec_999',
		] );

		$payload = [
			'ShopID' => 'tok_shop_777',
			'ActionSuccess' => '1',
			'ApprovalCode' => '654321',
			'WsPayOrderId' => 'ORD-999',
		];

		$raw_sig = 'tok_shop_777' . 'tok_sec_999' . '1' . '654321' . 'tok_sec_999' . 'tok_shop_777' . '654321' . 'ORD-999';
		$payload['Signature'] = hash( 'sha512', $raw_sig );

		$callback = new Monri_WC_Callback();
		$ref = new ReflectionMethod( Monri_WC_Callback::class, 'validate_monri_wspay_callback' );
		if ( PHP_VERSION_ID < 80100 ) {
			$ref->setAccessible( true );
		}

		$this->assertTrue( $ref->invoke( $callback, $payload ) );
	}

	/**
	 * @covers Monri_WC_Callback::get_monri_wspay_callback_action
	 * @throws ReflectionException
	 */
	public function test_get_monri_wspay_callback_action(): void {
		$callback = new Monri_WC_Callback();
		$ref = new ReflectionMethod( Monri_WC_Callback::class, 'get_monri_wspay_callback_action' );
		if ( PHP_VERSION_ID < 80100 ) {
			$ref->setAccessible( true );
		}

		$this->assertSame( 'Refunded', $ref->invoke( $callback, [ 'Refunded' => '1', 'Completed' => '1' ] ) );
		$this->assertSame( 'Voided', $ref->invoke( $callback, [ 'Voided' => '1', 'Completed' => '1' ] ) );
		$this->assertSame( 'Completed', $ref->invoke( $callback, [ 'Completed' => '1' ] ) );
		$this->assertSame( 'Authorized', $ref->invoke( $callback, [ 'Authorized' => '1' ] ) );
		$this->assertSame( 'Unknown', $ref->invoke( $callback, [ 'OtherField' => '1' ] ) );
		$this->assertSame( 'Unknown', $ref->invoke( $callback, [] ) );
	}

	/**
	 * @covers Monri_WC_Callback::get_monri_wspay_transaction_data
	 * @throws ReflectionException
	 */
	public function test_get_monri_wspay_transaction_data(): void {
		$callback = new Monri_WC_Callback();
		$ref = new ReflectionMethod( Monri_WC_Callback::class, 'get_monri_wspay_transaction_data' );
		if ( PHP_VERSION_ID < 80100 ) {
			$ref->setAccessible( true );
		}

		$payload = [
			'WsPayOrderId' => '12345678',
			'ApprovalCode' => 'APP987',
			'PaymentPlan' => '0200',
			'STAN' => '112233',
			'Amount' => '500,00',
			'ExtraUnusedKey' => 'Ignored',
		];

		$result = $ref->invoke( $callback, $payload );

		$this->assertSame( [
			'WsPayOrderId' => '12345678',
			'ApprovalCode' => 'APP987',
			'PaymentPlan' => '0200',
			'STAN' => '112233',
			'Amount' => '500,00',
		], $result );
	}

	public function test_webpay_callback_digest_computation(): void {
		$merchant_key = 'super_secret_merchant_key';
		$json_payload = json_encode( [
			'order_number' => '1001-test',
			'status' => 'approved',
			'response_code' => '0000',
			'amount' => 1500,
		] );

		$expected_digest = hash( 'sha512', $merchant_key . $json_payload );
		$auth_header = 'WP3-callback ' . $expected_digest;

		$stripped_auth = trim( str_replace( 'WP3-callback', '', $auth_header ) );
		$this->assertTrue( hash_equals( $expected_digest, $stripped_auth ) );
	}
}
