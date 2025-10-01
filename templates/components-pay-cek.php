<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var array $config */
/** @var array $installments */
/** @var array $tokenization */
?>


<div id="pay-cek-element"></div>
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

        var monri = Monri(config.authenticity_token, {locale: config.locale});
        var components = monri.components({clientSecret: config.client_secret});

        var style = {invalid: {color: 'red'}};

        var payCek = components.create('pay-cek', {
            style: style,
            trx_token: config.client_secret,
            environment: config.env,
        }).onStartPayment(() => {
            monri.confirmPayment(payCek, transaction)
                .then(response => {
                    if (response.error) {
                        $('#monri-error').text(response.error.message);
                        return;
                    }
                    window.location.href = config.return_url;
                })
        })

        payCek.mount('pay-cek-element');

    })(jQuery);

</script>
