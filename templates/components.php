<?php
/** @var array $config */
/** @var array $installments */

$installments_price_increase = false;
?>

<?php if ($installments): ?>
<div id="monri-installments" class="monri-installments">

	<label for="monri-card-installments"><?php esc_html_e(__('Number of installments: ', 'monri')) ?></label>
	<select id="monri-card-installments" name="monri-card-installments" class="input-text">
		<?php foreach ($installments as $installment): ?>
			<option value="<?php echo $installment['value'] ?>"
					<?php if ($installment['selected']): ?>selected<?php endif ?>
			><?php esc_html_e($installment['label']) ?></option>
			<?php $installments_price_increase = $installments_price_increase || ($installment['price_increase'] !== 0); ?>
		<?php endforeach; ?>
	</select>

	<?php if ($installments_price_increase): ?>
	<p>
		<?php esc_html_e(__('Fees may be applied for installments','monri')) ?>
	</p>
	<?php endif; ?>
	<br/>
</div>

<?php endif; ?>

<div id="monri-components"></div>
<p id="monri-components-error" style="color:red;text-transform:uppercase;" role="alert"></p>
<input type="hidden" id="monri-token" name="monri-token" />

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

        var monri = Monri('<?php echo $config['authenticity_token'] ?>', {locale: '<?php echo $config['locale'] ?>'});
        var components = monri.components(
            '<?php echo $config['random_token'] ?>',
            '<?php echo $config['digest'] ?>',
            '<?php echo $config['timestamp'] ?>'
        );

        var style = {invalid: {color: 'red'}};

        // Add an instance of the card Component into the `card-element` <div>.
        var card = components.create('card', {style: style});
        card.mount('monri-components');

        // handle card errors
        card.onChange(function (event) {
            if (event.error) {
                $('#monri-components-error').text(event.error.message);
                $('#monri-token').val('');
            } else {
                $('#monri-components-error').empty();
            }
        });

        $('form.checkout').on('checkout_place_order_monri', function () {
            if ($('#monri-token').val()) {
                return true;
            }

			monri.createToken(card).then(function (result) {
				if (result.error) {
					$('#monri-components-error').text(result.error.message);
				} else {
					$('#monri-token').val(result.result.id);
                    $('form.checkout').submit();
				}
			});

			return false;
        });

    })(jQuery);
</script>
