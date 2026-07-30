<?php

class Monri_WC_Gateway_Adapter_Webpay_Components_New extends Monri_WC_Gateway_Webpay_Components_Abstract {

    /**
     * Adapter ID
     */
    public const ADAPTER_ID = 'webpay_components_new';

    /**
     * Supported features
     *
     * @var string[]
     */
    public $supports = array( 'products', 'refunds' );

    /**
     * Gateway ID
     *
     * @var string
     */
    public $id = 'monri_components_card';

    /**
     * @var Monri_WC_Gateway
     */
    private $payment;

    /**
     * Components New constructor.
     *
     * @return void
     */
    public function __construct() {
        $this->init_settings();
        $this->has_fields  = false;
        $this->title       = $this->settings['title'] ?? __( 'Monri', 'monri' );
        $this->description = $this->settings['description'];

        // Only register receipt hook once (class may be instantiated multiple times).
        static $receipt_hooked = false;
        if ( ! $receipt_hooked ) {
            add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'process_components' ) );
            $receipt_hooked = true;
        }

        add_action( 'woocommerce_before_thankyou', array( $this, 'process_return' ) );

        // load components.js on frontend checkout.
        add_action(
            'template_redirect',
            function () {
                if ( is_checkout() ) {
                    $script_url = $this->get_option_bool( 'test_mode' ) ? self::SCRIPT_ENDPOINT_TEST : self::SCRIPT_ENDPOINT;
                    wp_enqueue_script(
                        'monri-components',
                        $script_url,
                        array(),
                        MONRI_WC_VERSION,
                        false
                    );
                }
            }
        );

        //call this api on frontend periodically to check if transaction has been completed
        add_action('rest_api_init', function () {
            register_rest_route('monri/v1', '/transaction-status/(?P<order_number>[^/]+)', array(
                'methods'  => 'GET',
                'callback' => array( $this, 'monri_get_transaction_status_rest'),
                'permission_callback' => array( $this, 'monri_transaction_status_permission' ),
            ));
        });

        add_action( 'woocommerce_order_status_changed', [ $this, 'process_capture' ], null, 4 );
        add_action( 'woocommerce_order_status_changed', [ $this, 'process_void' ], null, 4 );
    }

    /**
     * Initialize when used as adapter within main Monri_WC_Gateway.
     *
     * @param Monri_WC_Gateway $payment
     *
     * @return void
     */
    public function init( $payment ) {
        $this->payment = $payment;
        $payment->has_fields = false;

        // Register receipt hook for the main gateway ID (monri) so
        // the components form renders on the pay/receipt page.
        add_action( 'woocommerce_receipt_' . $payment->id, array( $this, 'process_components' ) );
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

        if ( $this->get_option_bool( 'test_mode' ) ) {
            $order_id = Monri_WC_Utils::get_test_order_id( $order_id );
        }

        wc_get_template(
            'components-new.php',
            array(
                'config' => array(
                    'env'                => $this->get_option_bool( 'test_mode' ) ? 'test' : 'prod',
                    'client_secret'      => $this->request_authorize( $order ),
                    'authenticity_token' => $this->get_option( 'monri_authenticity_token' ),
                    'locale'             => $this->get_option( 'form_language' ),
                    'return_url'         => $this->get_return_url( $order ),
                    'ch_full_name'       => wc_trim_string( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
                    'ch_address'         => wc_trim_string( $order->get_billing_address_1(), 100, '' ),
                    'ch_city'            => wc_trim_string( $order->get_billing_city(), 100, '' ),
                    'ch_zip'             => wc_trim_string( $order->get_billing_postcode(), 100, '' ),
                    'ch_country'         => wc_trim_string( $order->get_billing_country(), 100, '' ),
                    'ch_phone'           => wc_trim_string( $order->get_billing_phone(), 100, '' ),
                    'ch_email'           => wc_trim_string( $order->get_billing_email(), 100, '' ),
                    'orderInfo'          => $order_id . '_' . gmdate( 'dmy' ),
                    'order_number'       => $order_id,
                    'order_hash'         => $order->get_meta( 'order_access_hash' )
                ),
            ),
            basename( MONRI_WC_PLUGIN_PATH ),
            MONRI_WC_PLUGIN_PATH . 'templates/'
        );
    }

    /**
     *
     * @param $order
     * @return string
     */
    protected function request_authorize($order) {

        $order_id = $order->get_id();
        if ( $this->get_option_bool( 'test_mode' ) ) {
            $order_id = Monri_WC_Utils::get_test_order_id( $order_id );
        }

        $url = $this->get_option_bool( 'test_mode' ) ?
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
            'transaction_type' => $this->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase',
            'order_info'       => 'woocommerce order'
        ];


        if ( $this->tokenization_enabled() && is_user_logged_in() ) {

            $tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id(), 'monri' );

            $supported_payment_methods = ['card'];

            foreach ( $tokens as $token ) {
                $supported_payment_methods[] = $token->get_token();
            }

            $data['supported_payment_methods'] = $supported_payment_methods;

        }

        $data = wp_json_encode( $data );

        $timestamp = time();
        $digest    = hash( 'sha512',
            $this->get_option( 'monri_merchant_key' ) .
            $timestamp .
            $this->get_option( 'monri_authenticity_token' ) .
            $data
        );

        $authorization = "WP3-v2 {$this->get_option( 'monri_authenticity_token' )} $timestamp $digest";

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

        $body          = wp_remote_retrieve_body( $response );
        return json_decode( $body, true )['client_secret'] ?? '';
    }

    /**
     * @return bool
     */
    public function tokenization_enabled() {
        return $this->get_option_bool( 'monri_web_pay_tokenization_enabled' );
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
                'authenticity_token' => $this->get_option( 'monri_authenticity_token' ),
                'locale'             => $this->get_option( 'form_language' ),
                'order_creation'     => 'before_payment',
                'env'                => $this->get_option_bool( 'test_mode' ) ? 'test' : 'prod',
            ]
        ];
    }
}
