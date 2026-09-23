<?php
/**
 * WSPay return re-post form.
 *
 * WSPay returns cross-site, where browsers enforcing SameSite=Lax withhold this site's cookies.
 * Re-posting the parameters from here makes the request same-site, so the session and login
 * cookies are sent and the thank you page renders for the right customer.
 *
 * This template can be overridden by copying it to yourtheme/monri/wspay-return-form.php
 *
 * @var string $action Thank you page URL to post to.
 * @var array  $params Parameters as returned by WSPay.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

?>
<form id="monri-wspay-return" method="post" action="<?php echo esc_url( $action ); ?>">
	<?php foreach ( $params as $key => $value ) : ?>
        <input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>">
	<?php endforeach; ?>
</form>
<script>document.getElementById( 'monri-wspay-return' ).submit();</script>
