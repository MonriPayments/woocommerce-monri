<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var $order WC_Order */
/** @var $src string */
/** @var $action string */
/** @var $options array */
?>
<!-- Monri redirect -->
<form action="action">
    <script src="<?php echo esc_url( $src ) ?>"
            class="lightbox-button"
            data-authenticity-token = "<?php echo esc_attr($options['data-authenticity-token']) ?>"
            data-amount = "<?php echo esc_attr($options['data-amount']) ?>"
            data-currency = "<?php echo esc_attr($options['data-currency']) ?>"
            data-order-number = "<?php echo esc_attr($options['data-order-number']) ?>"
            data-order-info = "<?php echo esc_attr($options['data-order-info']) ?>"
            data-digest = "<?php echo esc_attr($options['data-digest']) ?>"
            data-transaction-type = "<?php echo esc_attr($options['data-transaction-type']) ?>"
            data-ch-full-name = "<?php echo esc_attr($options['data-ch-full-name']) ?>"
            data-ch-zip = "<?php echo esc_attr($options['data-ch-zip']) ?>"
            data-ch-phone = "<?php echo esc_attr($options['data-ch-phone']) ?>"
            data-ch-email = "<?php echo esc_attr($options['data-ch-email']) ?>"
            data-ch-address = "<?php echo esc_attr($options['data-ch-address']) ?>"
            data-ch-city = "<?php echo esc_attr($options['data-ch-city']) ?>"
            data-ch-country = "<?php echo esc_attr($options['data-ch-country']) ?>"
            data-language = "<?php echo esc_attr($options['data-success-url-override']) ?>"
            data-success-url-override = "<?php echo esc_attr($options['data-success-url-override']) ?>"
            data-cancel-url-override = "<?php echo esc_attr($options['data-cancel-url-override']) ?>"
    ></script>

</form>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const monriButton = document.querySelector('.monri-lightbox-button-el');
        monriButton.click();
    });
</script>
