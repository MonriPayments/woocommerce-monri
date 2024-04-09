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

		// @todo: maybe not here?
        require_once __DIR__ . '/callback.php';
        $callback = new Monri_WC_Callback();
        $callback->init();
		//

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

		// add field dependency logic
		echo <<<JS
<script>
    (function ($) {
        
        var rules = {}, toListen = [];
        $('input[data-depends],textarea[data-depends],select[data-depends]').each(function(i, el) {
            rules[$(el).attr('id')] = {};
            var i, idName;
            for (i in $(el).data('depends')) {
                idName = '#woocommerce_monri_'+i;
                rules[$(el).attr('id')][idName] = $(el).data('depends')[i];
                if (!toListen.includes(idName)) {
                    toListen.push(idName);
                }
            }
        });
        
        var updateOptions = function() {
            var show, el;
            for(el in rules) {
                show = true;
                for (depends in rules[el]) {
                    if (!$(depends).parents('tr').is(':visible')) {
                        show = false; break;
                    }
                    
	                if ($(depends).val() != rules[el][depends]) {
	                    show = false; break;
	                }
                    
                    if ($(depends).attr('type') === 'checkbox' && !$(depends).is(':checked')) {
                        show = false; break;
                    }
                }
                show ? $('#'+el).parents('tr').show() : $('#'+el).parents('tr').hide();
            }
        }

        $(toListen.join(',')).on('change', updateOptions);
        updateOptions();
        
    })(jQuery)

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

}
