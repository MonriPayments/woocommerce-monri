<?php

namespace Monri\Tests\Unit;

use Monri_WC_Installments_Fee;
use WC_Cart;

class InstallmentsFeeTest extends TestCase {

	/**
	 * @covers Monri_WC_Installments_Fee::after_calculate_totals
	 */
	public function test_after_calculate_totals_skips_when_installments_less_than_two(): void {
		$this->set_plugin_settings( [
			'price_increase_1' => '5.0',
		] );

		WC()->session->set( 'monri_installments', 1 );

		$cart = new WC_Cart();
		$cart->set_total( 100.0 );

		$fee_calculator = new Monri_WC_Installments_Fee();
		$fee_calculator->after_calculate_totals( $cart );

		$this->assertCount( 0, $cart->fees_api()->get_fees() );
		$this->assertSame( 100.0, $cart->get_total() );
	}

	/**
	 * @covers Monri_WC_Installments_Fee::after_calculate_totals
	 */
	public function test_after_calculate_totals_skips_when_installments_greater_than_36(): void {
		$this->set_plugin_settings( [
			'price_increase_37' => '5.0',
		] );

		WC()->session->set( 'monri_installments', 37 );

		$cart = new WC_Cart();
		$cart->set_total( 100.0 );

		$fee_calculator = new Monri_WC_Installments_Fee();
		$fee_calculator->after_calculate_totals( $cart );

		$this->assertCount( 0, $cart->fees_api()->get_fees() );
		$this->assertSame( 100.0, $cart->get_total() );
	}

	/**
	 * @covers Monri_WC_Installments_Fee::after_calculate_totals
	 */
	public function test_after_calculate_totals_skips_when_fee_is_zero(): void {
		$this->set_plugin_settings( [
			'price_increase_6' => '0',
		] );

		WC()->session->set( 'monri_installments', 6 );

		$cart = new WC_Cart();
		$cart->set_total( 100.0 );

		$fee_calculator = new Monri_WC_Installments_Fee();
		$fee_calculator->after_calculate_totals( $cart );

		$this->assertCount( 0, $cart->fees_api()->get_fees() );
		$this->assertSame( 100.0, $cart->get_total() );
	}

	/**
	 * @covers Monri_WC_Installments_Fee::after_calculate_totals
	 */
	public function test_after_calculate_totals_calculates_and_adds_fee(): void {
		$this->set_plugin_settings( [
			'price_increase_6' => '5.5',
		] );

		WC()->session->set( 'monri_installments', 6 );

		$cart = new WC_Cart();
		$cart->set_total( 200.0 );
		$cart->set_fee_total( 0 );

		$fee_calculator = new Monri_WC_Installments_Fee();
		$fee_calculator->after_calculate_totals( $cart );

		// Expected fee: 200.0 * 5.5 / 100 = 11.00
		$fees = $cart->fees_api()->get_fees();
		$this->assertCount( 1, $fees );
		$fee = $fees[0];
		$this->assertSame( Monri_WC_Installments_Fee::CODE, $fee['id'] );
		$this->assertSame( 'Installments fee', $fee['name'] );
		$this->assertFalse( $fee['taxable'] );
		$this->assertSame( 11.0, $fee['amount'] );
		$this->assertSame( 11.0, $fee['total'] );

		$this->assertSame( 11.0, $cart->get_fee_total() );
		$this->assertSame( 211.0, $cart->get_total() );
	}

	/**
	 * @covers Monri_WC_Installments_Fee::after_calculate_totals
	 */
	public function test_after_calculate_totals_with_fallback_cart(): void {
		$this->set_plugin_settings( [
			'price_increase_12' => '10.0',
		] );

		WC()->session->set( 'monri_installments', 12 );
		WC()->cart->set_total( 150.0 );
		WC()->cart->set_fee_total( 0 );
		WC()->cart->fees_api()->fees = [];

		$fee_calculator = new Monri_WC_Installments_Fee();
		$fee_calculator->after_calculate_totals( null );

		$fees = WC()->cart->fees_api()->get_fees();
		$this->assertCount( 1, $fees );
		$this->assertSame( 15.0, $fees[0]['amount'] );
		$this->assertSame( 165.0, WC()->cart->get_total() );
	}

	/**
	 * @covers Monri_WC_Installments_Fee::store_api_update_callback
	 */
	public function test_store_api_update_callback_sets_session(): void {
		$fee_calculator = new Monri_WC_Installments_Fee();

		$fee_calculator->store_api_update_callback( [ 'installments' => 8 ] );
		$this->assertSame( 8, WC()->session->get( 'monri_installments' ) );

		// When installments are not present in payload
		WC()->session->set( 'monri_installments', 5 );
		$fee_calculator->store_api_update_callback( [ 'other_param' => 'value' ] );
		$this->assertSame( 5, WC()->session->get( 'monri_installments' ) );
	}

	/**
	 * @covers Monri_WC_Installments_Fee::update_order_review
	 */
	public function test_update_order_review(): void {
		$fee_calculator = new Monri_WC_Installments_Fee();

		// Case 1: Monri payment method with installments
		$posted_data = 'payment_method=monri&monri-card-installments=4';
		$fee_calculator->update_order_review( $posted_data );
		$this->assertSame( 4, WC()->session->get( 'monri_installments' ) );

		// Case 2: Other payment method unsets installments
		$posted_data_other = 'payment_method=bacs';
		$fee_calculator->update_order_review( $posted_data_other );
		$this->assertNull( WC()->session->get( 'monri_installments' ) );
	}
}
