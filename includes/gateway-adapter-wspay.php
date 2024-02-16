<?php

class Monri_WC_Gateway_Adapter_Wspay
{
	public const ADAPTER_ID = 'wspay';

	public const ENDPOINT_TEST =  'https://ipgtest.monri.com/v2/form';
	public const ENDPOINT = 'https://ipg.monri.com/v2/form';

	/**
	 * @var Monri_WC_Settings
	 */
	private $settings;

	public $has_fields = false;

	/**
	 * @var Monri_WC_Gateway
	 */
	private $payment;

	public function __construct()
	{
		$this->settings = Monri_WC_Settings::instance();
	}

	public function init($payment) {
		$this->payment = $payment;
		//$this->payment->id

		//$this->check_monri_response();
		add_action('woocommerce_receipt_monri', [$this, 'process_redirect']);
		add_action('woocommerce_thankyou_monri', [$this, 'process_return']);
	}

}
