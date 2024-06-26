<?php

class Monri_WC_Gateway extends WC_Payment_Gateway {

	private const TEST_SUFFIX = '-test';

	private $adapter;

	public $id = 'monri';

	public function __construct() {
        $this->method_title = __('Monri', 'monri');
		$this->method_description = __('Monri Payment Gateway Plug-in for WooCommerce', 'monri');

		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->settings['title'] ?? __('Monri', 'monri');
		$this->description = $this->settings['description'];
		//$this->instructions = $this->get_option('instructions');

		// resolve adapter based on settings
		if ($this->get_option('monri_payment_gateway_service') === 'monri-ws-pay') {
			require_once __DIR__ . '/gateway-adapter-wspay.php';
            require_once __DIR__ . '/monri-wspay-api.php';
			$this->adapter = new Monri_WC_Gateway_Adapter_Wspay();
		} elseif ($this->get_option('monri_payment_gateway_service') === 'monri-web-pay' &&
		          $this->get_option('monri_web_pay_integration_type') === 'components'
		) {
			require_once __DIR__ . '/gateway-adapter-webpay-components.php';
            require_once __DIR__ . '/monri-api.php';
			$this->adapter = new Monri_WC_Gateway_Adapter_Webpay_Components();
		} else {
			require_once __DIR__ . '/gateway-adapter-webpay-form.php';
            require_once __DIR__ . '/monri-api.php';
			$this->adapter = new Monri_WC_Gateway_Adapter_Webpay_Form();
		}

		$this->adapter->init($this);

		// @todo: maybe not here?
        require_once __DIR__ . '/callback.php';
        $callback = new Monri_WC_Callback();
        $callback->init();
		//
        $this->supports = [ 'products', 'refunds', 'tokenization' ];
		if (is_admin()) {
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
		}
	}

	/**
	 * @return string
	 */
	public function get_adapter_id() {
		return $this->adapter::ADAPTER_ID;
	}

	/**
	 * Forward to adapter
	 *
	 * @inheritDoc
	 */
	public function process_payment( $order_id ) {
		if(method_exists($this->adapter, 'process_payment')) {
			return $this->adapter->process_payment( $order_id );
		}
		return parent::process_payment( $order_id );
	}

	/**
	 * Forward to adapter
	 *
	 * @inheritDoc
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		if(method_exists($this->adapter, 'process_refund')) {
			return $this->adapter->process_refund( $order_id, $amount, $reason );
		}
		return parent::process_refund( $order_id, $amount, $reason );
	}

	/**
	 * Forward to adapter
	 *
	 * @inheritDoc
	 */
	public function validate_fields() {
		if(method_exists($this->adapter, 'validate_fields')) {
			return $this->adapter->validate_fields();
		}
		return parent::validate_fields();
	}

	/**
	 * Forward to adapter
	 *
	 * @inheritDoc
	 */
	public function payment_fields() {
		parent::payment_fields();
		if(method_exists($this->adapter, 'payment_fields')) {
			$this->adapter->payment_fields();
		}
	}

	/**
	 * Forward to adapter, prepare blocks data for new checkout
	 *
	 * @return array
	 */
	public function prepare_blocks_data() {
		if(method_exists($this->adapter, 'prepare_blocks_data')) {
			return $this->adapter->prepare_blocks_data();
		}

		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function init_form_fields() {
		$this->form_fields =  Monri_WC_Settings::instance()->get_form_fields();
	}

	/**
	 * @inheritDoc
	 */
	public function admin_options()
	{
		parent::admin_options();

        $path = plugins_url( 'assets/js/field-dependency.js', MONRI_WC_PLUGIN_INDEX );
        wp_enqueue_script( 'monri-admin', $path, [], MONRI_WC_VERSION);
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function get_option_bool( $key ) {
		return in_array( $this->get_option( $key ),  array( 'yes', '1', true ), true );
	}

    /**
     * Forward to adapter
     *
     * @inheritDoc
     */
    public function can_refund_order( $order ) {
        if(method_exists($this->adapter, 'can_refund_order')) {
            return $this->adapter->can_refund_order( $order);
        }
        return parent::can_refund_order( $order);
    }

}
