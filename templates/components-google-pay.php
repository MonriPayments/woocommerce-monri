<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var array $config */
/** @var array $installments */
/** @var array $tokenization */
?>


<div id="google-pay-element"></div>
<p id="monri-error" style="color:red;" role="alert"></p>
<input type="hidden" id="monri-transaction" name="monri-transaction" autocomplete="off" value=""/>

<script type="text/javascript">
    (function($) {
        var config = <?php echo wp_json_encode( $config ); ?>;
        var transaction = {
            ch_full_name: config.ch_full_name,
            ch_address: config.address,
            ch_city: config.city,
            ch_zip: config.zip,
            ch_phone: config.phone,
            ch_country: config.country,
            ch_email: config.email,
            ch_language: config.locale,
        }
        console.log(transaction);

        var monri = Monri('<?php echo esc_js( $config['authenticity_token'] ) ?>', {locale: '<?php echo esc_js( $config['locale'] ) ?>'});
        var components = monri.components({clientSecret: '<?php echo esc_js( $config['client_secret'] ) ?>'});

        var style = {invalid: {color: 'red'}};

        var googlePay = components.create('google-pay', {
            style: style,
            trx_token: "<?php echo esc_js( $config['client_secret'] ) ?>",
            environment: "<?php echo esc_js( $config['env'] ) ?>",
            transaction: transaction,
        })
        console.log('googlePay', googlePay);
        googlePay.mount('google-pay-element');

        googlePay.onChange(function (event) {
            console.log('onChange', event);
        });

    })(jQuery);

</script>
