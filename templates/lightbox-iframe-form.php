<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/** @var array $config */
?>
<form action="">
</form>


<script>
    (function($) {
        const transactionParams = {
            address: $('#billing_address_1').val(),
            fullName: $('#billing_first_name').val() + ' ' + $('#billing_last_name').val(),
            city: $('#billing_city').val(),
            zip: $('#billing_postcode').val(),
            phone: $('#billing_phone').val(),
            country: $('#billing_country').val(),
            email: $('#billing_email').val()
        }
        console.log('transaction params: ', transactionParams);
        let script = document.createElement('script');
        script.src = "<?php echo esc_url($config['src']); ?>";
        script.className = "lightbox-button";

        script.setAttribute('data-authenticity-token', "<?php echo esc_attr($config['data-authenticity-token']); ?>");
        script.setAttribute('data-amount', "<?php echo esc_attr($config['data-amount']); ?>");
        script.setAttribute('data-currency', "<?php echo esc_attr($config['data-currency']); ?>");
        script.setAttribute('data-order-number', "<?php echo esc_attr($config['data-order-number']); ?>");
        script.setAttribute('data-order-info', "<?php echo esc_attr($config['data-order-info']); ?>");
        script.setAttribute('data-digest', "<?php echo esc_attr($config['data-digest']); ?>");
        script.setAttribute('data-transaction-type', "<?php echo esc_attr($config['data-transaction-type']); ?>");
        script.setAttribute('data-language', "<?php echo esc_attr($config['data-language']); ?>");
        script.setAttribute('data-success-url-override', "<?php echo esc_attr($config['data-success-url-override']); ?>");
        script.setAttribute('data-ch-full-name', transactionParams.fullName);
        script.setAttribute('data-ch-zip', transactionParams.zip);
        script.setAttribute('data-ch-phone', transactionParams.phone);
        script.setAttribute('data-ch-email', transactionParams.email);
        script.setAttribute('data-ch-address', transactionParams.address);
        script.setAttribute('data-ch-city', transactionParams.city);
        script.setAttribute('data-ch-country', transactionParams.country);


        document.querySelector('form').appendChild(script);
    })(jQuery);


</script>