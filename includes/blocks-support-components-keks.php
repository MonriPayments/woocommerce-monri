<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Payments Blocks integration
 */
final class Monri_WC_Keks_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'monri-components-keks';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_monri_settings', [] );

		add_action('enqueue_block_editor_assets', function () {
			$script_url = $this->get_setting( 'test_mode' ) ?
				Monri_WC_Gateway_Adapter_Webpay_Components::SCRIPT_ENDPOINT_TEST :
				Monri_WC_Gateway_Adapter_Webpay_Components::SCRIPT_ENDPOINT;
			wp_enqueue_script( 'monri-components-keks', $script_url, array(), MONRI_WC_VERSION );
		});
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		$settings = get_option( 'woocommerce_monri_settings', [] );
		$supported_payment_methods = $settings['monri_web_pay_supported_payment_methods'] ?? [];
		if ( $settings['monri_web_pay_integration_type'] === 'components' && in_array( 'keks-pay-hr', $supported_payment_methods, true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/assets/js/blocks/index.js';
		$script_asset_path = MONRI_WC_PLUGIN_PATH . 'assets/js/blocks/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require( $script_asset_path )
			: array(
				'dependencies' => array(),
				'version'      => MONRI_WC_VERSION
			);
		$script_url = MONRI_WC_PLUGIN_URL . $script_path;

		if ( $this->get_setting( 'monri_payment_gateway_service' ) === 'monri-web-pay' &&
		     $this->get_setting( 'monri_web_pay_integration_type' ) === 'components' &&
             wp_script_is( 'monri-components' )
		) {
			$script_asset['dependencies'][] = 'monri-components';
		}
		wp_register_script(
			'monri-keks-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'monri-keks-blocks', 'monri', MONRI_WC_PLUGIN_PATH . 'languages/' );
		}

		return [ 'monri-keks-blocks' ];
	}

}
