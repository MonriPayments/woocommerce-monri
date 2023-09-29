<?php

class MonriApi
{
    private $merchant_key;
    private $authenticity_token;
    private $test_mode;
    private $form_language;
    private $payment_gateway_service;

    private $monri_ws_pay_form_shop_id;
    private $monri_authenticity_token;
    private $monri_ws_pay_form_secret;
    private $monri_merchant_key;

    private $monri_ws_pay_form_tokenization_enabled;
    private $monri_ws_pay_form_tokenization_shop_id;
    private $monri_ws_pay_form_tokenization_secret;
    private $__tokenization_enabled;

    public function __construct()
    {
        $monri_settings = get_option('woocommerce_monri_settings');

        $this->merchant_key = $monri_settings['monri_merchant_key'];
        $this->authenticity_token = $monri_settings['monri_authenticity_token'];
        $this->test_mode = $monri_settings['test_mode'];
        $this->form_language = $monri_settings['form_language'];
        $this->payment_gateway_service = $monri_settings['monri_payment_gateway_service'];
        $this->monri_ws_pay_form_shop_id = $monri_settings['monri_ws_pay_form_shop_id'];
        $this->monri_authenticity_token = $monri_settings['monri_authenticity_token'];
        $this->monri_ws_pay_form_secret = $monri_settings['monri_ws_pay_form_secret'];
        $this->monri_merchant_key = $monri_settings['monri_merchant_key'];
        $this->monri_ws_pay_form_tokenization_enabled = $monri_settings['monri_ws_pay_form_tokenization_enabled'];
        $this->monri_ws_pay_form_tokenization_shop_id = $monri_settings['monri_ws_pay_form_tokenization_shop_id'];
        $this->monri_ws_pay_form_tokenization_secret = $monri_settings['monri_ws_pay_form_tokenization_secret'];
    }

    private function is_ws_pay()
    {
        return $this->payment_gateway_service == 'monri-ws-pay';
    }

    private function is_web_pay()
    {
        return $this->payment_gateway_service == 'monri-web-pay';
    }

    public function api_username()
    {
        if ($this->is_ws_pay()) {
            if ($this->tokenization_enabled()) {
                return $this->monri_ws_pay_form_tokenization_shop_id;
            } else {
                return $this->monri_ws_pay_form_shop_id;
            }
        } else {
            return $this->monri_authenticity_token;
        }
    }

    public function api_password()
    {
        if ($this->is_ws_pay()) {
            if ($this->tokenization_enabled()) {
                return $this->monri_ws_pay_form_tokenization_secret;
            } else {
                return $this->monri_ws_pay_form_secret;
            }
        } else {
            return $this->monri_merchant_key;
        }
    }

    private static function redirect($get_return_url)
    {
        header('Location: ' . $get_return_url);
        exit(0);
    }

    private function calculateDigest($order_number)
    {
        return hash('SHA1', $this->merchant_key . $order_number);
    }

    public function tokenization_requested()
    {
        // It's always the case
        return true;
    }

    // Checks if tokenization is enabled by:
    // - checking if user is logged in
    // - checking if integration is wspay -> in that case it checks boolean flag in options
    // - checking if integration is webpay -> in that case ot's false since tokenization is not enabled for WebPay
    public function tokenization_enabled()
    {
        if (isset($this->__tokenization_enabled)) {
            return $this->__tokenization_enabled;
        }
        // Tokenization is only enabled for logged in users
        if (!is_user_logged_in()) {
            $this->__tokenization_enabled = false;
        } else if ($this->is_ws_pay()) {
            $this->__tokenization_enabled = $this->monri_ws_pay_form_tokenization_enabled == 'yes';
        } else if ($this->is_web_pay()) {
            $this->__tokenization_enabled = false;
        } else {
            $this->__tokenization_enabled = false;
        }

        return $this->__tokenization_enabled;
    }

    public function checkIfOrderIsApproved($order_number)
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

    public function handleWooCommerceOrder($result, $order_number)
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

    public function curlXml($endpoint, $payload)
    {
        $url = $this->test_mode ? 'https://ipgtest.monri.com' : 'https://ipg.monri.com';
        return $this->request('POST', $url . $endpoint, [], $payload, [
            'Content-Type' => 'application/xml',
            'Accept' => 'application/xml',
        ]);
    }

    public function parsePaymentToken($payment_token)
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

