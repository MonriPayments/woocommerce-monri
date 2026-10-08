<?php

namespace Monri\Tests\Unit;

use Brain\Monkey\Functions;
use Monri_WC_Api;
use SimpleXMLElement;
use WP_Error;

class ApiTest extends TestCase {

	/**
	 * @covers Monri_WC_Api::instance
	 */
	public function test_instance_returns_singleton(): void {
		$instance1 = Monri_WC_Api::instance();
		$instance2 = Monri_WC_Api::instance();

		$this->assertInstanceOf( Monri_WC_Api::class, $instance1 );
		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * @covers Monri_WC_Api::__construct
	 */
	public function test_construct_sets_test_mode_from_settings(): void {
		$this->set_plugin_settings( [ 'test_mode' => 'yes' ] );
		$api_test = new Monri_WC_Api();
		$this->assertTrue( $api_test->test_mode );

		$this->set_plugin_settings( [ 'test_mode' => 'no' ] );
		$api_live = new Monri_WC_Api();
		$this->assertFalse( $api_live->test_mode );
	}

	/**
	 * @covers Monri_WC_Api::orders_show
	 */
	public function test_orders_show_payload_and_request(): void {
		$this->set_plugin_settings( [
			'test_mode' => 'yes',
			'monri_merchant_key' => 'test_merchant_key',
			'monri_authenticity_token' => 'test_auth_token',
		] );

		$captured_url = '';
		$captured_args = [];

		Functions\stubs( [
			'wp_remote_post' => function ( $url, $args ) use ( &$captured_url, &$captured_args ) {
				$captured_url = $url;
				$captured_args = $args;
				return [
					'response' => [ 'code' => 200 ],
					'body' => '<response><status>approved</status></response>',
				];
			},
			'wp_remote_retrieve_response_code' => function () {
				return 200;
			},
			'wp_remote_retrieve_body' => function ( $response ) {
				return $response['body'];
			},
		] );

		$api = new Monri_WC_Api();
		$response = $api->orders_show( 'ORD-1001' );

		$this->assertInstanceOf( SimpleXMLElement::class, $response );
		$this->assertSame( 'approved', (string) $response->status );
		$this->assertSame( 'https://ipgtest.monri.com/orders/show', $captured_url );

		$xml = simplexml_load_string( $captured_args['body'] );
		$this->assertSame( 'ORD-1001', (string) $xml->{'order-number'} );
		$this->assertSame( 'test_auth_token', (string) $xml->{'authenticity-token'} );

		$expected_digest = hash( 'SHA1', 'test_merchant_key' . 'ORD-1001' );
		$this->assertSame( $expected_digest, (string) $xml->digest );
	}

	/**
	 * @covers Monri_WC_Api::refund
	 */
	public function test_refund_payload_and_digest(): void {
		$this->set_plugin_settings( [
			'test_mode' => 'no',
			'monri_merchant_key' => 'live_key_123',
			'monri_authenticity_token' => 'live_token_abc',
		] );

		$captured_url = '';
		$captured_args = [];

		Functions\stubs( [
			'wp_remote_post' => function ( $url, $args ) use ( &$captured_url, &$captured_args ) {
				$captured_url = $url;
				$captured_args = $args;
				return [
					'response' => [ 'code' => 200 ],
					'body' => '<transaction><status>approved</status></transaction>',
				];
			},
			'wp_remote_retrieve_response_code' => function () {
				return 200;
			},
			'wp_remote_retrieve_body' => function ( $response ) {
				return $response['body'];
			},
		] );

		$api = new Monri_WC_Api();
		$response = $api->refund( 'ORD-555', 5000, 'EUR' );

		$this->assertInstanceOf( SimpleXMLElement::class, $response );
		$this->assertSame( 'https://ipg.monri.com/transactions/ORD-555/refund.xml', $captured_url );

		$xml = simplexml_load_string( $captured_args['body'] );
		$this->assertSame( 'ORD-555', (string) $xml->{'order-number'} );
		$this->assertSame( '5000', (string) $xml->amount );
		$this->assertSame( 'EUR', (string) $xml->currency );
		$this->assertSame( 'live_token_abc', (string) $xml->{'authenticity-token'} );

		$expected_digest = hash( 'SHA1', 'live_key_123' . 'ORD-555' . '5000' . 'EUR' );
		$this->assertSame( $expected_digest, (string) $xml->digest );
	}

	/**
	 * @covers Monri_WC_Api::capture
	 */
	public function test_capture_payload_and_endpoint(): void {
		$this->set_plugin_settings( [
			'test_mode' => 'yes',
			'monri_merchant_key' => 'key',
			'monri_authenticity_token' => 'token',
		] );

		$captured_url = '';
		Functions\stubs( [
			'wp_remote_post' => function ( $url, $args ) use ( &$captured_url ) {
				$captured_url = $url;
				return [
					'response' => [ 'code' => 201 ],
					'body' => '<transaction><status>approved</status></transaction>',
				];
			},
			'wp_remote_retrieve_response_code' => function () {
				return 201;
			},
			'wp_remote_retrieve_body' => function ( $response ) {
				return $response['body'];
			},
		] );

		$api = new Monri_WC_Api();
		$response = $api->capture( 'ORD-777', 2500, 'EUR' );

		$this->assertInstanceOf( SimpleXMLElement::class, $response );
		$this->assertSame( 'https://ipgtest.monri.com/transactions/ORD-777/capture.xml', $captured_url );
	}

	/**
	 * @covers Monri_WC_Api::void
	 */
	public function test_void_payload_and_endpoint(): void {
		$this->set_plugin_settings( [
			'test_mode' => 'yes',
			'monri_merchant_key' => 'key',
			'monri_authenticity_token' => 'token',
		] );

		$captured_url = '';
		Functions\stubs( [
			'wp_remote_post' => function ( $url, $args ) use ( &$captured_url ) {
				$captured_url = $url;
				return [
					'response' => [ 'code' => 200 ],
					'body' => '<transaction><status>approved</status></transaction>',
				];
			},
			'wp_remote_retrieve_response_code' => function () {
				return 200;
			},
			'wp_remote_retrieve_body' => function ( $response ) {
				return $response['body'];
			},
		] );

		$api = new Monri_WC_Api();
		$response = $api->void( 'ORD-888', 3000, 'EUR' );

		$this->assertInstanceOf( SimpleXMLElement::class, $response );
		$this->assertSame( 'https://ipgtest.monri.com/transactions/ORD-888/void.xml', $captured_url );
	}

	/**
	 * @covers Monri_WC_Api::refund
	 */
	public function test_api_handles_wp_error(): void {
		$this->set_plugin_settings( [ 'test_mode' => 'yes' ] );

		Functions\stubs( [
			'wp_remote_post' => function () {
				return new WP_Error( 'http_error', 'Connection timed out' );
			},
		] );

		$api = new Monri_WC_Api();
		$response = $api->refund( 'ORD-123', 100, 'EUR' );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'Connection timed out', $response->get_error_message() );
	}

