<?php

class Monri_WC_Utils {

	private const TEST_SUFFIX = '-test';

	private const ORDER_LOCK_PREFIX = 'monri_order_';

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

	/**
	 * MySQL 5.7.5+ limits lock names to 64 characters.
	 * false = already locked, true = lock acquired, null = mysql error
	 *
	 * @param string $order_id
	 *
	 * @return bool|null
	 */
	public static function order_lock( $order_id ) {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT GET_LOCK(%s, %d)',
				self::ORDER_LOCK_PREFIX . $order_id,
				0
			)
		);

		if ( null === $result ) {
			return null;
		}

		return $result === '1';
	}

	/**
	 * @param $order_id
	 *
	 * @return bool|null
	 */
	public static function order_unlock( $order_id ) {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT RELEASE_LOCK(%s)',
				self::ORDER_LOCK_PREFIX . $order_id
			)
		);

		if ( null === $result ) {
			return null;
		}

		return $result === '1';
	}
}
