<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var array $config */
/** @var array $installments */

$installments_price_increase = false;
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
