<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Payments Blocks integration
 */
final class Monri_WC_Components_Pay_Cek_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'monri_components_pay_cek';

	/**
	 * The gateway instance.
	 *
	 * @var Monri_WC_Gateway_Webpay_Components_Pay_Cek
	 */
	private $gateway;

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
		$this->settings = get_option( 'woocommerce_monri_settings', array() );

		add_action(
			'enqueue_block_editor_assets',
			function () {
				$script_url = $this->get_setting( 'test_mode' ) ?
					Monri_WC_Gateway_Webpay_Components_Abstract::SCRIPT_ENDPOINT_TEST :
					Monri_WC_Gateway_Webpay_Components_Abstract::SCRIPT_ENDPOINT;
				wp_enqueue_script( 'monri-components-pay-cek', $script_url, array(), MONRI_WC_VERSION );
			}
		);
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		// todo: temporary. Fix once fully indepedent from components
		return Monri_WC_Settings::instance()->include_components_pay_cek();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/assets/js/blocks/pay-cek.js';
		$script_asset_path = MONRI_WC_PLUGIN_PATH . 'assets/js/blocks/pay-cek.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => MONRI_WC_VERSION,
			);
		$script_url        = MONRI_WC_PLUGIN_URL . $script_path;

		if ( wp_script_is( 'monri-components-pay-cek' ) ) {
			$script_asset['dependencies'][] = 'monri-components-pay-cek';
		}

		wp_register_script(
			'monri-pay-cek-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'monri-pay-cek-blocks', 'monri', MONRI_WC_PLUGIN_PATH . 'languages/' );
		}

		return array( 'monri-pay-cek-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$data = array(
			'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
		);

		return $data;
	}
}
