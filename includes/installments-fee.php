<?php

class Monri_WC_Installments_Fee {

	public const CODE = 'monri_installments_fee';

	/**
	 * @var Monri_WC_Settings
	 */
	private $settings;

	public function __construct() {
		$this->settings = Monri_WC_Settings::instance();
	}

	/**
	 * Init hooks
	 */
	public function init() {

		if ( is_admin() ) {
			return;
		}

		add_action( 'woocommerce_after_calculate_totals', array( $this, 'after_calculate_totals' ) );
		add_action( 'woocommerce_checkout_update_order_review', [ $this, 'update_order_review' ] );

		if ( function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
			woocommerce_store_api_register_update_callback(
				[
					'namespace' => 'monri-payments',
					'callback'  => [ $this, 'store_api_update_callback' ]
				]
			);
		}

		// reset installments on checkout load
		add_action( 'template_redirect', function () {
			if ( ( is_checkout() && ! is_checkout_pay_page() ) || is_cart() ) {
				//WC()->session->set( 'monri_installments', 0 );
				unset( WC()->session->monri_installments );
			}
		} );

	}

	/**
	 * Sets selected installments on New Blocks Checkout
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function store_api_update_callback( $data ) {
		if ( ! isset( $data['installments'] ) ) {
			return;
		}

		$installments = $data['installments'];
		WC()->session->set( 'monri_installments', $installments );
	}

	/**
	 * Sets selected installments on Old Checkout
	 *
	 * @param string $posted_data
	 *
	 * @return void
	 */
	public function update_order_review( $posted_data ) {
		parse_str( $posted_data, $posted_data );

		if ( isset ( $posted_data['payment_method'] ) && $posted_data['payment_method'] !== 'monri' ) {
			unset ( WC()->session->monri_installments );

			return;
		}

		/** @var array $posted_data */
		if ( isset( $posted_data['monri-card-installments'] ) ) {
			WC()->session->set( 'monri_installments', (int) $posted_data['monri-card-installments'] );
		}
	}

	/**
	 * @param WC_Cart $cart
	 */
	public function after_calculate_totals( $cart ) {

		if ( ! ( $cart instanceof WC_Cart ) ) {
			$cart = WC()->cart;
		}

		//@note: payment is not set on session on new checkout, so we can't check selected payment here

		// get installments from session
		$installments = (int) WC()->session->get( 'monri_installments' );

		if ( $installments <= 1 || $installments > 36 ) {
			return;
		}

		$total = (float) $cart->get_total( 'edit' );

		$installments_fee_percent = (float) $this->settings->get_option( "price_increase_$installments", 0 );
		$installments_fee         = round( $total * $installments_fee_percent / 100, 2 );

		if ( $installments_fee < 0.01 ) {
			return;
		}

		$cart->fees_api()->add_fee( array(
			'id'        => self::CODE,
			'name'      => __( 'Installments fee', 'monri' ),
			'taxable'   => false,
			'tax_class' => '',
            'tax'       => 0.0,
            'tax_data'  => '',
			'amount'    => $installments_fee,
			'total'     => $installments_fee
		) );

		$cart->set_fee_total( $cart->get_fee_total() + $installments_fee );
		$cart->set_total( $total + $installments_fee );
	}

}
