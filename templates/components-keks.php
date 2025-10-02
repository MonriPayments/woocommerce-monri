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
        var config = <?php echo wp_json_encode( $config ); ?>;
        var monri = Monri(config.authenticity_token, {locale: config.locale});
        var components = monri.components({clientSecret: config.client_secret});

        var style = {invalid: {color: 'red'}};

        var keksPay = components.create('keks-pay', {
            style: style,
            trx_token: config.client_secret,
            environment: config.env
        })
        console.log('keksPay', keksPay);
        keksPay.mount('keks-pay-element');

        //todo: currently not working. https://docs.monri.com/docs/keks-pay
        // keksPay.on('paymentSuccess', (result) => {
        //     window.location.href = config.return_url;
        // });
        //
        // keksPay.on('paymentError', (error) => {
        //     $('#monri-error').text(error.message);
        // });

    })(jQuery);

</script>
