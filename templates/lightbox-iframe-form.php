<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/** @var array $config */
?>
<form action="" id="monri-lightbox-form">
</form>

<script>
    (function($) {
        function collectBrowserInfo(ip_address) {
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
                http_accept_language: language || '*',
                ip: ip_address || ''
            };
        }

        $('form.checkout').on('checkout_place_order_success', function (t, result) {
            var selectedGateway = $('input[name="payment_method"]:checked').val();
            if (selectedGateway !== 'monri') return;

            let script = document.createElement('script');
            script.src = result['src'];
            script.className = "lightbox-button";

            script.setAttribute('data-authenticity-token', result['data-authenticity-token']);
            script.setAttribute('data-amount', result['data-amount']);
            script.setAttribute('data-currency', result['data-currency']);
            script.setAttribute('data-order-number', result['data-order-number']);
            script.setAttribute('data-order-info', result['data-order-info']);
            script.setAttribute('data-digest', result['data-digest']);
            script.setAttribute('data-transaction-type', result['data-transaction-type']);
            script.setAttribute('data-language', result['data-language']);
            script.setAttribute('data-success-url-override', result['data-success-url-override']);
            script.setAttribute('data-cancel-url-override', result['data-cancel-url-override']);
            script.setAttribute('data-callback-url-override', result['data-callback-url-override']);
            script.setAttribute('data-ch-full-name', result['data-ch-full-name']);
            script.setAttribute('data-ch-zip', result['data-ch-zip']);
            script.setAttribute('data-ch-phone', result['data-ch-phone']);
            script.setAttribute('data-ch-email', result['data-ch-email']);
            script.setAttribute('data-ch-address', result['data-ch-address']);
            script.setAttribute('data-ch-city', result['data-ch-city']);
            script.setAttribute('data-ch-country', result['data-ch-country']);
            script.setAttribute('data-browser-info', JSON.stringify(collectBrowserInfo(result['data-ip'])));

            if( result['data-number-of-installments'] ) {
                script.setAttribute('data-number-of-installments', result['data-number-of-installments']);
            }

            if( result['data-supported-payment-methods'] ) {
                script.setAttribute('data-supported-payment-methods', result['data-supported-payment-methods']);
            }

            if( result['data-tokenize-pan'] ) {
                script.setAttribute('data-tokenize-pan', result['data-tokenize-pan']);
            }

            script.onload = function() {
                $('button.monri-lightbox-button-el').click();
            }

            script.onerror = function() {
                console.log('something went wrong');
            }

            document.querySelector('#monri-lightbox-form').appendChild(script);
            return false;
        });


    })(jQuery);


</script>