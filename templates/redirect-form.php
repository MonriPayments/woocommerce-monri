<?php
/** @var $order WC_Order */
/** @var $action string */
/** @var $options array */
?>
<!-- Monri redirect -->
<form action="<?php echo $action ?>" method="post" data-ajax="false" id="monri_payment_form">

	<?php foreach ($options as $key => $value): ?>
		<input type='hidden' name="<?php echo esc_attr($key) ?>" value="<?php echo esc_attr($value) ?>"/>
	<?php endforeach; ?>

	<input type="submit" class="button-alt" id="monri_payment_form_submit" value="<?php echo __('Pay via Monri', 'monri') ?>"/>
	<a class="button cancel" href="<?php echo $order->get_cancel_order_url() ?>">
		<?php echo __('Cancel order &amp; restore cart', 'monri') ?>
	</a>
	<script type="text/javascript">
	(function(){
        /*
		jQuery("body").block(
			{
				message:
					'<img src="<?php echo MONRI_WC_PLUGIN_URL ?>/assets/images/ajax-loader.gif"' +
					' alt="Redirecting…"'+
					' style="float:left; margin-right: 10px; width:50px"/>' +
					'<?php echo __('Thank you for your order. You are being redirected to Monri so you can complete payment.', 'Monri') ?>',
				overlayCSS: {
					background: "#fff",
					opacity: 0.6
				},
				css: {
					padding: 20,
					textAlign: "center",
					color: "#555",
					border: "3px solid #aaa",
					backgroundColor:"#fff",
					cursor: "wait",
					lineHeight: "32px"
				}
			});
			*/
		jQuery("#monri_payment_form").submit();
	})();
	</script>
</form>
