<?php

class MonriApi
{
    private $merchant_key;
    private $authenticity_token;
    private $test_mode;
    private $form_language;

    public function __construct()
    {
        $monri_settings = get_option('woocommerce_monri_settings');

        $this->merchant_key = $monri_settings['monri_merchant_key'];
        $this->authenticity_token = $monri_settings['monri_authenticity_token'];
        $this->test_mode = $monri_settings['test_mode'];
        $this->form_language = $monri_settings['form_language'];
    }

    private static function redirect($get_return_url)
    {
        header('Location: ' . $get_return_url);
        exit(0);
    }

    private function calculateDigest($order_number) {
        return hash('SHA1', $this->merchant_key . $order_number);
    }

    function checkIfOrderIsApproved($order_number)
    {
        $paResXMLPayload = "<?xml version='1.0' encoding='UTF-8'?>
                <secure-message>              
                  <MD>{$_POST['MD']}</MD>
                  <PaRes>{$_POST['PaRes']}</PaRes>
                </secure-message>";
        $paResResponse = $this->curlXml('/pares', $paResXMLPayload);

        $digest = $this->calculateDigest($order_number);
        $ordersEndpoint = '/orders/show';
        $ordersXMLPayload = '<?xml version="1.0" encoding="UTF-8"?>
              <order>
                <order-number>' . $order_number . '</order-number>
                <authenticity-token>' . $this->authenticity_token . '</authenticity-token>
                <digest>' . $this->calculateDigest($order_number) . '</digest>
            </order>';

        $orderResponse = $this->curlXml('/orders/show', $ordersXMLPayload);

        try {
            $result = new \SimpleXmlElement($orderResponse['body']);
        } catch (\Exception $e) {
            die('XML parsing failed.');
        }

        if (!$result !== false) {
            return false;
        }

        $this->handleWooCommerceOrder($result, $order_number);

        return trim($result->status) !== 'declined';
    }

    function handleWooCommerceOrder($result, $order_number)
    {
        global $woocommerce;
        $order = new WC_Order($order_number);

        require_once __DIR__ . '/class-monri.php';

        if ($this->form_language == "en") {
            $lang = WC_Monri::get_en_translation();
        } elseif ($this->form_language == "ba-hr" || $this->form_language == "hr") {
            $lang = WC_Monri::get_ba_hr_translation();
        } elseif ($this->form_language == "sr") {
            $lang = WC_Monri::get_sr_translation();
        }

        if (isset($result->status) && trim($result->status) === 'approved') {
            // Payment has been successful
            $order->update_status('wc-completed', __($lang['PAYMENT_COMPLETED'], 'monri'));

            // Empty the cart (Very important step)
            $woocommerce->cart->empty_cart();
            WC()->cart->empty_cart();
        } else {

            $order->update_status('failed');
            $order->add_order_note('Failed');
            $order->add_order_note('Thank you for shopping with us. However, the transaction has been declined.');
        }
    }

    function curlXml($endpoint, $payload)
    {
        $url = $this->test_mode ? 'https://ipgtest.monri.com' : 'https://ipg.monri.com';
        return $this->request('POST', $url . $endpoint, [], $payload, [
            'Content-Type' => 'application/xml',
            'Accept' => 'application/xml',
        ]);
    }

    function parsePaymentToken($payment_token)
    {
        try {
            $arr = json_decode(MonriApi::base64url_decode($payment_token), true);
            return [
                'authenticity_token' => $arr[0],
                'order_number' => $arr[1],
                'return_url' => $arr[2]
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
        MonriApi::redirect(wc_get_cart_url());
    }

    public function three3dsReturnUrl($order_number, $redirect_url)
    {
        $payment_token = MonriApi::base64url_encode(json_encode([$this->authenticity_token, $order_number, $redirect_url]));
        return plugins_url() . "/woocommerce-monri/payment-result.php?payment_token=$payment_token";
    }

    /**
     * @param string $route
     * @param array $query
     * @param $body
     * @param array $headers
     * @return HttpResponse
     * @throws Exception
     */
    private function request($method, $route, $query, $body, $headers = [])
    {
        $ch = curl_init();

        $queryStrings = null;

        if (sizeof($query)) {
            $queryStrings = '?' . http_build_query($query);
        }

        $url = $route . $queryStrings;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Monri 3DS Ringer');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, iterator_to_array($this->processRequestHeaders($headers)));

        if ($body != null) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response_body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (!$response_body) {
            throw new Exception(curl_error($ch), curl_errno($ch));
        }

        curl_close($ch);

        return [
            'statusCode' => $code,
            'body' => $response_body,
        ];
    }

    /**
     * @param array $headers
     * @return Generator
     */
    private function processRequestHeaders($headers)
    {
        foreach ($headers as $headerName => $headerValue) {
            yield sprintf('%s: %s', $headerName, $headerValue);
        }
    }

}
