<?php

/**
 * Components adapter for the flow where the order is created before payment.
 *
 * Runs behind Monri_WC_Gateway exactly like Monri_WC_Gateway_Adapter_Webpay_Components,
 * so orders are placed with the main gateway id and every handler below matches on it.
 */
class Monri_WC_Gateway_Adapter_Webpay_Components_New {

    /**
     * Adapter ID
     */
    public const ADAPTER_ID = 'webpay_components_new';

    public const AUTHORIZATION_ENDPOINT_TEST = 'https://ipgtest.monri.com/v2/payment/new';
    public const AUTHORIZATION_ENDPOINT      = 'https://ipg.monri.com/v2/payment/new';

    public const SCRIPT_ENDPOINT_TEST = 'https://ipgtest.monri.com/dist/components.js';
    public const SCRIPT_ENDPOINT      = 'https://ipg.monri.com/dist/components.js';

    /**
     * @var Monri_WC_Gateway
     */
    private $payment;

    /**
     * Supported features
     *
     * @var string[]
     */
    public $supports = [ 'products', 'refunds' ];

    /**
     * @param Monri_WC_Gateway $payment
     *
     * @return void
     */
    public function init( $payment ) {
        $this->payment             = $payment;
        $this->payment->has_fields = false;

        add_action( 'woocommerce_receipt_' . $this->payment->id, [ $this, 'process_components' ] );
        add_action( 'woocommerce_before_thankyou', [ $this, 'process_return' ] );
        add_action( 'woocommerce_order_status_changed', [ $this, 'process_capture' ], null, 4 );
        add_action( 'woocommerce_order_status_changed', [ $this, 'process_void' ], null, 4 );

        // load components.js on frontend checkout.
        add_action( 'template_redirect', function () {
            if ( is_checkout() ) {
                $script_url = $this->payment->get_option_bool( 'test_mode' ) ? self::SCRIPT_ENDPOINT_TEST : self::SCRIPT_ENDPOINT;
                wp_enqueue_script( 'monri-components', $script_url, [], MONRI_WC_VERSION, false );
            }
        } );

        $this->init_tokenization();
    }

    /**
     * Adds tokenization support and wires up the saved cards.
     *
     * Saved cards are offered inside the Monri card component itself (through
     * supported_payment_methods in the authorize request), so WooCommerce's own
     * saved-methods list is hidden on checkout.
     *
     * @return void
     */
    private function init_tokenization() {

        if ( ! $this->tokenization_enabled() ) {
            return;
        }

        $this->supports[] = 'tokenization';

        require_once __DIR__ . '/payment-token-webpay.php';

        add_filter( 'woocommerce_payment_token_class', function ( $value, $type ) {
            if ( $type === 'Monri_Webpay' ) {
                return Monri_WC_Payment_Token_Webpay::class;
            }

            return $value;
        }, 0, 2 );

        add_filter( 'woocommerce_get_customer_payment_tokens', function ( $tokens, $customer_id, $gateway_id ) {
            // Gateway id is not usually sent here. We use it to get user payment tokens when building the authorize request.
            if ( ! is_checkout() || $gateway_id === $this->payment->id ) {
                return $tokens;
            }

            // Else we hide Monri saved payment options on checkout
            return array_filter( $tokens, function ( $token ) {
                return $token->get_type() !== 'Monri_Webpay';
            } );
        }, 10, 3 );
    }

    /**
     * @return bool
     */
    public function tokenization_enabled() {
        return $this->payment->get_option_bool( 'monri_web_pay_tokenization_enabled' );
    }

