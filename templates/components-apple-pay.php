<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var array $config */
/** @var array $installments */
/** @var array $tokenization */
?>

<p id="monri-status">
    <?php esc_html_e('After payment, please wait a moment while we verify your transaction...', 'monri'); ?>
</p>
<div id="apple-pay-element"></div>
<p id="monri-error" style="color:red;" role="alert"></p>
<input type="hidden" id="monri-transaction" name="monri-transaction" autocomplete="off" value=""/>

<script type="text/javascript">
    (function($) {
        var config = <?php echo wp_json_encode( $config ); ?>;
        var transaction = {
            ch_full_name: config.ch_full_name,
            ch_address: config.ch_address,
            ch_city: config.ch_city,
            ch_zip: config.ch_zip,
            ch_phone: config.ch_phone,
            ch_country: config.ch_country,
            ch_email: config.ch_email,
            ch_language: config.locale,
        }
        console.log(transaction);

        var monri = Monri(config.authenticity_token, {locale: config.locale});
        var components = monri.components({clientSecret: config.client_secret});

        var style = {invalid: {color: 'red'}};

        var applePay = components.create('apple-pay', {
            style,
            trx_token: config.client_secret,
            environment: config.env,
            transaction,
        })

        applePay.mount('apple-pay-element');
        console.log("applePay: ", applePay);

        function getOrderStatus() {
            $.ajax({
                url: '/wp-json/monri/v1/transaction-status/' + encodeURIComponent(config.order_number),
                method: 'GET',
                data: {
                    order_hash: config.order_hash
                },
                success: function(response) {
                    if (response) {
                        window.location.href = config.return_url;
                    }
                },
            });
        }

        setInterval(getOrderStatus, 3000);

    })(jQuery);

</script>
