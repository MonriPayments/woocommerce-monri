<?php
/** @var array $config */
/** @var array $installments */

$installments_price_increase = false;
$installments = false;
?>

<?php if ($installments): ?>
<div id="monri-installments" class="monri-installments">

	<label for="monri-card-installments"><?php esc_html_e('Number of installments: ', 'monri') ?></label>
	<select id="monri-card-installments" name="monri-card-installments" class="input-text">
		<?php foreach ($installments as $installment): ?>
			<option value="<?php echo esc_attr( $installment['value'] ) ?>"
					<?php if ($installment['selected']): ?>selected<?php endif ?>
			><?php echo esc_html($installment['label']) ?></option>
			<?php $installments_price_increase = $installments_price_increase || ($installment['price_increase'] !== 0); ?>
		<?php endforeach; ?>
	</select>

	<?php if ($installments_price_increase): ?>
	<p>
		<?php esc_html_e('Fees may be applied for installments','monri') ?>
	</p>
	<?php endif; ?>
	<br/>
</div>

<?php endif; ?>

<div id="monri-components"></div>
<p id="monri-components-error" style="color:red;" role="alert"></p>
<input type="hidden" id="monri-transaction" name="monri-transaction" />

<?php if ($installments_price_increase): /* installments are changing total */ ?>
<script type="text/javascript">
    (function ($) {
        var previousPaymentMethod;
        $( document.body ).on( 'payment_method_selected', function() {
            var selectedPaymentMethod = $( '.woocommerce-checkout input[name="payment_method"]:checked' ).attr( 'id' );

            if(selectedPaymentMethod === 'payment_method_monri' || previousPaymentMethod === 'payment_method_monri') {
                jQuery( 'form.checkout' ).trigger('update_checkout');
            }

            previousPaymentMethod = selectedPaymentMethod;
        });

        $(document).on("change", "#monri-card-installments", function () {
            $( 'form.checkout' ).trigger('update_checkout');
        });

    })(jQuery);
</script>
<?php endif; ?>

<script type="text/javascript">
	(function($) {

        var monri = Monri('<?php echo esc_js( $config['authenticity_token'] ) ?>', {locale: '<?php echo esc_js( $config['locale'] ) ?>'});
        var components = monri.components({clientSecret: '<?php echo esc_js( $config['client_secret'] ) ?>'});

        var style = {invalid: {color: 'red'}};

        var card = components.create('card', {style: style, showInstallmentsSelection: true});
        card.mount('monri-components');

        card.onChange(function (event) {
            if (event.error) {
                $('#monri-components-error').text(event.error.message);
                $('#monri-token').val(''); // !!!!!!!!
            } else {
                $('#monri-components-error').empty();
            }
        });

        var selected = 1;
        card.addChangeListener('installments', function (event) {
            console.log(event);
            console.log(event.data)
            console.log(event.data.selectedInstallment)
            console.log(event.message)
            console.log(event.valid)

			if (selected !== event.data.selectedInstallment) {
                selected = event.data.selectedInstallment;
                //$( 'form.checkout' ).trigger('update_checkout');
			}

            //
        });

        $('form.checkout').on('checkout_place_order_monri', function () {
            if ($('#monri-transaction').val()) {
                return true;
            }

            const transactionParams = {
                address: $('#_billing_address_1').val(),
                fullName: $('#_billing_first_name').val() + ' ' + $('#_billing_last_name').val(),
                city: $('#_billing_city').val(),
                zip: $('#_billing_postcode').val(),
                phone: $('#_billing_phone').val(),
                country: $('#_billing_country').val(),
                email: $('#_billing_email').val(),
                orderInfo: "Testna trx"
            }

            console.log(transactionParams);

            monri.confirmPayment(card, transactionParams).then(function (response) {
                console.log(response);

                if (response.error) {
                    $('#monri-components-error').text(response.error.message);
                    return;
                }

				// handle declined on 3DS Cancel
				if (response.result.status === 'approved') {
                    $('#monri-transaction').val(JSON.stringify(response.result));
                    $('form.checkout').submit();

				} else {
					$('#monri-components-error').text('Transaction declined.');
				}

            });

			return false;
        });

    })(jQuery);

</script>
