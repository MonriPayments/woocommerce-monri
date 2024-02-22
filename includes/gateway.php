<?php

class Monri_WC_Gateway extends WC_Payment_Gateway {

	protected $adapter;

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
			$this->adapter = new Monri_WC_Gateway_Adapter_Wspay();
		} elseif ($this->get_option('monri_payment_gateway_service') === 'monri-web-pay' &&
		          $this->get_option('monri_web_pay_integration_type') === 'components'
		) {
			require_once __DIR__ . '/gateway-adapter-webpay-components.php';
			$this->adapter = new Monri_WC_Gateway_Adapter_Webpay_Components();
		} else {
			require_once __DIR__ . '/gateway-adapter-webpay-form.php';
			$this->adapter = new Monri_WC_Gateway_Adapter_Webpay_Form();
		}

		$this->adapter->init($this);

        require_once __DIR__ . '/callback.php';
        $callback = new Monri_WC_Callback();
        $callback->init();

		// adapter can change this, or inherit from adapter?
		//$this->has_fields = $this->adapter->has_fields ?? false;

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
	}

	public function process_payment( $order_id ) {
		if(method_exists($this->adapter, 'process_payment')) {
			return $this->adapter->process_payment( $order_id );
		}
		return parent::process_payment( $order_id );
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		if(method_exists($this->adapter, 'process_refund')) {
			return $this->adapter->process_refund( $order_id, $amount, $reason );
		}
		return parent::process_refund( $order_id, $amount, $reason );
	}

	public function validate_fields() {
		if(method_exists($this->adapter, 'validate_fields')) {
			return $this->adapter->validate_fields();
		}
		return parent::validate_fields();
	}

	public function payment_fields() {
		if(method_exists($this->adapter, 'payment_fields')) {
			$this->adapter->payment_fields();
		}
		parent::payment_fields();
	}

	public function init_form_fields() {
		$this->form_fields =  Monri_WC_Settings::instance()->get_form_fields();
	}

	public function admin_options()
	{
		parent::admin_options();

		echo <<<JS
<script>
    (function () {
        updateOptions(jQuery("#woocommerce_monri_monri_payment_gateway_service").val())
    })()
    
    function updateOptions(value) {
        jQuery(".woocommerce-monri-dynamic-option").parents("tr").hide()
        if (value === "monri-ws-pay") {
            jQuery('.woocommerce-monri-dynamic-option.monri-web-pay-option').parents('tr').hide()
            jQuery('.woocommerce-monri-dynamic-option.monri-ws-pay-option').parents('tr').show()
        } else if (value === "monri-web-pay") {
            jQuery('.woocommerce-monri-dynamic-option.monri-web-pay-option').parents('tr').show()
            jQuery('.woocommerce-monri-dynamic-option.monri-ws-pay-option').parents('tr').hide()
        }
    }
    
    jQuery("#woocommerce_monri_monri_payment_gateway_service").on("change", function (e) {
        updateOptions(e.target.value)
    })
</script>
JS;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function get_option_bool( $key ) {
		return in_array( $this->get_option( $key ),  array( 'yes', '1', true ), true );
	}

	// is enabled?
	public function is_availableXX( $key ) {

	}
}
