<?php

class MonriApi
{
    private $merchant_key;
    private $authenticity_token;

    public function __construct()
    {
        $this->merchant_key = get_option('monri_merchant_key');;
        $this->authenticity_token = get_option('monri_authenticity_token');
    }

    private static function redirect($get_return_url)
    {
        // TODO: implement redirect
    }

    function checkIfOrderIsApproved($order_number)
    {
        // TODO: send API request to /orders/show
        return true;
    }

    function parsePaymentToken($payment_token)
    {
        try {
            $arr = json_decode(MonriApi::base64url_decode($payment_token), true);
            return [
                'order_number' => $arr[1],
                'authenticity_token' => $arr[0],
                'return_url' => $arr[1]
            ];
        } catch (Exception $exception) {
            // TODO: write this into logs!
            return null;
        }

    }

    public function resolvePaymentStatus($payment_token)
    {
        $result = $this->parsePaymentToken($payment_token);

        if ($result == null) {
            $this->anErrorOccurred();
        } else {
            $order_number = $result['order_number'];
            if ($this->checkIfOrderIsApproved($order_number)) {
                $this->redirectToOrderApproved($result['return_url']);
            } else {
                $this->redirectToOrderDeclined();
            }
        }
    }


    static function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    static function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    private function anErrorOccurred()
    {

    }

    private function redirectToOrderApproved($return_url)
    {
        MonriApi::redirect($return_url);
    }

    private function redirectToOrderDeclined()
    {

    }

    public function three3dsReturnUrl($order_number, $redirect_url)
    {
        $payment_token = MonriApi::base64url_encode(json_encode([$this->authenticity_token, $order_number, $redirect_url]));
        return plugins_url() . "/woocommerce-monri/payment-result.php?payment_token=$payment_token";
    }
}