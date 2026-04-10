<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/** @var array $config */
?>
<form action="" id="monri-lightbox-form">
</form>

<script>
    (function($) {
        function handlePaymentResponse() {
            const queryParams = new URLSearchParams(window.location.search);
            const transactionResponse = queryParams.get("transaction_response");

            if (transactionResponse) {
                try {
                    const decodedResponse = decodeURIComponent(transactionResponse);
                    const response = JSON.parse(decodedResponse);

                    if (response.status === "approved") {
                        return true;
                    } else {
                        console.log("Plaćanje nije uspjelo", response);
                    }
                } catch (error) {
                    console.error("Greška pri parsiranju odgovora na transakciju:", error);
                }
            }
        }

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

        function clickMonriLightboxButton() {
            var button = document.querySelector('button.monri-lightbox-button-el');
            button.style.display = 'none';
            button.click();
        }

        $('form.checkout').on('checkout_place_order_success', function (t, result) {

            if (handlePaymentResponse()) {
                return true;
            }
            var selectedGateway = $('input[name="payment_method"]:checked').val();
            if (selectedGateway !== 'monri') return;

            // do not append new script if old one already exists, just trigger click on the button to open lightbox
            var existingScript = document.getElementById('monri-lightbox-loader');
            if (existingScript) {
                clickMonriLightboxButton();
                return false;
            }

            let script = document.createElement('script');
            script.id = 'monri-lightbox-loader';
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
                clickMonriLightboxButton();
            }

            script.onerror = function() {
                console.log('something went wrong');
            }

            document.querySelector('#monri-lightbox-form').appendChild(script);
            return false;
        });


    })(jQuery);


</script>