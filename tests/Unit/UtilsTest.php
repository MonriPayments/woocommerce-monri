<?php

namespace Monri\Tests\Unit;

use Monri_WC_Utils;

class UtilsTest extends TestCase {

	/**
	 * @covers Monri_WC_Utils::get_test_order_id
	 */
	public function test_get_test_order_id_appends_suffix_and_timestamp(): void {
		$before = time();
		$test_id = Monri_WC_Utils::get_test_order_id( '12345' );
		$after = time();

		$this->assertStringStartsWith( '12345-test', $test_id );
		$timestamp_str = substr( $test_id, strlen( '12345-test' ) );
		$this->assertIsNumeric( $timestamp_str );
		$timestamp = (int) $timestamp_str;
		$this->assertGreaterThanOrEqual( $before, $timestamp );
		$this->assertLessThanOrEqual( $after, $timestamp );
	}

	/**
	 * @covers Monri_WC_Utils::resolve_real_order_id
	 */
	public function test_resolve_real_order_id_with_test_suffix(): void {
		$test_id = '9999-test1712345678';
		$real_id = Monri_WC_Utils::resolve_real_order_id( $test_id );
		$this->assertSame( '9999', $real_id );

		$string_test_id = 'WC_ORDER_100-test1600000000';
		$real_string_id = Monri_WC_Utils::resolve_real_order_id( $string_test_id );
		$this->assertSame( 'WC_ORDER_100', $real_string_id );
	}

	/**
	 * @covers Monri_WC_Utils::resolve_real_order_id
	 */
	public function test_resolve_real_order_id_without_test_suffix(): void {
		$id = '8888';
		$real_id = Monri_WC_Utils::resolve_real_order_id( $id );
		$this->assertSame( '8888', $real_id );

		$custom_id = 'ORDER-PREFIX-123';
		$this->assertSame( 'ORDER-PREFIX-123', Monri_WC_Utils::resolve_real_order_id( $custom_id ) );
	}

	/**
	 * @covers Monri_WC_Utils::sanitize_hash
	 */
	public function test_sanitize_hash_preserves_valid_hex(): void {
		$valid_hash = 'a1b2c3d4e5f67890';
		$this->assertSame( $valid_hash, Monri_WC_Utils::sanitize_hash( $valid_hash ) );
	}

	/**
	 * @covers Monri_WC_Utils::sanitize_hash
	 */
	public function test_sanitize_hash_removes_invalid_characters(): void {
		$dirty_hash = 'a1B2-C3_d4!e5F6@7890#XYZ';
		// It strips non [a-f0-9] chars (note: capital letters B, C, F, X, Y, Z are stripped)
		$sanitized = Monri_WC_Utils::sanitize_hash( $dirty_hash );
		$this->assertSame( 'a123d4e567890', $sanitized );
	}

	/**
	 * @covers Monri_WC_Utils::sanitize_hash
	 */
	public function test_sanitize_hash_empty_string(): void {
		$this->assertSame( '', Monri_WC_Utils::sanitize_hash( '' ) );
		$this->assertSame( '', Monri_WC_Utils::sanitize_hash( '---!@#$' ) );
	}
}
