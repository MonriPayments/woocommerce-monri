<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var array $config */
/** @var array $installments */
/** @var array $tokenization */
?>

<p id="monri-status">
	<?php esc_html_e('After payment, please wait a moment while we verify your transaction...', 'monri'); ?>
</p>
<div id="ips-otp-element"></div>
<p id="monri-error" style="color:red;" role="alert"></p>
<input type="hidden" id="monri-transaction" name="monri-transaction" autocomplete="off" value=""/>

<script type="text/javascript">
    (function($) {
        var config = <?php echo wp_json_encode( $config ); ?>;
        var monri = Monri(config.authenticity_token, {locale: config.locale});
        var components = monri.components({clientSecret: config.client_secret});

        var style = {invalid: {color: 'red'}};

        var ipsRs = components.create('ips-otp', {
            style,
            trx_token: config.client_secret,
            environment: config.env,
        })

        ipsRs.mount('ips-otp-element');

        window.addEventListener('message', (event) => {
            if (event.data.type === 'PAYMENT_RESULT') {
                const {transaction} = event.data;
                if (transaction.status === 'approved') {
                    window.location.href = config.return_url;
                } else {
                    $('#monri-error').text( "<?php esc_html_e('Transaction declined, please reload the page.', 'monri'); ?>" );
                }
            }
        });
    })(jQuery);

</script>
