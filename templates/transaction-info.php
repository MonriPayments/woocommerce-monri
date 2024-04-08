<?php
/** @var $order WC_Order */
/** @var $transaction_info array */
?>
<?php if ( ! empty( $transaction_info ) ): ?>
<div class="woocommerce-order-monri-transaction-info">
	<p><?php esc_html_e( 'Transaction info', 'monri' ) ?></p>
	<ul>

		<?php foreach ( $transaction_info as $key => $value ): ?>
			<li><?php esc_html_e( $key, 'monri' ) ?>: <strong><?php echo esc_html( $value ) ?></strong></li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>
