<?php

class Monri_WC_Api
{
	public const ENDPOINT = 'https://ipg.monri.com';
	public const TEST_ENDPOINT = 'https://ipgtest.monri.com';

	/**
	 * @var Monri_WC_Api
	 */
	private static $instance;

	/**
	 * @var bool
	 */
	public $test_mode = true;

	/**
	 * @return Monri_WC_Api
	 */
	public static function instance() {

		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		$this->test_mode = Monri_WC_Settings::instance()->get_option_bool('test_mode');
	}

	/**
	 * @param $path
	 * @param $body
	 *
	 * @return SimpleXmlElement|WP_Error
	 */
	private function request($path, $body) {

		$url = $this->test_mode ? self::TEST_ENDPOINT : self::ENDPOINT;

		$headers = [
			'Content-Type' => 'application/xml',
			'Accept' => 'application/xml',
		];

		$response = wp_remote_post("$url$path", array(
			'body' => $body,
			'headers' => $headers,
			'user-agent' => 'Monri 3DS Ringer',
			'timeout' => 15
		));

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( !in_array( $code, [ 200, 201 ], true ) ) {
			return new \WP_Error('monri_api_error', $body ?: $code);
		}

		try {
			$body = new SimpleXmlElement($body);
		} catch (\Exception $e) {
			return new \WP_Error('monri_api_error', $body ?: $code);
		}

		/*
		return [
			'statusCode' => $code,
			'body' => $response_body,
		];
		*/

		return $body;
	}

	/**
	 * @param string $order_number
	 *
	 * @return SimpleXmlElement|WP_Error
	 */
	public function orders_show($order_number) {

		$authenticity_token = Monri_WC_Settings::instance()->get_option('monri_authenticity_token');

		$payload = '<?xml version="1.0" encoding="UTF-8"?>
              <order>
                <order-number>' . $order_number . '</order-number>
                <authenticity-token>' . $authenticity_token . '</authenticity-token>
                <digest>' . $this->digest($order_number) . '</digest>
            </order>';

		return $this->request('/orders/show', $payload);
	}

	/**
	 * @param string $order_number
	 * @param float|string $amount
	 * @param string $currency
	 *
	 * @return SimpleXmlElement|WP_Error
	 */
	public function refund($order_number, $amount, $currency) {

		$authenticity_token = Monri_WC_Settings::instance()->get_option('monri_authenticity_token');

		$payload = '<?xml version="1.0" encoding="UTF-8"?>
              <transaction>
                <amount>' . $amount . '</amount>
                <currency>' . $currency . '</currency>
                <order-number>' . $order_number . '</order-number>
                <authenticity-token>' . $authenticity_token . '</authenticity-token>
                <digest>' . $this->digest($order_number) . '</digest>
            </transaction>';

		return $this->request("/transactions/$order_number/refund.xml", $payload);
	}

	/**
	 * @param array $post
	 *
	 * @return SimpleXmlElement|WP_Error
	 */
	public function pares($post) {

		$xml = "<?xml version='1.0' encoding='UTF-8'?>
                <secure-message>              
                  <MD>{$post['MD']}</MD>
                  <PaRes>{$post['PaRes']}</PaRes>
                </secure-message>";

		return $this->request('/pares', $xml);
	}

	/**
	 * @param string $order_number
	 *
	 * @return string
	 */
	private function digest($order_number) {

		$merchant_key = Monri_WC_Settings::instance()->get_option('monri_merchant_key');
		return hash('SHA1', $merchant_key . $order_number);
	}

}
