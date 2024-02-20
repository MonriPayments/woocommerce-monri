<?php

class Monri_WC_Callback
{
	public function __construct()
	{
	}
	
	public function init() {
		
	}

	public function _parse_request() {
		$monri_settings = get_option('woocommerce_monri_settings');


		if (!is_array($monri_settings) || !isset($monri_settings['callback_url_endpoint'])) {
			return;
		}

		$monri_callback_url_endpoint = isset($monri_settings['callback_url_endpoint']) ?
			$monri_settings['callback_url_endpoint'] : '/monri-callback';

		$merchant_key = null;

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_ends_with($_SERVER['REQUEST_URI'], $monri_callback_url_endpoint)) {
			if (!isset($monri_settings['monri_merchant_key'])) {
				$this->error('Monri key is not defined or does not exist.', array(404, 'Not Found'));
			}

			$merchant_key = $monri_settings['monri_merchant_key'];
		}

		$this->handle_callback();
	}

	/**
	 * Sets proper Status header with along with HTTP Status Code and Status Name.
	 *
	 * @param array $status
	 */
	private function http_status(array $status = array()) {
		// FastCGI special treatment
		$protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';
		$http_status = substr(php_sapi_name(), 0, 3) === 'cgi' ? 'Status:' : $protocol;
		header(sprintf('%s %s %s', $http_status, $status[0], $status[1]));
	}

	/**
	 * Prints the error message and exits the process with a given HTTP Status Code.
	 *
	 * @param $message
	 * @param array $status
	 */
	private function error($message, array $status = array()) {
		monri_http_status($status);
		header('Content-Type: text/plain');

		echo $message;
		exit((int)$status[0]);
	}

	/**
	 * Completes the request with Status: 200 OK.
	 */
	private function done() {
		monri_http_status(array(200, 'OK'));
		exit(0);
	}

	private function redirect($url) {
		header("Location: " . $url);
		exit(0);
	}

	/**
	 * Handles the given URL `$callback` as the callback URL for Monri Payment Gateway.
	 * This endpoint accepts only POST requests which have their payload in the PHP Input Stream.
	 * The payload must be a valid JSON.
	 *
	 */
	public function handle_callback() {

		if (!str_ends_with($_SERVER['REQUEST_URI'], $pathname)) {
			return;
		}

		$request_method = $_SERVER['REQUEST_METHOD'];

		$bad_request_header = array(400, 'Bad Request');

		if ($request_method !== 'POST') {
			$message = sprintf('Invalid request. Expected POST, received: %s.', $request_method);
			$this->error($message, $bad_request_header);
		}

		// Grabbing read-only stream from the request body.
		$json = file_get_contents('php://input');

		if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$this->error('Authorization header missing.', $bad_request_header);
		}

		// Strip-out the 'WP3-callback' part from the Authorization header.
		$authorization = trim(
			str_replace('WP3-callback', '', $_SERVER['HTTP_AUTHORIZATION'])
		);
		// Calculating the digest...
		$digest = hash('sha512', $merchant_key . $json);

		// ... and comparing it with one from the headers.
		if ($digest !== $authorization) {
			$this->error('Authorization header missing.', $bad_request_header);
		}

		$payload = null;
		$json_malformed = 'JSON payload is malformed.';

		// Handling JSON parsing for PHP >= 7.3...
		if (class_exists('JsonException')) {
			try {
				$payload = json_decode($json, true);
			} catch (\JsonException $e) {
				$this->error($json_malformed, $bad_request_header);
			}
		} // ... and for PHP <= 7.2.
		else {
			$payload = json_decode($json, true);

			if ($payload === null && json_last_error() !== JSON_ERROR_NONE) {
				$this->error($json_malformed, $bad_request_header);
			}
		}

		if (!isset($payload['order_number']) || !isset($payload['status'])) {
			$this->error('Order information not found in response.', array(404, 'Not Found'));
		}

		$order_number = $payload['order_number'];

		try {
			$order = wc_get_order($order_number);

			$skip_statuses = array('cancelled', 'failed', 'refunded');

			if (in_array($order->get_status(), $skip_statuses)) {
				monri_done();
			}

			if ($payload['status'] === 'approved') {
				$note = sprintf(
					'Order #%s-%s is successfully processed, ref. num. %s: %s.',
					$payload['id'], $order_number, $payload['reference_number'], $payload['response_message']
				);

				$order->update_status('wc-completed', $note);
				$order->add_order_note($note);
			}
		} catch (\Exception $e) {
			$message = sprintf('Order ID: %s not found or does not exist.', $order_number);
			$this->error($message, array(404, 'Not Found'));
		}

		monri_done();
	}
}
