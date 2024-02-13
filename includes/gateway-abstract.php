<?php

abstract class Monri_WC_Gateway extends WC_Payment_Gateway {

	public function get_option_key() {
		return $this->plugin_id . 'monri_settings';
	}

	public function get_field_key( $key ) {
		return $this->plugin_id . 'monri _settings';
	}

	public function admin_optionsxx()
	{
		echo '<h3>' . __('Monri Payment Gateway', 'Monri') . '</h3>';
		echo '<table class="form-table">';
		// Generate the HTML For the settings form.
		$this->generate_settings_html();
		echo '</table>';
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
}
