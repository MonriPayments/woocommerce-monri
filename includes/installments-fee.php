<?php
class Monri_WC_Installments_Fee
{
	const NAME = 'Installments fee';

	const CODE = 'monri_installments_fee';

	public function __construct() {

	}

	/**
	 * Init hooks
	 */
	public function init() {
		// check if enabled?

		if (is_admin()) {
			return;
		}

		add_action( 'woocommerce_after_calculate_totals', array( $this, 'after_calculate_totals' ) );
	}

	/**
	 * @param WC_Cart $cart
	 */
	public function after_calculate_totals( $cart ) {
		if( WC()->session->get( 'chosen_payment_method' ) !== 'monri' ) {
			return;
		}

		// check if active adapter has installments?

		$total = (float)$cart->get_total( 'edit' );

		// monri_installments, how to set on cart? get from post? set on wc session?

		$number_of_installments = 0;
		$installments_fee = 0;

		if ($number_of_installments > 1) {
			$installments_fee = (float) $this->settings->get_option("price_increase_$number_of_installments", 0);
			//if ($installments_fee !== 0) {
				$installments_fee = $total * $installments_fee / 100;
			//}
		}

		if ($installments_fee < 0.01) {
			return;
		}

		$cart->fees_api()->add_fee(array(
			'id' => self::CODE,
			'name'      => __(self::NAME, 'monri'),
			'taxable'   => false,
			'tax_class' => '',
			'amount' => $installments_fee,
			'total' => $installments_fee
		));

		$cart->set_fee_total( $cart->get_fee_total() + $installments_fee);
		$cart->set_total( $total + $installments_fee );
	}

}
