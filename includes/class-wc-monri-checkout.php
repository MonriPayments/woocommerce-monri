<?php

class WC_Monri_Checkout extends WC_Checkout {

	/**
	 * Process the checkout after the confirm order button is pressed.
	 *
	 * @throws Exception When validation fails.
	 */
	public function process_checkout() {
		try {
			$nonce_value    = wc_get_var( $_REQUEST['woocommerce-process-checkout-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // phpcs:ignore
			$expiry_message = sprintf(
			/* translators: %s: shop cart url */
				__( 'Sorry, your session has expired. <a href="%s" class="wc-backward">Return to shop</a>', 'woocommerce' ),
				esc_url( wc_get_page_permalink( 'shop' ) )
			);

			if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, 'woocommerce-process_checkout' ) ) {
				// If the cart is empty, the nonce check failed because of session expiry.
				if ( WC()->cart->is_empty() ) {
					throw new Exception( $expiry_message );
				}

				WC()->session->set( 'refresh_totals', true );
				throw new Exception( __( 'We were unable to process your order, please try again.', 'woocommerce' ) );
			}

			wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );
			wc_set_time_limit( 0 );

			do_action( 'woocommerce_before_checkout_process' );

			if ( WC()->cart->is_empty() ) {
				throw new Exception( $expiry_message );
			}

			do_action( 'woocommerce_checkout_process' );

			$errors      = new WP_Error();
			$posted_data = $this->get_posted_data();

			// Update session for customer and totals.
			$this->update_session( $posted_data );

			// Validate posted data and cart items before proceeding.
			$this->validate_checkout( $posted_data, $errors );

			foreach ( $errors->errors as $code => $messages ) {
				$data = $errors->get_error_data( $code );
				foreach ( $messages as $message ) {
					wc_add_notice( $message, 'error', $data );
				}
			}

			if ( empty( $posted_data['woocommerce_checkout_update_totals'] ) && 0 === wc_notice_count( 'error' ) ) {
				$this->process_customer( $posted_data );
				$order_id = $this->create_order( $posted_data );
				$order    = wc_get_order( $order_id );

				if ( is_wp_error( $order_id ) ) {
					throw new Exception( $order_id->get_error_message() );
				}

				if ( ! $order ) {
					throw new Exception( __( 'Unable to create order.', 'woocommerce' ) );
				}
				wp_send_json(array( 'result'   => 'success'));

			} else {
				$this->send_ajax_failure_response();
			}

		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
			$this->send_ajax_failure_response();
		}
	}
}
add_action('wc_ajax_monri_checkout', 'monri_process_checkout');

function monri_process_checkout() {
	$monri_checkout = new WC_Monri_Checkout();
	$monri_checkout->process_checkout();
}
