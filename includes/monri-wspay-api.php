<?php
class Monri_WSPay_WC_Api {
    public const ENDPOINT_TEST = 'https://test.wspay.biz/api/services';
    public const ENDPOINT = 'https://secure.wspay.biz/api/services';
    /**
     * @var Monri_WC_Api
     */
    private static $instance;

    /**
     * @var bool
     */
    public $test_mode = true;

    /**
     * @var string
     */
    private $version = '2.0';

    /**
     * @return Monri_WSPay_WC_Api
     */
    public static function instance() {

        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        $this->test_mode = Monri_WC_Settings::instance()->get_option_bool( 'test_mode' );
    }

    /**
     * @param string $STAN
     * @param string $approval_code
     * @param string $wspay_order_id
     * @param int $amount
     * @param string $shop_id
     * @param bool $is_tokenization
     *
     * @return string
     */
    private function generate_signature_API($STAN, $approval_code, $wspay_order_id, $amount, $shop_id, $is_tokenization ) {
        $secret_key = $is_tokenization ?
            Monri_WC_Settings::instance()->get_option( 'monri_ws_pay_form_tokenization_secret' ) :
            Monri_WC_Settings::instance()->get_option( 'monri_ws_pay_form_secret' );

        $clean_total_amount = str_replace(',', '', $amount);
        $signature =
            $shop_id . $wspay_order_id .
            $secret_key . $STAN .
            $secret_key . $approval_code .
            $secret_key . $clean_total_amount .
            $secret_key . $wspay_order_id;

        $signature = hash('sha512', $signature);
        return $signature;
    }

    /**
     * Send POST request to $url with $params as a field
     *
     * @param string $path
     * @param array $params
     *
     * @return array
     */
    private function request( $path, $params ) {

        $url = $this->test_mode ? self::ENDPOINT_TEST : self::ENDPOINT;
        $url .= $path;

        $result = wp_remote_post( $url, [
                'body'      => wp_json_encode( $params ),
                'headers'   => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'timeout'   => 15,
                'sslverify' => false
            ]
        );

        if ( is_wp_error( $result ) || ! isset( $result['body'] ) ) {
            return [];
        }

        return json_decode( $result['body'], true );
    }
    /**
     * @param string $STAN
     * @param string $approval_code
     * @param string $wspay_order_id
     * @param int $amount
     * @param bool $is_tokenization
     *
     * @return array
     */
    public function capture($STAN, $approval_code, $wspay_order_id, $amount, $is_tokenization) {
        $req = $this->create_request_body($STAN, $approval_code, $wspay_order_id, $amount, $is_tokenization);
        return $this->request('/completion', $req);
    }
    /**
     * @param string $STAN
     * @param string $approval_code
     * @param string $wspay_order_id
     * @param int $amount
     * @param bool $is_tokenization
     *
     * @return array
     */
    public function refund($STAN, $approval_code, $wspay_order_id, $amount, $is_tokenization) {
        $req = $this->create_request_body($STAN, $approval_code, $wspay_order_id, $amount, $is_tokenization);
        return $this->request('/refund', $req);
    }
    /**
     * @param string $STAN
     * @param string $approval_code
     * @param string $wspay_order_id
     * @param int $amount
     * @param bool $is_tokenization
     *
     * @return array
     */
    public function void($STAN, $approval_code, $wspay_order_id, $amount, $is_tokenization) {
        $req = $this->create_request_body($STAN, $approval_code, $wspay_order_id, $amount, $is_tokenization);
        return $this->request('/void', $req);
    }
    /**
     * @param string $STAN
     * @param string $approval_code
     * @param string $wspay_order_id
     * @param int $amount
     * @param bool $is_tokenization
     *
     * @return array
     */
    private function create_request_body($STAN, $approval_code, $wspay_order_id, $amount, $is_tokenization) {

        $shop_id = $is_tokenization ?
            Monri_WC_Settings::instance()->get_option( 'monri_ws_pay_form_tokenization_shop_id' ) :
            Monri_WC_Settings::instance()->get_option( 'monri_ws_pay_form_shop_id' );
        $signature = $this->generate_signature_API($STAN, $approval_code, $wspay_order_id, $amount, $shop_id, $is_tokenization);
        $version = $this->version;
        $req = [];
        $req['STAN'] = $STAN;
        $req['Version'] = $version;
        $req['ShopID'] = $shop_id;
        $req['Signature'] = $signature;
        $req['Amount'] = $amount;
        $req['WsPayOrderId'] = $wspay_order_id;
        $req['ApprovalCode'] = $approval_code;
        return $req;
    }
}