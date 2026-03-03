<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var array $config */
/** @var array $installments */
/** @var array $tokenization */
?>

<div id="monri-components"></div>
<p id="monri-error" style="color:red;" role="alert"></p>
<input type="hidden" id="monri-transaction" name="monri-transaction" autocomplete="off" value=""/>

<script type="text/javascript">
	(function($) {

        var monri = Monri('<?php echo esc_js( $config['authenticity_token'] ) ?>', {locale: '<?php echo esc_js( $config['locale'] ) ?>'});
        var components = monri.components({clientSecret: '<?php echo esc_js( $config['client_secret'] ) ?>'});

        var style = {invalid: {color: 'red'}};

        var card = components.create('card',
            {style: style
                <?php if( $installments ): ?>, showInstallmentsSelection: true<?php endif ?>
	            <?php if( $tokenization ): ?>, tokenizePanOffered: true<?php endif ?>
            });
        card.mount('monri-components');

        card.onChange(function (event) {
            if (event.error) {
                $('#monri-error').text(event.error.message);
            } else {
                $('#monri-error').empty();
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
            var time_zone_offset = d.getTimezoneOffset();

            return {
                screen_width: screen_width,
                screen_height: screen_height,
                color_depth: color_depth,
                user_agent: user_agent,
                time_zone_offset: time_zone_offset,
                language: language,
                java_enabled: java_enabled,
                http_accept: '*/*',
                http_user_agent: user_agent,
                http_accept_language: language || '*'
            };
        }

        $('form.checkout').on('checkout_place_order_monri', function () {
            if ($('#monri-transaction').val()) {
                return true;
            }

            // Needed to skip order placement, just do validation of fields
            let formData = $('form.checkout').serializeArray();
            formData.push({name: 'woocommerce_checkout_update_totals', value: '1'});
            formData.push({name: 'monri_components_checkout_validation', value: '1'});

            let url = wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'checkout' )
            let response;
            $.ajax({
                type: 'POST',
                url: url,
                data: $.param(formData),
                success: function (result) {
                    response = result
                },
                async:false
            });

            // Order placement is skipped, so failure is always returned. However, if messages is empty, validation has passed
            if (response.result === 'failure' && response.messages) {
                return;
            }
            const browser_info = collectBrowserInfo();
            browser_info.ip = '<?php echo esc_js( $config['ip_address'] ) ?>';

            const transactionParams = {
                address: $('#billing_address_1').val(),
                fullName: $('#billing_first_name').val() + ' ' + $('#billing_last_name').val(),
                city: $('#billing_city').val(),
                zip: $('#billing_postcode').val(),
                phone: $('#billing_phone').val(),
                country: $('#billing_country').val(),
                email: $('#billing_email').val(),
                browser_info: browser_info
            }

            console.log('transactionParams: ', transactionParams)
            monri.confirmPayment(card, transactionParams).then(function (response) {
                if (response.error) {
                    $('#monri-error').text(response.error.message);
                    return;
                }

				// handle declined on 3DS Cancel
				if (response.result.status === 'approved') {
                    $('#monri-transaction').val(JSON.stringify(response.result));
                    $('form.checkout').submit();
				} else {
					$('#monri-error').text( "<?php esc_html_e('Transaction declined, please reload the page.', 'monri'); ?>" );
				}
            });

			return false;
        });

    })(jQuery);

</script>
