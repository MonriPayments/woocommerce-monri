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
        (function($){

            function collectBrowserInfo() {
                var screen_width = window && window.screen ? window.screen.width : '';
                var screen_height = window && window.screen ? window.screen.height : '';
                var color_depth = window && window.screen ? window.screen.colorDepth : '';
                var user_agent = window && window.navigator ? window.navigator.userAgent : '';
                var java_enabled = window && window.navigator ? navigator.javaEnabled() : false;
                var ip_address = <?php echo json_encode( $order->get_customer_ip_address() ); ?>;

                var language = '';
                if (window && window.navigator) {
                    language = window.navigator.language
                        ? window.navigator.language
                        : window.navigator.browserLanguage || '';
                }

                var d = new Date();
                var time_zone_offset = d.getTimezoneOffset();

                return {
                    screen_width: screen_width,
                    screen_height: screen_height,
                    color_depth: color_depth,
                    user_agent: user_agent,
                    time_zone_offset: time_zone_offset,
                    language: language,
                    java_enabled: java_enabled,
                    http_accept: '*/*',
                    http_user_agent: user_agent,
                    http_accept_language: language || '*',
                    ip: ip_address,
                };
            }

            var $form = $("#monri_payment_form");
            $form.on('submit', function(){
                var browserInfoJson = JSON.stringify(collectBrowserInfo());

                $('<input>').attr({
                    type: 'hidden',
                    name: 'browser_info',
                    value: browserInfoJson
                }).appendTo($form);
            });
            $form.submit();

        })(jQuery);
    </script>
</form>
