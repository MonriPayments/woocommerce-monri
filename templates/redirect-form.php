<!-- Monri redirect -->
<form action="<?php echo $action ?>" method="post" data-ajax="false" id="monri_payment_form">

	<?php foreach ($options as $key => $value): ?>
		<input type='hidden' name="<?php echo $key ?>" value="<?php echo $value ?>"/>
	<?php endforeach; ?>

	<input type="submit" class="button-alt" id="monri_payment_form_submit" value="<?php echo __('Pay via Monri', 'Monri') ?>"/>
	<a class="button cancel" href="<?php echo $order->get_cancel_order_url() ?>">
		<?php echo __('Cancel order &amp; restore cart', 'monri_wc') ?>
	</a>
	<script type="text/javascript">
    (function(){
        jQuery("body").block(
            {
                message:
	                '<img src="<?php echo MONRI_WC_PLUGIN_URL ?>/assets/images/ajax-loader.gif"' +
	                ' alt="Redirecting…"'+
	                ' style="float:left; margin-right: 10px; width:50px"/>' +
	                '<?php echo __('Hvala na narudžbi. Sada Vas preusmjeravamo na Monri kako bi završili plaćanje.', 'Monri') ?>',
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
        jQuery("#monri_payment_form").submit();
    })();
	</script>
</form>
