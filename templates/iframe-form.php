<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var $order WC_Order */
/** @var $action string */
/** @var $options array */
?>
<!-- Monri iframe -->
<iframe id="monri_payment_iframe" style="min-height:300px; width: 100%"></iframe>

<form action="<?php echo esc_url( $action ) ?>" method="post" data-ajax="false" id="monri_payment_form" target="monri_payment_iframe">

	<?php foreach ($options as $key => $value): ?>
		<input type='hidden' name="<?php echo esc_attr($key) ?>" value="<?php echo esc_attr($value) ?>"/>
	<?php endforeach; ?>

	<input type="submit" class="button-alt" id="monri_payment_form_submit" value="<?php esc_attr_e('Pay via Monri', 'monri') ?>"/>
	<a class="button cancel" href="<?php echo esc_url( $order->get_cancel_order_url() ) ?>">
		<?php esc_html_e('Cancel order &amp; restore cart', 'monri') ?>
	</a>
</form>

<script type="text/javascript">
	(function(){
		jQuery("#monri_payment_form").submit();
		iFrameResize({ checkOrigin: false }, '#monri_payment_iframe');
		//iframeResize
	})();
</script>
