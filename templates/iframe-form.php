<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var $order WC_Order */
/** @var $action string */
/** @var $options array */
?>
<!-- Monri iframe -->
<iframe id="monri_payment_iframe" name="monri_payment_iframe" style="width:100%; height:100vh;"></iframe>

<form action="<?php echo esc_url( $action ) ?>" method="post" data-ajax="false" id="monri_payment_form" target="monri_payment_iframe">
	<?php foreach ($options as $key => $value): ?>
		<input type='hidden' name="<?php echo esc_attr($key) ?>" value="<?php echo esc_attr($value) ?>"/>
	<?php endforeach; ?>
</form>

<script>
	(function(){
		jQuery("#monri_payment_form").submit();
		iframeResize({ license: "GPLv3", checkOrigin: false }, '#monri_payment_iframe');
	})();
</script>
