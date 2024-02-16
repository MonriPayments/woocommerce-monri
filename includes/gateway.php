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

		// Lets check for SSL
		//add_action('admin_notices', array($this, 'do_ssl_check'));

		require_once __DIR__ . '/gateway-adapter-webpay-form.php';
		require_once __DIR__ . '/gateway-adapter-webpay-components.php';

		// resolve adapter based on settings
		if ($this->get_option('monri_payment_gateway_service') === 'monri-ws-pay') {
			$this->adapter = new Monri_WC_Gateway_Adapter_Wspay();
		} elseif ($this->get_option('monri_payment_gateway_service') === 'monri-web-pay' &&
		          $this->get_option('monri_web_pay_integration_type') === 'components'
		) {
			$this->adapter = new Monri_WC_Gateway_Adapter_Webpay_Components();
		} else {
			$this->adapter = new Monri_WC_Gateway_Adapter_Webpay_Form();
		}

		$this->adapter->init($this);

		// adapter can change this, inherit from adapter
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
		$yes_or_no = array(
			"0" => 'No',
			"1" => 'Yes'
		);

		$integration_types = array(
			"form" => 'Form',
			"components" => 'Components'
		);

		$transaction_type = array(
			"0" => "Purchase",
			"1" => "Authorize"
		);

		$number_of_allowed_installments = array(
			"24" => "24",
			"12" => "12",
			"6" => "6"
		);

		$form_language = array(
			"en" => "English",
			"de" => "German",
			"ba-hr" => "Bosanski",
			"hr" => "Hrvatski",
			"sr" => "Srpski"
		);

		$payment_gateway_services = array(
			"monri-web-pay" => "Monri WebPay",
			"monri-ws-pay" => "Monri WSPay"
		);

		$domain = 'monri';

		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', $domain),
				'type' => 'checkbox',
				'label' => __('Enable Monri', $domain),
				'default' => 'no'
			),
			'monri_payment_gateway_service' => array(
				'title' => __('Payment Gateway Service:', $domain),
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'default' => 'monri-web-pay',
				'description' => __('', $domain),
				'options' => $payment_gateway_services,
				'desc_tip' => true,
			),
			'title' => array(
				'title' => __('Title', $domain),
				'type' => 'text',
				'description' => __('Title which the customer sees during checkout.', $domain),
				'desc_tip' => true,
				'default' => __('Monri', $domain)
			),
			'description' => array(
				'title' => __('Description', $domain),
				'type' => 'textarea',
				'description' => __('Description which the customer sees during checkout.', $domain),
				'default' => __('Description for Monri', $domain)
			),
			'instructions' => array(
				'title' => __('Instructions', $domain),
				'type' => 'textarea',
				'description' => __('Instructions that will be added to the thank you page.', $domain),
				'default' => __('Instructions for Monri.', $domain)
			),
			'monri_merchant_key' => array(
				'title' => __('Monri Key', $domain),
				'type' => 'text',
				'description' => __('', $domain),
				'desc_tip' => true,
				'default' => __('', $domain),
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'monri_authenticity_token' => array(
				'title' => __('Monri authenticity token', $domain),
				'type' => 'text',
				'description' => __('', $domain),
				'desc_tip' => true,
				'default' => __('', $domain),
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'monri_ws_pay_form_shop_id' => array(
				'title' => __('Monri WsPay Form ShopId', $domain),
				'type' => 'text',
				'description' => __('', $domain),
				'desc_tip' => true,
				'default' => __('', $domain),
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_secret' => array(
				'title' => __('Monri WsPay Form Secret', $domain),
				'type' => 'text',
				'description' => __('', $domain),
				'desc_tip' => true,
				'default' => __('', $domain),
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_tokenization_enabled' => array(
				'title' => __('Monri WsPay Form Tokenization Enabled', $domain),
				'type' => 'checkbox',
				'description' => __('', $domain),
				'desc_tip' => true,
				'default' => 'no',
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_tokenization_shop_id' => array(
				'title' => __('Monri WsPay Form Tokenization ShopId', $domain),
				'type' => 'text',
				'description' => __('', $domain),
				'desc_tip' => true,
				'default' => __('', $domain),
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_tokenization_secret' => array(
				'title' => __('Monri WsPay Form Tokenization Secret', $domain),
				'type' => 'text',
				'description' => __('', $domain),
				'desc_tip' => true,
				'default' => __('', $domain),
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_web_pay_integration_type' => array(
				'title' => __('Integration type', $domain),
				'type' => 'select',
				'class' => 'wc-enhanced-select woocommerce-monri-dynamic-option monri-web-pay-option',
				'default' => true,
				'description' => __('', $domain),
				'options' => $integration_types,
				'desc_tip' => true,
			),
			'test_mode' => array(
				'title' => __('Test mode', $domain),
				'type' => 'select',
				'class'    => 'wc-enhanced-select',
				'default' => 0,
				'description' => __('', $domain),
				'options' => $yes_or_no,
				'desc_tip' => true,
			),
			'transaction_type' => array(
				'title' => __('Transaction type', $domain),
				'type' => 'select',
				'class'    => 'wc-enhanced-select',
				'default' => 0,
				'description' => __('', $domain),
				'options' => $transaction_type,
				'desc_tip' => true
			),
			'form_language' => array(
				'title' => __('Form language', $domain),
				'type' => 'select',
				'class'    => 'wc-enhanced-select',
				'default' => 'EN',
				'description' => __('', $domain),
				'options' => $form_language,
				'desc_tip' => true,
			),
			'paying_in_installments' => array(
				'title' => __('Allow paying in installments', $domain),
				'type' => 'select',
				'class' => 'wc-enhanced-select woocommerce-monri-dynamic-option monri-web-pay-option',
				'default' => 0,
				'description' => __('', $domain),
				'options' => $yes_or_no,
				'desc_tip' => true,
			),
			'number_of_allowed_installments' => array(
				'title' => __('Number of allowed installments', $domain),
				'type' => 'select',
				'class' => 'wc-enhanced-select woocommerce-monri-dynamic-option monri-web-pay-option',
				'default' => 0,
				'description' => __('', $domain),
				'options' => $number_of_allowed_installments,
				'desc_tip' => true,
			),
			'bottom_limit' => array(
				'title' => __('Price limit for paying in installments:', $domain),
				'type' => 'text',
				'description' => __('This controls the bottom price limit on which the installments can be used.', $domain),
				'desc_tip' => true,
				'default' => __('0', $domain),
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			)
		);

		for ($i = 2; $i <= 24; $i++) {
			$this->form_fields["price_increase_$i"] = array(
				'title' => __("Price increase when paying in $i installments", $domain),
				'type' => 'text',
				'description' => __('This controls the price increase when paying with installments.', $domain),
				'desc_tip' => true,
				'default' => __('0', $domain),
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			);
		}
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
