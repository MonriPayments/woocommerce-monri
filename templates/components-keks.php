<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var array $config */
/** @var array $installments */
/** @var array $tokenization */
?>

<div id="keks-pay-element"></div>
<p id="monri-error" style="color:red;" role="alert"></p>
<input type="hidden" id="monri-transaction" name="monri-transaction" autocomplete="off" value=""/>

<script type="text/javascript">
    (function($) {
        var monriKeks = Monri('<?php echo esc_js( $config['authenticity_token'] ) ?>', {locale: '<?php echo esc_js( $config['locale'] ) ?>'});
        var componentsKeks = monriKeks.components({clientSecret: '<?php echo esc_js( $config['client_secret'] ) ?>'});
        // Create an instance of the keks-pay Component.

        var style = {invalid: {color: 'red'}};

        var keksPay = componentsKeks.create('keks-pay', {
            style: style,
            trx_token: "<?php echo esc_js( $config['client_secret'] ) ?>",
            environment: "<?php echo esc_js( $config['is_test'] ) ?>",
        })
        // Add an instance of the keks-pay Component into the `keks-pay-element` <div>.
        keksPay.mount('keks-pay-element');

        keksPay.onChange(function (event) {
            var displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });


    })(jQuery);

</script>
