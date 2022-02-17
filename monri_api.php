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

    function checkIfOrderIsApproved($order_number)
    {
        // TODO: send API request to /orders/show
        return true;
    }

    function parsePaymentToken($payment_token)
    {
        try {
            $arr = json_decode($this->base64url_decode($payment_token));
            return [
                'order_number' => $arr[1],
                'authenticity_token' => $arr[0]
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
            return $this->anErrorOccurred();
        }

        if ($this->checkIfOrderIsApproved($result['order_number'])) {
            return $this->redirectToOrderApproved();
        } else {
            return $this->redirectToOrderDeclined();
        }
    }


    function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    private function anErrorOccurred()
    {

    }

    private function redirectToOrderApproved()
    {

    }

    private function redirectToOrderDeclined()
    {

    }
}