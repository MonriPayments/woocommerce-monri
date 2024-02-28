<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Payments Blocks integration
 */
final class Monri_WC_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var Monri_WC_Gateway
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'monri';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_monri_settings', [] );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];

		woocommerce_store_api_register_update_callback(
			[
				'namespace' => 'monri-payments',
				'callback'  => function ( $data ) {
					if ( ! isset( $data['installments'] ) ) {
						return;
					}

					$installments = $data['installments'];
					WC()->session->set( 'monri_installments', $installments );
				}
			]
		);
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
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
		$script_url        = MONRI_WC_PLUGIN_URL . $script_path;

		if ( $this->get_setting( 'monri_payment_gateway_service' ) === 'monri-web-pay' &&
		     $this->get_setting( 'monri_web_pay_integration_type' ) === 'components'
		) {
			$script_asset['dependencies'][] = 'monri-components';
		}
		wp_register_script(
			'monri-wc-payments-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'monri-wc-payments-blocks', 'monri', MONRI_WC_PLUGIN_PATH . 'languages/' );
		}

		return [ 'monri-wc-payments-blocks' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$data = [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] ),
			'service'     => $this->get_setting( 'monri_payment_gateway_service' ),
		];

		if ( $data['service'] === 'monri-web-pay' ) {
			$data['integration_type'] = $this->get_setting( 'monri_web_pay_integration_type' );

			if ( $data['integration_type'] === 'components' ) {
				$data['components'] = $this->prepare_components_data();
			}
		}

		// @todo not aware of bottom limit

		if ( $data['service'] === 'monri-web-pay' && $this->get_setting( 'paying_in_installments' ) ) {
			$data['installments'] = $this->get_setting( 'number_of_allowed_installments' );
		} else {
			$data['installments'] = 0;
		}

		return $data;
	}

	/**
	 * @return array
	 */
	private function prepare_components_data() {
		// @see Monri_WC_Gateway_Adapter_Webpay_Components::payment_fields
		$randomToken = wp_generate_uuid4();
		$timestamp   = ( new DateTime() )->format( 'c' );
		$digest      = hash( 'SHA512', $this->get_setting( 'monri_merchant_key' ) . $randomToken . $timestamp );

		return array(
			'authenticity_token' => $this->get_setting( 'monri_authenticity_token' ),
			'random_token'       => $randomToken,
			'digest'             => $digest,
			'timestamp'          => $timestamp
		);
	}
}
