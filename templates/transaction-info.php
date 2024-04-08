<?php
/** @var $order WC_Order */
/** @var $transaction_info array */
?>
<?php if ( ! empty( $transaction_info ) ): ?>
<section class="woocommerce-order-monri-transaction-info">
	<p><?php esc_html_e( 'Transaction info', 'monri' ) ?></p>
	<ul>

		<?php foreach ( $transaction_info as $info ): ?>
			<li><?php esc_html_e( $info['label'], 'monri' ) ?>: <strong><?php echo esc_html( $info['value'] ) ?></strong></li>
		<?php endforeach; ?>
	</ul>
</section>
<?php endif; ?>
