<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/** @var $order WC_Order */
/** @var $transaction_info array */
?>
<?php if ( ! empty( $transaction_info ) ): ?>
<section class="woocommerce-order-monri-transaction-info">
	<strong><?php esc_html_e( 'Transaction info', 'monri' ) ?></strong>
	<ul>
        <li><?php esc_html_e( 'Transaction ID', 'monri' ) ?>: <strong><?php echo esc_html($transaction_info['WsPayOrderId']['value']) ?></strong></li>
        <li><?php esc_html_e( 'Approval code', 'monri' ) ?>: <strong><?php echo esc_html($transaction_info['ApprovalCode']['value']) ?></strong></li>
        <li><?php esc_html_e( 'Credit card type', 'monri' ) ?>: <strong><?php echo esc_html($transaction_info['PaymentType']['value']) ?></strong></li>
        <li><?php esc_html_e( 'Credit card number', 'monri' ) ?>: <strong><?php echo esc_html($transaction_info['CreditCardNumber']['value']) ?></strong></li>
        <li><?php esc_html_e( 'Payment plan', 'monri' ) ?>: <strong><?php echo esc_html($transaction_info['PaymentPlan']['value']) ?></strong></li>
        <li><?php esc_html_e( 'Date/Time', 'monri' ) ?>: <strong><?php echo esc_html($transaction_info['DateTime']['value']) ?></strong></li>
    </ul>
</section>
<?php endif; ?>