	/**
	 * @covers Monri_WC_Api::refund
	 */
	public function test_api_handles_http_error_status_code(): void {
		$this->set_plugin_settings( [ 'test_mode' => 'yes' ] );

		Functions\stubs( [
			'wp_remote_post' => function () {
				return [ 'response' => [ 'code' => 500 ], 'body' => 'Internal Server Error' ];
			},
			'wp_remote_retrieve_response_code' => function () {
				return 500;
			},
			'wp_remote_retrieve_body' => function ( $response ) {
				return $response['body'];
			},
		] );

		$api = new Monri_WC_Api();
		$response = $api->refund( 'ORD-123', 100, 'EUR' );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'monri_api_error', $response->get_error_code() );
	}

	/**
	 * @covers Monri_WC_Api::refund
	 */
	public function test_api_handles_invalid_xml_body(): void {
		$this->set_plugin_settings( [ 'test_mode' => 'yes' ] );

		Functions\stubs( [
			'wp_remote_post' => function () {
				return [ 'response' => [ 'code' => 200 ], 'body' => '<<<Not XML>>>' ];
			},
			'wp_remote_retrieve_response_code' => function () {
				return 200;
			},
			'wp_remote_retrieve_body' => function ( $response ) {
				return $response['body'];
			},
		] );

		$api = new Monri_WC_Api();
		$prev = libxml_use_internal_errors( true );
		$response = $api->refund( 'ORD-123', 100, 'EUR' );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'monri_api_error', $response->get_error_code() );
	}
}
