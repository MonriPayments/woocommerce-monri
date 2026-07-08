<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var array $config */
/** @var array $installments */
/** @var array $tokenization */
?>

<p id="monri-status">
	<?php esc_html_e('After payment, please wait a moment while we verify your transaction...', 'monri'); ?>
</p>
<div id="flik-pay-element"></div>
<p id="monri-error" style="color:red;" role="alert"></p>
<input type="hidden" id="monri-transaction" name="monri-transaction" autocomplete="off" value=""/>

<script type="text/javascript">
    (function($) {
        var config = <?php echo wp_json_encode( $config ); ?>;
        var monri = Monri(config.authenticity_token, {locale: config.locale});
        var components = monri.components({clientSecret: config.client_secret});

        var style = {invalid: {color: 'red'}};

        var flikPay = components.create('flik-pay', {
            style,
            trx_token: config.client_secret,
            environment: config.env,
        })

        flikPay.mount('flik-pay-element');
    })(jQuery);

</script>