    /**
     * Process the payment and return the result
     *
     * The order exists at this point, payment happens on the receipt page.
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment( $order_id ) {

        $order = wc_get_order( $order_id );

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url( true ),
        ];
    }

    /**
     * Passes config data to template file
     *
     * @param int $order_id
     *
     * @return void
     */
    public function process_components( $order_id ) {

        $order = wc_get_order( $order_id );

        // Cards can only be saved for a customer account, never for a guest order.
        $tokenization = $this->tokenization_enabled() && $order->get_user_id();

        // Authorize first, it stores the order number (suffixed in test mode) the rest of the config refers to.
        $client_secret      = $this->request_authorize( $order );
        $monri_order_number = $order->get_meta( 'monri_order_number' );

        wc_get_template(
            'components-new.php',
            array(
                'tokenization' => $tokenization,
                'config' => array(
                    'env'                => $this->payment->get_option_bool( 'test_mode' ) ? 'test' : 'prod',
                    'client_secret'      => $client_secret,
                    'authenticity_token' => $this->payment->get_option( 'monri_authenticity_token' ),
                    'locale'             => $this->payment->get_option( 'form_language' ),
                    'return_url'         => $this->payment->get_return_url( $order ),
                    'ch_full_name'       => wc_trim_string( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), 100, '' ),
                    'ch_address'         => wc_trim_string( $order->get_billing_address_1(), 100, '' ),
                    'ch_city'            => wc_trim_string( $order->get_billing_city(), 100, '' ),
                    'ch_zip'             => wc_trim_string( $order->get_billing_postcode(), 100, '' ),
                    'ch_country'         => wc_trim_string( $order->get_billing_country(), 100, '' ),
                    'ch_phone'           => wc_trim_string( $order->get_billing_phone(), 100, '' ),
                    'ch_email'           => wc_trim_string( $order->get_billing_email(), 100, '' ),
                    'ip_address'         => $order->get_customer_ip_address(),
                    'orderInfo'          => $monri_order_number . '_' . gmdate( 'dmy' ),
                    'order_number'       => $monri_order_number,
                    'tokenization'       => $tokenization,
                ),
            ),
            basename( MONRI_WC_PLUGIN_PATH ),
            MONRI_WC_PLUGIN_PATH . 'templates/'
        );
    }

    /**
     * Generate client secret
     *
     * @param WC_Order $order
     *
     * @return string
     */
    protected function request_authorize( $order ) {

        $order_id = $order->get_id();
        if ( $this->payment->get_option_bool( 'test_mode' ) ) {
            $order_id = Monri_WC_Utils::get_test_order_id( $order_id );
        }

        $url = $this->payment->get_option_bool( 'test_mode' ) ?
            self::AUTHORIZATION_ENDPOINT_TEST :
            self::AUTHORIZATION_ENDPOINT;

        $order_total = (float) $order->get_total();

        $amount_in_minor_units = (int) round( $order_total * 100 );

        $currency = get_woocommerce_currency();
        if ( $currency === 'KM' ) {
            $currency = 'BAM';
        }

        $data = [
            'amount'           => $amount_in_minor_units,
            'order_number'     => $order_id,
            'currency'         => $currency,
            'transaction_type' => $this->payment->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase',
            'order_info'       => 'woocommerce order',
            'ip'               => $order->get_customer_ip_address(),
        ];

        if ( $this->tokenization_enabled() && is_user_logged_in() ) {

            $tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id(), $this->payment->id );

            $supported_payment_methods = ['card'];

            foreach ( $tokens as $token ) {
                $supported_payment_methods[] = $token->get_token();
            }

            $data['supported_payment_methods'] = $supported_payment_methods;

        }

        $data = wp_json_encode( $data );

        $timestamp = time();
        $digest    = hash( 'sha512',
            $this->payment->get_option( 'monri_merchant_key' ) .
            $timestamp .
            $this->payment->get_option( 'monri_authenticity_token' ) .
            $data
        );

        $authorization = "WP3-v2 {$this->payment->get_option( 'monri_authenticity_token' )} $timestamp $digest";

        Monri_WC_Logger::log( $data, __METHOD__ );

        $response = wp_remote_post( $url, [
                'body'      => $data,
                'headers'   => [
                    'Content-Type'   => 'application/json',
                    'Content-Length' => strlen( $data ),
                    'Authorization'  => $authorization
                ],
                'timeout'   => 10,
                'sslverify' => true
            ]
        );

        Monri_WC_Logger::log( $response, __METHOD__ );

        if ( is_wp_error( $response ) ) {
            $response = [ 'status' => 'error', 'error' => $response->get_error_message() ];
        }

        $body = wp_remote_retrieve_body( $response );

        $order->update_meta_data( 'monri_order_number', $order_id );
        $order->save();

        return json_decode( $body, true )['client_secret'] ?? '';
    }

    /**
     * Monri returns on thankyou page
     *
     * @param int $order_id
     *
     * @return void
     */
    public function process_return( $order_id ) {

        $order = wc_get_order( $order_id );
        if ( ! $order || $order->get_payment_method() !== $this->payment->id ) {
            return;
        }

        $this->sync_order_status( $order );
        $this->maybe_save_payment_token( $order );
    }

    /**
     * Checks order status on Monri and updates order accordingly
     *
     * @param WC_Order $order
     *
     * @return bool
     */
    public function sync_order_status( $order ) {

        $monri_order_number = $order->get_meta( 'monri_order_number' );

        if ( ! $monri_order_number ) {
            return false;
        }

        $response           = Monri_WC_Api::instance()->orders_show( $monri_order_number );
        $formatted_response = json_decode( wp_json_encode( $response ), true );
        Monri_WC_Logger::log( $formatted_response, __METHOD__ );
        if ( is_wp_error( $response ) ) {
            $order->add_order_note(
                sprintf( __( 'There was an error getting the order status', 'monri' ) )
            );

            return false;
        }

        if ( ! isset( $formatted_response['response-code'] ) ) {
            return false;
        }

        // Check response code of order.
        switch ( $formatted_response['response-code'] ) {
            case '0000':
                if ( $order->get_status() === 'pending' ) {
                    $transaction_type = ! empty( $formatted_response['transaction-type'] ) ?
                        sanitize_text_field( $formatted_response['transaction-type'] ) : '';
                    $approval_code    = ! empty( $formatted_response['approval-code'] ) ?
                        sanitize_text_field( $formatted_response['approval-code'] ) : '';

                    if ( $transaction_type === 'purchase' ) {
                        $order->payment_complete( $approval_code );
                    } else {
                        $order->update_status( 'on-hold', __( 'Order awaiting payment', 'monri' ) );
                    }

                    /* translators: %s: generated id which represents order number */
                    $order->add_order_note( sprintf( __( 'Order number in Monri administration: %s', 'monri' ), $monri_order_number ) );
                    $order->save();
                }
                break;

            case '1050':
                if ( $order->get_status() === 'pending' ) {
                    $order->update_status( 'cancelled' );
                }
                break;

            default:
                break;
        }

        return true;
    }

    /**
     * Saves the card the shopper opted to save on the components form.
     *
     * The order is created before payment here, so there is no process_payment
     * response to read the token from - the components template posts the
     * confirmPayment result along to the thankyou page instead. The transaction
     * itself is verified against Monri by sync_order_status() before this runs.
     *
     * @param WC_Order $order
     *
     * @return void
     */
    private function maybe_save_payment_token( $order ) {

        if ( ! $this->tokenization_enabled() || ! $order->get_user_id() ) {
            return;
        }

        // Not paid (or not paid yet) as far as Monri is concerned.
        if ( in_array( $order->get_status(), array( 'pending', 'failed', 'cancelled' ), true ) ) {
            return;
        }

        // monri-transaction is a JSON value, it is individually sanitized after decode
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Missing
        $transaction = json_decode( wp_unslash( $_POST['monri-transaction'] ?? '{}' ), true );

        if ( ! is_array( $transaction ) || ! isset( $transaction['payment_method']['data'] ) ) {
            return;
        }

        $token_data = array();
        foreach ( array( 'expiration_date', 'masked', 'brand', 'token' ) as $key ) {
            if ( isset( $transaction['payment_method']['data'][ $key ] ) ) {
                $token_data[ $key ] = sanitize_text_field( $transaction['payment_method']['data'][ $key ] );
            }
        }

        $this->save_user_token( $order->get_user_id(), $token_data );
    }

    /**
     * Stores a Monri card token as a WooCommerce payment token
     *
     * @param int   $user_id
     * @param array $data Token data as returned by Monri in payment_method.data.
     *
     * @return bool
     */
    public function save_user_token( $user_id, $data ) {

        // Monri only returns token data when the shopper ticked "save card".
        if ( ! isset( $data['token'], $data['brand'], $data['masked'], $data['expiration_date'] ) ) {
            return false;
        }

        if ( $this->token_already_exists( $user_id, $data['masked'] ) ) {
            return false;
        }

        $wc_token = new Monri_WC_Payment_Token_Webpay();

        $wc_token->set_gateway_id( $this->payment->id );
        $wc_token->set_token( $data['token'] );
        $wc_token->set_user_id( $user_id );

        $masked_pan_array = explode( '-', $data['masked'] );
        $wc_token->set_last4( end( $masked_pan_array ) );
        $wc_token->set_card_type( $data['brand'] );

        $wc_token->set_expiry_year( substr( $data['expiration_date'], 0, 2 ) );
        $wc_token->set_expiry_month( substr( $data['expiration_date'], 2, 2 ) );

        return (bool) $wc_token->save();
    }

    /**
     * Check if payment token already exists to avoid making duplicates
     *
     * @param int    $user_id
     * @param string $masked_pan
     *
     * @return bool
     */
    private function token_already_exists( $user_id, $masked_pan ) {

        $masked_pan_array = explode( '-', $masked_pan );
        $last4            = end( $masked_pan_array );

        foreach ( WC_Payment_Tokens::get_customer_tokens( $user_id, $this->payment->id ) as $user_token ) {
            if ( $user_token->get_last4() === $last4 ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process a refund
     *
     * @param int        $order_id
     * @param float|null $amount Refund amount.
     * @param string     $reason
     *
     * @return bool
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {

        $order = wc_get_order( $order_id );
        if ( $order->get_payment_method() !== $this->payment->id ) {
            return false;
        }

        $monri_order_id = $order->get_meta( 'monri_order_number' );
        $currency       = $order->get_currency();

        if ( empty( $monri_order_id ) ) {
            $order->add_order_note( sprintf( __( 'There was an error submitting the refund to Monri.', 'monri' ) ) );

            return false;
        }

        $response           = Monri_WC_Api::instance()->refund( $monri_order_id, $amount * 100, $currency );
        $formatted_response = json_decode( wp_json_encode( $response ), true );
        if ( is_wp_error( $response ) || ! ( isset( $formatted_response['response-code'] ) && $formatted_response['response-code'] === '0000' ) ) {
            Monri_WC_Logger::log( $formatted_response, __METHOD__ );
            $order->add_order_note(
                sprintf( __( 'There was an error submitting the refund to Monri.', 'monri' ) )
            );

            return false;
        }

        $order->add_order_note(
            sprintf(
            /* translators: %s: amount which was successfully refunded */
                __( 'Refund of %s successfully sent to Monri.', 'monri' ),
                wc_price( $amount, array( 'currency' => $order->get_currency() ) )
            )
        );

        return true;
    }

    /**
     * Capture order on Monri side
     *
     * @param int    $order_id
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function process_capture( $order_id, $from, $to ) {

        if ( ! ( in_array( $from, array( 'pending', 'on-hold' ), true ) && in_array( $to, wc_get_is_paid_statuses(), true ) ) ) {
            return false;
        }

        $order = wc_get_order( $order_id );
        if ( $order->get_payment_method() !== $this->payment->id ) {
            return false;
        }

        $monri_order_id = $order->get_meta( 'monri_order_number' );
        if ( empty( $monri_order_id ) ) {
            return false;
        }

        $currency = $order->get_currency();
        $amount   = $order->get_total() - $order->get_total_refunded();

        if ( $amount < 0.01 ) {
            return false;
        }

        $response           = Monri_WC_Api::instance()->capture( $monri_order_id, $amount * 100, $currency );
        $formatted_response = json_decode( wp_json_encode( $response ), true );
        if ( is_wp_error( $response ) || ! ( isset( $formatted_response['response-code'] ) && $formatted_response['response-code'] === '0000' ) ) {
            Monri_WC_Logger::log( $formatted_response, __METHOD__ );
            $order->add_order_note(
                sprintf( __( 'There was an error submitting the capture to Monri.', 'monri' ) )
            );

            return false;
        }

        $order->payment_complete( $monri_order_id );
        $order->add_order_note(
            sprintf(
            /* translators: %s: amount which was successfully captured */
                __( 'Capture of %s successfully sent to Monri.', 'monri' ),
                wc_price( $amount, array( 'currency' => $order->get_currency() ) )
            )
        );

        return true;
    }

    /**
     * Void order on Monri side
     *
     * @param int    $order_id
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function process_void( $order_id, $from, $to ) {

        if ( ! ( in_array( $from, array( 'pending', 'on-hold' ), true ) && in_array( $to, array( 'cancelled', 'failed' ), true ) ) ) {
            return false;
        }

        $order = wc_get_order( $order_id );
        if ( $order->get_payment_method() !== $this->payment->id ) {
            return false;
        }

        $monri_order_id = $order->get_meta( 'monri_order_number' );
        if ( empty( $monri_order_id ) ) {
            return false;
        }

        $amount   = $order->get_total() - $order->get_total_refunded();
        $currency = $order->get_currency();
        if ( $amount < 0.01 ) {
            return false;
        }

        $response           = Monri_WC_Api::instance()->void( $monri_order_id, $amount * 100, $currency );
        $formatted_response = json_decode( wp_json_encode( $response ), true );
        if ( is_wp_error( $response ) || ! ( isset( $formatted_response['response-code'] ) && $formatted_response['response-code'] === '0000' ) ) {
            Monri_WC_Logger::log( $formatted_response, __METHOD__ );
            $order->add_order_note(
                sprintf( __( 'There was an error submitting the void to Monri.', 'monri' ) )
            );

            return false;
        }

        $order->add_order_note(
            sprintf(
            /* translators: %s: amount which was successfully voided */
                __( 'Void of %s successfully sent to Monri.', 'monri' ),
                wc_price( $amount, array( 'currency' => $order->get_currency() ) )
            )
        );

        return true;
    }

    /**
     * Provide blocks data for the new checkout block integration.
     * Since this adapter creates the order before payment, we pass
     * configuration that tells the frontend to use the order-first flow.
     *
     * @return array
     */
    public function prepare_blocks_data() {
        return [
            'components' => [
                'authenticity_token' => $this->payment->get_option( 'monri_authenticity_token' ),
                'locale'             => $this->payment->get_option( 'form_language' ),
                'order_creation'     => 'before_payment',
                'env'                => $this->payment->get_option_bool( 'test_mode' ) ? 'test' : 'prod',
            ]
        ];
    }
}
