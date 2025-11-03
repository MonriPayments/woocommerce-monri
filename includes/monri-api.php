<?php

class Monri_WC_Api {

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

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		$this->test_mode = Monri_WC_Settings::instance()->get_option_bool( 'test_mode' );
	}

	/**
	 * @param string $path
	 * @param string $body
	 * @param bool $log_request
	 *
	 * @return SimpleXmlElement|WP_Error
	 */
	private function request( $path, $body, $log_request = true ) {

		if ($log_request) {
			Monri_WC_Logger::log( func_get_args(), __METHOD__ );
		}

		$url = $this->test_mode ? self::TEST_ENDPOINT : self::ENDPOINT;

		$headers = [
			'Content-Type' => 'application/xml',
			'Accept'       => 'application/xml',
		];

		$response = wp_remote_post( "$url$path", array(
			'body'       => $body,
			'headers'    => $headers,
			'user-agent' => 'Monri 3DS Ringer',
			'timeout'    => 15
		) );

		if ($log_request) {
			Monri_WC_Logger::log( $response, __METHOD__ );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( ! in_array( $code, [ 200, 201 ], true ) ) {
			return new \WP_Error( 'monri_api_error', $body ?: $code );
		}

		try {
			$body = new SimpleXmlElement( $body );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'monri_api_error', $body ?: $code );
		}

		return $body;
	}

	/**
	 * @param string $order_number
	 *
	 * @return SimpleXmlElement|WP_Error
	 */
	public function orders_show( $order_number ) {

		$authenticity_token = Monri_WC_Settings::instance()->get_option( 'monri_authenticity_token' );

		$payload = new SimpleXMLElement( "<order></order>" );
		$payload->addChild( 'order-number', $order_number );
		$payload->addChild( 'authenticity-token', $authenticity_token );
		$payload->addChild( 'digest', $this->digest( $order_number ) );

		return $this->request( '/orders/show', $payload->asXML(), false );
	}

	/**
	 * @param string $order_number
	 * @param float|string $amount
	 * @param string $currency
	 *
	 * @return SimpleXmlElement|WP_Error
	 */
	public function refund( $order_number, $amount, $currency ) {

		$authenticity_token = Monri_WC_Settings::instance()->get_option( 'monri_authenticity_token' );

		$payload = new SimpleXMLElement( "<transaction></transaction>" );
		$payload->addChild( 'order-number', $order_number );
		$payload->addChild( 'amount', $amount );
		$payload->addChild( 'currency', $currency );
		$payload->addChild( 'authenticity-token', $authenticity_token );
		$payload->addChild( 'digest', $this->digestAPI( $order_number, $amount, $currency ) );

		return $this->request( "/transactions/$order_number/refund.xml", $payload->asXML() );
	}

	/**
	 * @param string $order_number
	 * @param float|string $amount
	 * @param string $currency
	 *
	 * @return SimpleXmlElement|WP_Error
	 */
	public function capture( $order_number, $amount, $currency ) {

		$authenticity_token = Monri_WC_Settings::instance()->get_option( 'monri_authenticity_token' );

		$payload = new SimpleXMLElement( "<transaction></transaction>" );
		$payload->addChild( 'order-number', $order_number );
		$payload->addChild( 'amount', $amount );
		$payload->addChild( 'currency', $currency );
		$payload->addChild( 'authenticity-token', $authenticity_token );
		$payload->addChild( 'digest', $this->digestAPI( $order_number, $amount, $currency ) );

		return $this->request( "/transactions/$order_number/capture.xml", $payload->asXML() );
	}

	/**
	 * @param string $order_number
	 * @param float|string $amount
	 * @param string $currency
	 *
	 * @return SimpleXmlElement|WP_Error
	 */
	public function void( $order_number, $amount, $currency ) {

		$authenticity_token = Monri_WC_Settings::instance()->get_option( 'monri_authenticity_token' );

		$payload = new SimpleXMLElement( "<transaction></transaction>" );
		$payload->addChild( 'order-number', $order_number );
		$payload->addChild( 'amount', $amount );
		$payload->addChild( 'currency', $currency );
		$payload->addChild( 'authenticity-token', $authenticity_token );
		$payload->addChild( 'digest', $this->digestAPI( $order_number, $amount, $currency ) );

		return $this->request( "/transactions/$order_number/void.xml", $payload->asXML() );
	}

	/**
	 * @param string $order_number
	 *
	 * @return string
	 */
	private function digest( $order_number ) {

		$merchant_key = Monri_WC_Settings::instance()->get_option( 'monri_merchant_key' );

		return hash( 'SHA1', $merchant_key . $order_number );
	}

	/**
	 * @param string $order_number
	 * @param int $amount
	 * @param string $currency
	 *
	 * @return string
	 */
	private function digestAPI( $order_number, $amount, $currency ) {

		$merchant_key = Monri_WC_Settings::instance()->get_option( 'monri_merchant_key' );

		return hash( 'SHA1', $merchant_key . $order_number . $amount . $currency );
	}
}
