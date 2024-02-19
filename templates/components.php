<?php
/** @var $config */
?>
<div id="monri_components"></div>
<div id="card-errors" class="" role="alert"></div>

<script type="text/javascript">
    jQuery( function( $ ) {
        //alert(1);
	});

	jQuery('#monri_components').ready(function () {

		var monri = Monri('<?php echo $config['authenticity_token'] ?>', {locale: 'hr'});
		var components = monri.components(
			"<?php echo $config['random_token'] ?>",
			"<?php echo $config['digest'] ?>",
			'<?php echo $config['timestamp'] ?>'
		);

		var style = { invalid: { color: 'red' } };

		// Add an instance of the card Component into the `card-element` <div>.
		var card = components.create('card', { style: style });
		card.mount('monri_components');

		jQuery('form.checkout').on('checkout_place_order', function () {
			// If the Monri radio button is checked, handle Monri token
			if (jQuery('input#payment_method_monri').is(':checked')) {

				if (jQuery('#monri-token').length == 0) {
					// If monri-token element could not be found add it to the form and set its value to 'not-set'.
					var hiddenInput = document.createElement('input');
					hiddenInput.setAttribute('type', 'hidden');
					hiddenInput.setAttribute('name', 'monri-token');
					hiddenInput.setAttribute('id', 'monri-token');
					hiddenInput.setAttribute('value', 'not-set');
					jQuery(this).append(hiddenInput);
				}

				if (jQuery('#monri-token').val() == 'not-set') {

					monri.createToken(card).then(function (result) {
						if (result.error) {
							// Inform the customer that there was an error.
							var errorElement = document.getElementById('card-errors');
							errorElement.textContent = result.error.message;

						} else {
							jQuery('#monri-token').val(result.result.id);
						}
					});

				}

			} else {
					// If the Monri radio button is not checked, delete the errors and the Monri token
					var displayError = document.getElementById('card-errors');
					displayError.textContent = '';
					jQuery('#monri-token').remove();
			}
		});

		jQuery(document.body).on('checkout_error', function () {
			// Trigger the submit of the checkout form If the thrown wc error is 'set_monri_token_notice' and
			// no error was returend by  monri.createToken. Else remove the 'monri token' html elemente so a new one can be generated on the next form submit.
			var error_text = jQuery('.woocommerce-error').find('li').first().text();

			if (error_text.trim() == 'set_monri_token_notice') {

				jQuery('.woocommerce-error').remove();

				if (jQuery('#card-errors').html() == '') {
						jQuery('#place_order').trigger('click');
				}

			}
		});

		card.onChange(function (event) {
			// If monri.createToken returned and error show it to the user.
			var displayError = document.getElementById('card-errors');
			if (event.error) {
				displayError.textContent = event.error.message;
				jQuery('#monri-token').remove();
			} else {
				displayError.textContent = '';
			}
		});

	});
</script>

