<?php

//or use woocommerce_payment_token_class filter !!

class WC_Payment_Token_Monri_Wspay extends WC_Payment_Token {

	/**
	 * @var string Toke Type String
	 */
	protected $type = 'Monri_Wspay';

	protected $extra_data = array(
		'number'       => '',
		'expiry_year_month'  => '',
		'expiry_month' => '',
	);

	public function get_display_nameXX( $deprecated = '' ) {

		$display = sprintf(
		/* translators: 1: credit card type 2: last 4 digits 3: expiry month 4: expiry year */
			__( '%1$s ending in %2$s (expires %3$s/%4$s)', 'woocommerce' ),
			wc_get_credit_card_type_label( $this->get_card_type() ),
			$this->get_last4(),
			$this->get_expiry_month(),
			substr( $this->get_expiry_year(), 2 )
		);
		return $display;
	}

	/**
	 * Return the masked credit card number.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string Masked credit card number.
	 */
	public function get_number( $context = 'view' ) {
		return $this->get_prop( 'number', $context );
	}

	/**
	 * Set the masked credit card number.
	 *
	 * @param string $number Masked credit card number.
	 */
	public function set_number( $number ) {
		$this->set_prop( 'number', $number );
	}

}
