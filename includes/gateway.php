<?php

class Monri_WC_Gateway extends WC_Payment_Gateway {

	protected $adapter;

	function __construct()
	{
		$this->id = 'monri';

		$this->method_title = __('Monri', 'monri');
		$this->method_description = __('Monri Payment Gateway Plug-in for WooCommerce', 'monri');

		//$this->title = ;

		// If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
		//$this->icon = null;

		// This basically defines your settings which are then loaded with init_settings()
		$this->init_form_fields();

		// After init_settings() is called, you can get the settings and load them into variables, e.g:
		//$this->title = $this->get_option( 'title' );
		$this->init_settings();

		//$this->init_options();

		/*
		foreach ($this->settings as $setting_key => $value) {
			$this->$setting_key = $value;
		}
		*/

		// Define user set variables.
		// The title to be used for the vertical tabs that can be ordered top to bottom

		$this->title = $this->settings['title'] ?? __('Monri', 'monri');
		$this->description = $this->settings['description'];
		//$this->instructions = $this->get_option('instructions');

		//add_option('woocommerce_pay_page_id', $page_id);

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
			return $this->adapter->payment_fields();
		}
		return parent::payment_fields();
	}

	function init_form_fields()
	{
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

		$form_id = 'monri';

		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', $form_id),
				'type' => 'checkbox',
				'label' => __('Enable Monri', $form_id),
				'default' => 'no'
			),
			'monri_payment_gateway_service' => array(
				'title' => __('Payment Gateway Service:', $form_id),
				'type' => 'select',
				'class' => 'wc-enhanced-select',
				'default' => 'monri-web-pay',
				'description' => __('', $form_id),
				'options' => $payment_gateway_services,
				'desc_tip' => true,
			),
			'title' => array(
				'title' => __('Title', $form_id),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', $form_id),
				'desc_tip' => true,
				'default' => __('Monri', $form_id)
			),
			'description' => array(
				'title' => __('Description', $form_id),
				'type' => 'textarea',
				'description' => __('This controls the description which the user sees during checkout.', $form_id),
				'default' => __('Description for Monri', $form_id)
			),
			'instructions' => array(
				'title' => __('Instructions', $form_id),
				'type' => 'textarea',
				'description' => __('Instructions that will be added to the thank you page.', $form_id),
				'default' => __('Instructions for Monri.', $form_id)
			),
			/*
			'thankyou_page' => array(
				'title' => __('Success page', $form_id),
				'type' => 'text',
				'description' => __('Success URL potrebno je kopirati u Monri Account na predviđeno mjesto! ', $form_id),
				'desc_tip' => true,
				'default' => __(wc_get_checkout_url() . get_option('woocommerce_checkout_order_received_endpoint', 'order-received'), $form_id)
			),
			'callback_url_endpoint' => array(
				'title' => __('Callback URL endpoint', 'wcwcGpg1'),
				'type' => 'text',
				'description' => __('Monri Callback URL endpoint koji će primati POST zahtjev sa Monri Gateway-a.', $form_id),
				'desc_tip' => true,
				'default' => '/monri-callback',
				$form_id,
			),
			'success_url_override' => array(
				'title' => __('Success URL override', 'wcwcGpg1'),
				'type' => 'text',
				'description' => __('Success URL koji želite koristiti pri svakoj transakciji. (HTTPS)', $form_id),
				'desc_tip' => true,
				'default' => '',
				$form_id,
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'cancel_url_override' => array(
				'title' => __('Cancel URL override', 'wcwcGpg1'),
				'type' => 'text',
				'description' => __('Cancel URL koji želite koristiti pri svakoj transakciji. (HTTPS)', $form_id),
				'desc_tip' => true,
				'default' => '',
				$form_id,
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'callback_url_override' => array(
				'title' => __('Callback URL override', 'wcwcGpg1'),
				'type' => 'text',
				'description' => __('Callback URL koji želite koristiti pri svakoj transakciji. (HTTPS)', $form_id),
				'desc_tip' => true,
				'default' => '',
				$form_id,
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			*/
			'monri_merchant_key' => array(
				'title' => __('Monri Key', $form_id),
				'type' => 'text',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => __('', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'monri_authenticity_token' => array(
				'title' => __('Monri authenticity token', $form_id),
				'type' => 'text',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => __('', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			),
			'monri_ws_pay_form_shop_id' => array(
				'title' => __('Monri WsPay Form ShopId', $form_id),
				'type' => 'text',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => __('', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_secret' => array(
				'title' => __('Monri WsPay Form Secret', $form_id),
				'type' => 'text',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => __('', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_tokenization_enabled' => array(
				'title' => __('Monri WsPay Form Tokenization Enabled', $form_id),
				'type' => 'checkbox',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => 'no',
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_tokenization_shop_id' => array(
				'title' => __('Monri WsPay Form Tokenization ShopId', $form_id),
				'type' => 'text',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => __('', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_ws_pay_form_tokenization_secret' => array(
				'title' => __('Monri WsPay Form Tokenization Secret', $form_id),
				'type' => 'text',
				'description' => __('', $form_id),
				'desc_tip' => true,
				'default' => __('', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-ws-pay-option'
			),
			'monri_web_pay_integration_type' => array(
				'title' => __('Integration type', $form_id),
				'type' => 'select',
				'class' => 'wc-enhanced-select woocommerce-monri-dynamic-option monri-web-pay-option',
				'default' => true,
				'description' => __('', $form_id),
				'options' => $integration_types,
				'desc_tip' => true,
			),
			'test_mode' => array(
				'title' => __('Test mode', $form_id),
				'type' => 'select',
				'class'    => 'wc-enhanced-select',
				'default' => 0,
				'description' => __('', $form_id),
				'options' => $yes_or_no,
				'desc_tip' => true,
			),
			'transaction_type' => array(
				'title' => __('Transaction type', $form_id),
				'type' => 'select',
				'class'    => 'wc-enhanced-select',
				'default' => 0,
				'description' => __('', $form_id),
				'options' => $transaction_type,
				'desc_tip' => true
			),
			'form_language' => array(
				'title' => __('Form language', $form_id),
				'type' => 'select',
				'class'    => 'wc-enhanced-select',
				'default' => 'EN',
				'description' => __('', $form_id),
				'options' => $form_language,
				'desc_tip' => true,
			),
			'paying_in_installments' => array(
				'title' => __('Allow paying in installments', $form_id),
				'type' => 'select',
				'class' => 'wc-enhanced-select woocommerce-monri-dynamic-option monri-web-pay-option',
				'default' => 0,
				'description' => __('', $form_id),
				'options' => $yes_or_no,
				'desc_tip' => true,
			),
			'number_of_allowed_installments' => array(
				'title' => __('Number of allowed installments', $form_id),
				'type' => 'select',
				'class' => 'wc-enhanced-select woocommerce-monri-dynamic-option monri-web-pay-option',
				'default' => 0,
				'description' => __('', $form_id),
				'options' => $number_of_allowed_installments,
				'desc_tip' => true,
			),
			'bottom_limit' => array(
				'title' => __('Price limit for paying in installments:', $form_id),
				'type' => 'text',
				'description' => __('This controls the bottom price limit on which the installments can be used.', $form_id),
				'desc_tip' => true,
				'default' => __('0', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			)
		);

		for ($i=2; $i <= 24; $i++) {
			$this->form_fields["price_increase_$i"] = array(
				'title' => __("Price increase when paying in $i installments", $form_id),
				'type' => 'text',
				'description' => __('This controls the price increase when paying with installments.', $form_id),
				'desc_tip' => true,
				'default' => __('0', $form_id),
				'class' => 'woocommerce-monri-dynamic-option monri-web-pay-option'
			);
		}
	}

	public function admin_options()
	{
		parent::admin_options();

		echo '<script>
    (function () {
        updateOptions(jQuery("#woocommerce_monri_monri_payment_gateway_service").val())
    })()
    
    function updateOptions(value) {
        jQuery(".woocommerce-monri-dynamic-option").parents("tr").hide()
        if (value === "monri-ws-pay") {
            jQuery(\'.woocommerce-monri-dynamic-option.monri-web-pay-option\').parents(\'tr\').hide()
            jQuery(\'.woocommerce-monri-dynamic-option.monri-ws-pay-option\').parents(\'tr\').show()
        } else if (value === "monri-web-pay") {
            jQuery(\'.woocommerce-monri-dynamic-option.monri-web-pay-option\').parents(\'tr\').show()
            jQuery(\'.woocommerce-monri-dynamic-option.monri-ws-pay-option\').parents(\'tr\').hide()
        }
    }
    
    jQuery("#woocommerce_monri_monri_payment_gateway_service").on("change", function (e) {
        updateOptions(e.target.value)
    })
</script>';

	}

	public function get_option_bool( $key ) {
		return in_array( $this->get_option( $key ),  array( 'yes', '1', true ), true );
	}

	// is enabled?
	public function is_availableXX( $key ) {

	}
}
