<?php

class Monri_WC_Payment_Token_Webpay extends WC_Payment_Token {

	/**
	 * @var string Token Type String
	 */
	protected $type = 'Monri_Webpay';

	/**
	 * Stores payment token data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'last4'        => '',
		'card_type'    => '',
		'expiry_year'  => '',
		'expiry_month' => '',
	);

	/**
	 * Get type to display to user.
	 *
	 * @param string $deprecated Deprecated since WooCommerce 3.0.
	 *
	 * @return string
	 * @since  2.6.0
	 */
	public function get_display_name( $deprecated = '' ) {
		$display = sprintf(
		/* translators: 1: credit card type 2: last 4 digits  */
			__( '%1$s ending in %2$s ', 'woocommerce' ),
			wc_get_credit_card_type_label( $this->get_card_type() ),
			$this->get_last4(),
		);

		return $display;
	}

	/**
	 * Returns the card type (mastercard, visa, ...).
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string Card type
	 */
	public function get_card_type( $context = 'view' ) {
		return $this->get_prop( 'card_type', $context );
	}

	/**
	 * Set the card type (mastercard, visa, ...).
	 *
	 * @param string $type Credit card type (mastercard, visa, ...).
	 */
	public function set_card_type( $type ) {
		$this->set_prop( 'card_type', $type );
	}


	/**
	 * Returns the last four digits.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string Last 4 digits
	 */
	public function get_last4( $context = 'view' ) {
		return $this->get_prop( 'last4', $context );
	}

	/**
	 * Set the last four digits.
	 *
	 * @param string $last4 Credit card last four digits.
	 */
	public function set_last4( $last4 ) {
		$this->set_prop( 'last4', $last4 );
	}


	/**
	 * Returns the card expiration year (YYYY).
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string Expiration year
	 */
	public function get_expiry_year( $context = 'view' ) {
		return $this->get_prop( 'expiry_year', $context );
	}

	/**
	 * Set the expiration year for the card (YYYY format).
	 *
	 * @param string $year Credit card expiration year.
	 *
	 * @since 2.6.0
	 */
	public function set_expiry_year( $year ) {
		$this->set_prop( 'expiry_year', $year );
	}

	/**
	 * Returns the card expiration month (MM).
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string Expiration month
	 */
	public function get_expiry_month( $context = 'view' ) {
		return $this->get_prop( 'expiry_month', $context );
	}

	/**
	 * Set the expiration month for the card (formats into MM format).
	 *
	 * @param string $month Credit card expiration month.
	 */
	public function set_expiry_month( $month ) {
		$this->set_prop( 'expiry_month', str_pad( $month, 2, '0', STR_PAD_LEFT ) );
	}
}
