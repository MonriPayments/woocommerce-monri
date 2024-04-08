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
}
