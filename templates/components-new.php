<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var array $config */
?>

<div id="card-element"></div>
<p id="monri-error" style="color:red;" role="alert"></p>
<p id="monri-status" style="display:none;">
    <?php esc_html_e( 'Please wait a moment while we verify your transaction...', 'monri' ); ?>
</p>
<button type="button" id="monri-pay-button" class="button alt">
    <?php esc_html_e( 'Pay for order', 'monri' ); ?>
</button>

<script type="text/javascript">
    (function($) {
        var config = <?php echo wp_json_encode( $config ); ?>;

        var monri = Monri(config.authenticity_token, {locale: config.locale});
        var components = monri.components({clientSecret: config.client_secret});

        var style = {invalid: {color: 'red'}};

        var card = components.create('card', {style: style});

        card.mount('card-element');

        var $error = $('#monri-error');
        var $status = $('#monri-status');
        var $button = $('#monri-pay-button');

        card.onChange(function (event) {
            if (event.error) {
                $error.text(event.error.message);
            } else {
                $error.empty();
            }
        });

        function collectBrowserInfo() {
            var screen_width = window && window.screen ? window.screen.width : '';
            var screen_height = window && window.screen ? window.screen.height : '';
            var color_depth = window && window.screen ? window.screen.colorDepth : '';
            var user_agent = window && window.navigator ? window.navigator.userAgent : '';
            var java_enabled = window && window.navigator ? navigator.javaEnabled() : false;

            var language = '';
            if (window && window.navigator) {
                language = window.navigator.language
                    ? window.navigator.language
                    : window.navigator.browserLanguage || '';
            }

            var d = new Date();

            return {
                screen_width: screen_width,
                screen_height: screen_height,
                color_depth: color_depth,
                user_agent: user_agent,
                time_zone_offset: d.getTimezoneOffset(),
                language: language,
                java_enabled: java_enabled,
                http_accept: '*/*',
                http_user_agent: user_agent,
                http_accept_language: language || '*',
                ip: config.ip_address
            };
        }

        function paymentFailed(message) {
            $error.text(message);
            $status.hide();
            $button.prop('disabled', false);
        }

        // The order already exists here, so billing data comes from the order and not from
        // the checkout form. This only confirms the transaction - the order itself is
        // completed by the Monri callback, or by process_return() on the thank you page.
        $button.on('click', function () {
            $error.empty();
            $status.show();
            $button.prop('disabled', true);

            var transactionParams = {
                fullName: config.ch_full_name,
                address: config.ch_address,
                city: config.ch_city,
                zip: config.ch_zip,
                phone: config.ch_phone,
                country: config.ch_country,
                email: config.ch_email,
                browser_info: collectBrowserInfo()
            };

            monri.confirmPayment(card, transactionParams).then(function (response) {
                if (response.error) {
                    paymentFailed(response.error.message);
                    return;
                }

                // handle declined on 3DS cancel
                if (response.result && response.result.status === 'approved') {
                    window.location.href = config.return_url;
                } else {
                    paymentFailed(<?php echo wp_json_encode( __( 'Transaction declined, please try again.', 'monri' ) ); ?>);
                }
            }).catch(function (error) {
                paymentFailed(
                    error && error.message ?
                        error.message :
                        <?php echo wp_json_encode( __( 'Payment could not be processed, please try again.', 'monri' ) ); ?>
                );
            });
        });

    })(jQuery);

</script>
