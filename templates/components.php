<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var array $config */
/** @var array $installments */
?>

<div id="monri-components"></div>
<p id="monri-error" style="color:red;" role="alert"></p>
<input type="hidden" id="monri-transaction" name="monri-transaction" autocomplete="off" value=""/>

<script type="text/javascript">
	(function($) {

        var monri = Monri('<?php echo esc_js( $config['authenticity_token'] ) ?>', {locale: '<?php echo esc_js( $config['locale'] ) ?>'});
        var components = monri.components({clientSecret: '<?php echo esc_js( $config['client_secret'] ) ?>'});

        var style = {invalid: {color: 'red'}};

        var card = components.create('card', {style: style<?php if( $installments ): ?>, showInstallmentsSelection: true<?php endif ?>});
        card.mount('monri-components');

        card.onChange(function (event) {
            if (event.error) {
                $('#monri-error').text(event.error.message);
            } else {
                $('#monri-error').empty();
            }
        });

        $('form.checkout').on('checkout_place_order_monri', function () {
            if ($('#monri-transaction').val()) {
                return true;
            }

            const transactionParams = {
                address: $('#billing_address_1').val(),
                fullName: $('#billing_first_name').val() + ' ' + $('#billing_last_name').val(),
                city: $('#billing_city').val(),
                zip: $('#billing_postcode').val(),
                phone: $('#billing_phone').val(),
                country: $('#billing_country').val(),
                email: $('#billing_email').val()
            }

            monri.confirmPayment(card, transactionParams).then(function (response) {
                //console.log(response);
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
