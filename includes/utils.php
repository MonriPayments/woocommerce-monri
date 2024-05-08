<?php

class Monri_WC_Utils {

	private const TEST_SUFFIX = '-test';

	/**
	 * @param string $order_id
	 *
	 * @return string
	 */
	public static function get_test_order_id( $order_id ) {
		return $order_id . self::TEST_SUFFIX . time();
	}

	/**
	 * @param string $test_order_id
	 *
	 * @return string
	 */
	public static function resolve_real_order_id( $test_order_id ) {
		return strstr( $test_order_id, self::TEST_SUFFIX, true ) ?: $test_order_id;
	}

	/**
	 * Sanitize hash, only hex digits/letters allowed in lowercase (0-9 and a-f)
	 *
	 * @param string $hash
	 *
	 * @return string
	 */
	public static function sanitize_hash( $hash ) {
		return (string) preg_replace( '/[^a-f0-9]/', '', $hash );
	}
}