    /**
     * @param array $lang
     * @param $woocommerce
     * @return array
     */
    public function monri_ws_pay_handle_redirect($lang)
    {
        global $woocommerce;
        $order_id = $_REQUEST['ShoppingCartID'];

        if ($order_id != '') {
            try {
                $order = new WC_Order($order_id);

                if ($order->status === 'completed') {
                    return [
                        'success' => true,
                        'message' => $lang["THANK_YOU_SUCCESS"],
                        'class' => 'woocommerce_message'
                    ];
                } else {
                    $digest = $_REQUEST['Signature'];
                    $success = isset($_REQUEST['Success']) ? $_REQUEST['Success'] : '0';
                    $approval_code = isset($_REQUEST['ApprovalCode']) ? $_REQUEST['ApprovalCode'] : null;
                    $shop_id = $this->api_username();
                    $secret_key = $this->api_password();
                    // ShopID
                    // SecretKey
                    // ShoppingCartID
                    // SecretKey
                    // Success
                    // SecretKey
                    // ApprovalCode
                    // SecretKey
                    $digest_parts = array(
                        $shop_id,
                        $secret_key,
                        $order_id,
                        $secret_key,
                        $success,
                        $secret_key,
                        $approval_code,
                        $secret_key,
                    );
                    $check_digest = hash('sha512', join("", $digest_parts));
                    if ($check_digest != $digest) {
                        return [
                            'success' => false,
                            'message' => $lang["SECURITY_ERROR"],
                            'class' => 'error'
                        ];
                    } else {
                        $trx_authorized = $success == '1' && !empty($approval_code);
                        if ($trx_authorized) {
                            $order->add_order_note($lang["MONRI_SUCCESS"] . $_REQUEST['ApprovalCode']);
                            $order->add_order_note($lang["THANK_YOU_SUCCESS"]);
                            $order->payment_complete();
                            $woocommerce->cart->empty_cart();
                            $tokenized = $this->save_token_details_ws_pay();
                            if ($tokenized != null) {
                                $order->add_order_note('Tokenized result: ' . $tokenized);
                            }

                            return [
                                'success' => true,
                                'message' => $lang["THANK_YOU_SUCCESS"],
                                'class' => 'woocommerce_message'
                            ];
                        } else {
                            $this->order_failed($order, $lang['THANK_YOU_DECLINED']);
                            return [
                                'success' => false,
                                'message' => $lang['THANK_YOU_DECLINED'],
                                'class' => 'woocommerce_error'
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                // $errorOccurred = true;
                $msg = "Error";
            }
        } else {
            return [
                'success' => false,
                'message' => $lang['THANK_YOU_DECLINED'],
                'class' => 'woocommerce_error'
            ];
        }
    }

    function save_token_details_ws_pay()
    {
        // is_user_logged_in()
        if (!is_user_logged_in()) {
            return 'User not logged in';
        }

        // We expect token, token number and token exp in success url for tokenized cards
        if (!isset($_REQUEST['Token']) || !isset($_REQUEST['TokenNumber']) || !isset($_REQUEST['TokenExp'])) {
            return null;
        }

        $user_id = get_current_user_id();
        $tokenized_cards = $this->get_tokenized_cards($user_id);
        // We need to save:
        // - current shop id
        // - token
        // - TokenNumber
        // - TokenExp
        $token_metadata = [
            'shop_id' => $this->api_username(),
            'token' => $_REQUEST['Token'],
            'token_number' => $_REQUEST['TokenNumber'],
            'token_exp' => $_REQUEST['TokenExp']
        ];
        array_push($tokenized_cards, $token_metadata);
        // The new meta field ID if a field with the given key didn't exist and was therefore added, 
        // true on successful update, 
        // false on failure or if the value passed to the function is the same as the one that is already in the database.
        $rv = update_metadata('user', $user_id, 'ws-pay-tokenized-cards', json_encode($tokenized_cards));
        if ($rv) {
            return 'Update success';
        } else {
            return 'Update failed';
        }
    }

    public function get_tokenized_cards_for_current_user()
    {
        if (!is_user_logged_in()) {
            return [];
        }
        return $this->get_tokenized_cards(get_current_user_id());
    }

    public function get_tokenized_cards($user_id)
    {
        $tokenized_cards = get_user_meta($user_id, 'ws-pay-tokenized-cards', true);
        if ($tokenized_cards == false) {
            $tokenized_cards = '[]';
        }
        return json_decode($tokenized_cards == '' ? '[]' : $tokenized_cards, true);
    }

    /**
     * @param WC_Order $order
     * @return void
     */
    public function order_failed($order, $message)
    {
        $order->update_status('failed');
        $order->add_order_note('Failed');
        $order->add_order_note($message);
    }

}