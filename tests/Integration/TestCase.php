<?php

namespace Monri\Tests\Integration;

use WC_Data_Exception;
use WC_Order;
use WP_UnitTestCase;

if ( ! class_exists( '\WP_UnitTestCase' ) ) {
	require_once __DIR__ . '/bootstrap.php';
}

abstract class TestCase extends WP_UnitTestCase {
	/**
	 * Helper to create a test WooCommerce order.
	 *
	 * @param array $args
	 *
	 * @return WC_Order
	 * @throws WC_Data_Exception
	 */
	protected function create_order( array $args = [] ): WC_Order {
		if ( function_exists( 'wc_create_order' ) ) {
			$order = wc_create_order( $args );
		} else {
			$order = new WC_Order();
		}
		$order->set_payment_method( 'monri' );
		$order->set_currency( 'EUR' );
		$order->set_total( '100.00' );
		$order->save();
		return $order;
	}
}
