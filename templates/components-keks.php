<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var array $config */
/** @var array $installments */
/** @var array $tokenization */
?>

<p id="monri-status">
    <?php esc_html_e('After payment, please wait a moment while we verify your transaction...', 'monri'); ?>
</p>
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

        function getOrderStatus() {
            $.ajax({
                url: '/wp-json/monri/v1/transaction-status/' + encodeURIComponent(config.order_number),
                method: 'GET',
                data: {
                    order_hash: config.order_hash
                },
                success: function(response) {
                    console.log(response)
                    if (response) {
                        window.location.href = config.return_url;
                    }
                },
            });
        }

        setInterval(getOrderStatus, 3000);

    })(jQuery);

</script>
