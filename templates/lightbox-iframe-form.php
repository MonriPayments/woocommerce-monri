<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var $order WC_Order */
/** @var $src string */
/** @var $action string */
/** @var $options array */
?>
<!-- Monri lightbox -->
<form action="">
    <script src="<?php echo esc_url($src); ?>" class="lightbox-button"
	<?php foreach ($options as $key => $value) : ?>
		<?php echo esc_attr($key); ?>="<?php echo esc_attr($value); ?>"
	<?php endforeach; ?>
    ></script>
</form>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const monriButton = document.querySelector('.monri-lightbox-button-el');
        monriButton.click();
    });
</script>
