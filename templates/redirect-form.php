<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var $order WC_Order */
/** @var $action string */
/** @var $options array */
?>
<!-- Monri redirect -->
<form action="<?php echo esc_url( $action ) ?>" method="post" data-ajax="false" id="monri_payment_form">

	<?php foreach ($options as $key => $value): ?>
		<input type='hidden' name="<?php echo esc_attr($key) ?>" value="<?php echo esc_attr($value) ?>"/>
	<?php endforeach; ?>

	<input type="submit" class="button-alt" id="monri_payment_form_submit" value="<?php esc_attr_e('Pay via Monri', 'monri') ?>"/>
	<a class="button cancel" href="<?php echo esc_url( $order->get_cancel_order_url() ) ?>">
		<?php esc_html_e('Cancel order &amp; restore cart', 'monri') ?>
	</a>
	<script type="text/javascript">
	(function(){
		jQuery("#monri_payment_form").submit();
	})();
	</script>
</form>
