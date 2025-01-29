<?php

if ( ! defined( 'ABSPATH' ) ) exit;
/** @var array $config */
?>
<form action="" id="monri-lightbox-form">
    <p>lol</p>
</form>


<script>
    (function($) {
        console.log('lightbox hi');

        $('form.checkout').on('checkout_place_order_success', function (t, result) {
            // ako lighbox gotov return true; // nepotrebno

            console.log(result);

            // uzmi result, stvori js/lighbox, script on load => form.submit()

            // otvaramo lightbox

            //alert(1);
            //$('#monri-lightbox-form').submit();


            const transactionParams = {
                address: $('#billing_address_1').val(),
                fullName: $('#billing_first_name').val() + ' ' + $('#billing_last_name').val(),
                city: $('#billing_city').val(),
                zip: $('#billing_postcode').val(),
                phone: $('#billing_phone').val(),
                country: $('#billing_country').val(),
                email: $('#billing_email').val()
            }

            console.log('transaction params: ', transactionParams);
            console.log('result.order_id: ', result.order_id);
            console.log('digest: ', result.monri_data['data-digest'])
            let script = document.createElement('script');
            script.src = result.monri_data['src'];
            script.className = "lightbox-button";

            script.setAttribute('data-authenticity-token', result.monri_data['data-authenticity-token']);
            script.setAttribute('data-amount', result.monri_data['data-amount']);
            script.setAttribute('data-currency', result.monri_data['data-currency']);
            script.setAttribute('data-order-number', result.monri_data['data-order-number']);
            script.setAttribute('data-order-info', result.monri_data['data-order-info']);
            script.setAttribute('data-digest', result.monri_data['data-digest']);
            script.setAttribute('data-transaction-type', result.monri_data['data-transaction-type']);
            script.setAttribute('data-language', result.monri_data['data-language']);
            script.setAttribute('data-success-url-override', result.monri_data['data-success-url-override']);
            script.setAttribute('data-ch-full-name', transactionParams.fullName);
            script.setAttribute('data-ch-zip', transactionParams.zip);
            script.setAttribute('data-ch-phone', transactionParams.phone);
            script.setAttribute('data-ch-email', transactionParams.email);
            script.setAttribute('data-ch-address', transactionParams.address);
            script.setAttribute('data-ch-city', transactionParams.city);
            script.setAttribute('data-ch-country', transactionParams.country);

            script.onload = function() {
                //alert(2);
                $('button.monri-lightbox-button-el').click();

            }

            script.onerror = function() {
                alert(3);
            }

            document.querySelector('#monri-lightbox-form').appendChild(script);
            return false;
        });


    })(jQuery);


</script>