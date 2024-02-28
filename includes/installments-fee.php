<?php
class Monri_WC_Installments_Fee
{
	public const NAME = 'Installments fee';

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
		// check if enabled?

		if (is_admin()) {
			return;
		}

		add_action( 'woocommerce_after_calculate_totals', array( $this, 'after_calculate_totals' ) );

		// add fee javascript here
	}

	/**
	 * @param WC_Cart $cart
	 */
	public function after_calculate_totals( $cart ) {

		if (!($cart instanceof WC_Cart)) {
			$cart = WC()->cart;
		}


		if( WC()->session->get( 'chosen_payment_method' ) !== 'monri' ) {
			return;
		}

		// check if installments are enabled?

		// monri_installments, how to set on cart? get from post? set on wc session?
		$installments = (int) WC()->session->get( 'monri_installments' );

		if( $installments <= 1 || $installments > 24) {
			return;
		}

		$total = (float)$cart->get_total( 'edit' );

		$installments_fee_percent = (float) $this->settings->get_option("price_increase_$installments", 0);
		//if ($installments_fee_percent !== 0) {
			$installments_fee = $total * $installments_fee_percent / 100;
		//}

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
