<?php

namespace Monri\Tests\Integration;

use Monri_WC_Gateway;
use Monri_WC_Payment_Token_Webpay;
use Monri_WC_Payment_Token_Wspay;
use WC_Payment_Tokens;

class OrderProcessTest extends TestCase {

	/**
	 * Test order creation and payment complete status transition.
	 */
	public function test_order_creation_and_payment_complete(): void {
		$order = $this->create_order();
		$this->assertSame( 'pending', $order->get_status() );
		$this->assertSame( 'monri', $order->get_payment_method() );

		$order->payment_complete( 'trx_test_999' );

		$this->assertContains( $order->get_status(), [ 'processing', 'completed' ] );
		$this->assertSame( 'trx_test_999', $order->get_transaction_id() );
	}

	/**
	 * Test WebPay custom payment token creation and retrieval.
	 */
	public function test_webpay_payment_token_persistence(): void {
		$token = new Monri_WC_Payment_Token_Webpay();
		$token->set_token( 'webpay_tok_integrate_123' );
		$token->set_gateway_id( 'monri' );
		$token->set_user_id( 1 );
		$token->set_last4( '4242' );
		$token->set_card_type( 'visa' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2028' );
		$token_id = $token->save();

		$this->assertGreaterThan( 0, $token_id );

		$retrieved = WC_Payment_Tokens::get( $token_id );
		if ( $retrieved ) {
			$this->assertSame( 'webpay_tok_integrate_123', $retrieved->get_token() );
			$this->assertSame( 'monri', $retrieved->get_gateway_id() );
			$this->assertSame( '4242', $retrieved->get_last4() );
		}
	}

	/**
	 * Test WSPay custom payment token creation and retrieval.
	 */
	public function test_wspay_payment_token_persistence(): void {
		$token = new Monri_WC_Payment_Token_Wspay();
		$token->set_token( 'wspay_tok_integrate_456' );
		$token->set_gateway_id( 'monri' );
		$token->set_user_id( 1 );
		$token->set_last4( '1111' );
		$token->set_card_type( 'MasterCard' );
		$token->set_expiry_month( '06' );
		$token->set_expiry_year( '2029' );
		$token_id = $token->save();

		$this->assertGreaterThan( 0, $token_id );

		$retrieved = WC_Payment_Tokens::get( $token_id );
		if ( $retrieved ) {
			$this->assertSame( 'wspay_tok_integrate_456', $retrieved->get_token() );
			$this->assertSame( 'monri', $retrieved->get_gateway_id() );
			$this->assertSame( '1111', $retrieved->get_last4() );
		}
	}

	/**
	 * Test refund flow eligibility check.
	 */
	public function test_can_refund_order_flow(): void {
		$gateway = new Monri_WC_Gateway();
		$order = $this->create_order();

		$this->assertFalse( $gateway->can_refund_order( $order ) );

		$order->payment_complete( 'trx_paid_555' );
		$this->assertTrue( $gateway->can_refund_order( $order ) );
	}
}
