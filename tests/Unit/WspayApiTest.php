<?php

namespace Monri\Tests\Unit;

use Brain\Monkey\Functions;
use Monri_WSPay_WC_Api;
use WP_Error;

class WspayApiTest extends TestCase {

	/**
	 * @covers Monri_WSPay_WC_Api::instance
	 */
	public function test_instance_returns_singleton(): void {
		$instance1 = Monri_WSPay_WC_Api::instance();
		$instance2 = Monri_WSPay_WC_Api::instance();

		$this->assertInstanceOf( Monri_WSPay_WC_Api::class, $instance1 );
		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * @covers Monri_WSPay_WC_Api::__construct
	 */
	public function test_construct_sets_test_mode_from_settings(): void {
		$this->set_plugin_settings( [ 'test_mode' => 'yes' ] );
		$api_test = new Monri_WSPay_WC_Api();
		$this->assertTrue( $api_test->test_mode );

		$this->set_plugin_settings( [ 'test_mode' => 'no' ] );
		$api_live = new Monri_WSPay_WC_Api();
		$this->assertFalse( $api_live->test_mode );
	}

	/**
	 * @covers Monri_WSPay_WC_Api::capture
	 */
	public function test_capture_builds_correct_payload_and_signature_non_tokenization(): void {
		$this->set_plugin_settings( [
			'test_mode' => 'yes',
			'monri_ws_pay_form_shop_id' => 'shop_123',
			'monri_ws_pay_form_secret' => 'secret_abc',
		] );

		$captured_url = '';
		$captured_args = [];

		Functions\stubs( [
			'wp_remote_post' => function ( $url, $args ) use ( &$captured_url, &$captured_args ) {
				$captured_url = $url;
				$captured_args = $args;
				return [
					'response' => [ 'code' => 200 ],
					'body' => json_encode( [ 'ActionSuccess' => '1', 'ApprovalCode' => 'APP-01' ] ),
				];
			},
		] );

		$api = new Monri_WSPay_WC_Api();
		$response = $api->capture( 'STAN-1', 'APP-01', 'WS-ORD-100', 10000, false );

		$this->assertIsArray( $response );
		$this->assertSame( '1', $response['ActionSuccess'] );
		$this->assertSame( 'https://test.wspay.biz/api/services/completion', $captured_url );

		$sent_body = json_decode( $captured_args['body'], true );
		$this->assertSame( 'STAN-1', $sent_body['STAN'] );
		$this->assertSame( '2.0', $sent_body['Version'] );
		$this->assertSame( 'shop_123', $sent_body['ShopID'] );
		$this->assertSame( 10000, $sent_body['Amount'] );
		$this->assertSame( 'WS-ORD-100', $sent_body['WsPayOrderId'] );
		$this->assertSame( 'APP-01', $sent_body['ApprovalCode'] );

		// Verify signature calculation
		$expected_raw = 'shop_123WS-ORD-100secret_abcSTAN-1secret_abcAPP-01secret_abc10000secret_abcWS-ORD-100';
		$expected_sig = hash( 'sha512', $expected_raw );
		$this->assertSame( $expected_sig, $sent_body['Signature'] );
	}

	/**
	 * @covers Monri_WSPay_WC_Api::refund
	 */
	public function test_refund_builds_correct_payload_and_signature_tokenization(): void {
		$this->set_plugin_settings( [
			'test_mode' => 'no',
			'monri_ws_pay_form_tokenization_shop_id' => 'tok_shop_777',
			'monri_ws_pay_form_tokenization_secret' => 'tok_secret_999',
		] );

		$captured_url = '';
		$captured_args = [];

		Functions\stubs( [
			'wp_remote_post' => function ( $url, $args ) use ( &$captured_url, &$captured_args ) {
				$captured_url = $url;
				$captured_args = $args;
				return [
					'response' => [ 'code' => 200 ],
					'body' => json_encode( [ 'ActionSuccess' => '1' ] ),
				];
			},
		] );

		$api = new Monri_WSPay_WC_Api();
		$response = $api->refund( 'STAN-99', 'APP-99', 'WS-ORD-500', 5000, true );

		$this->assertIsArray( $response );
		$this->assertSame( 'https://secure.wspay.biz/api/services/refund', $captured_url );

		$sent_body = json_decode( $captured_args['body'], true );
		$this->assertSame( 'tok_shop_777', $sent_body['ShopID'] );

		$expected_raw = 'tok_shop_777WS-ORD-500tok_secret_999STAN-99tok_secret_999APP-99tok_secret_9995000tok_secret_999WS-ORD-500';
		$expected_sig = hash( 'sha512', $expected_raw );
		$this->assertSame( $expected_sig, $sent_body['Signature'] );
	}

	/**
	 * @covers Monri_WSPay_WC_Api::void
	 */
	public function test_void_posts_to_void_endpoint(): void {
		$this->set_plugin_settings( [
			'test_mode' => 'yes',
			'monri_ws_pay_form_shop_id' => 'shop_1',
			'monri_ws_pay_form_secret' => 'sec_1',
		] );

		$captured_url = '';
		Functions\stubs( [
			'wp_remote_post' => function ( $url, $args ) use ( &$captured_url ) {
				$captured_url = $url;
				return [
					'response' => [ 'code' => 200 ],
					'body' => json_encode( [ 'ActionSuccess' => '1' ] ),
				];
			},
		] );

		$api = new Monri_WSPay_WC_Api();
		$response = $api->void( 'STAN-1', 'APP-1', 'WS-ORD-1', 1000, false );

		$this->assertSame( 'https://test.wspay.biz/api/services/void', $captured_url );
		$this->assertSame( '1', $response['ActionSuccess'] );
	}

	/**
	 * @covers Monri_WSPay_WC_Api::request
	 */
	public function test_request_handles_error_returns_empty_array(): void {
		$this->set_plugin_settings( [ 'test_mode' => 'yes' ] );

		Functions\stubs( [
			'wp_remote_post' => function () {
				return new WP_Error( 'http_error', 'Connection failed' );
			},
		] );

		$api = new Monri_WSPay_WC_Api();
		$response = $api->refund( 'STAN-1', 'APP-1', 'WS-ORD-1', 1000, false );

		$this->assertSame( [], $response );
	}
}
