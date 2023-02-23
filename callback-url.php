<?php

defined('MONRI_CALLBACK_IMPL') or die('Invalid request.');

/**
 * This function isn't available in PHP before PHP 8+s, so we'll conduct this small consistency check
 * and implement a failsafe solution:
 */
if (!function_exists('str_ends_with')) {
    /**
     * Checks if a string ends with a given substring.
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    function str_ends_with($haystack, $needle)
    {
        $length = strlen($needle);

        if (!$length) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }
}

/**
 * Sets proper Status header with along with HTTP Status Code and Status Name.
 *
 * @param array $status
 */
function monri_http_status($status = array())
{
    // FastCGI special treatment
    $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
    $http_status = substr(php_sapi_name(), 0, 3) === 'cgi' ? 'Status:' : $protocol;
    header(sprintf('%s %s %s', $http_status, $status[0], $status[1]));
}

/**
 * Prints the error message and exits the process with a given HTTP Status Code.
 *
 * @param $message
 * @param array $status
 */
function monri_error($message, $status = array())
{
    monri_http_status($status);
    header('Content-Type: text/plain');

    echo $message;
    exit((int)$status[0]);
}

/**
 * Completes the request with Status: 200 OK.
 */
function monri_done()
{
    echo '';
    monri_http_status(array(200, 'OK'));
    exit(0);
}

/**
 * Handles the given URL `$callback` as the callback URL for Monri Payment Gateway.
 * This endpoint accepts only POST requests which have their payload in the PHP Input Stream.
 * The payload must be a valid JSON.
 *
 * @param $pathname
 * @param $merchant_key
 * @param $callback
 */
function monri_handle_callback($pathname, $merchant_key, $callback)
{
    if (!str_ends_with($_SERVER['REQUEST_URI'], $pathname)) {
        return;
    }

    $request_method = $_SERVER['REQUEST_METHOD'];

    $bad_request_header = array(400, 'Bad Request');

    if ($request_method !== 'POST') {
        $message = sprintf('Invalid request. Expected POST, received: %s.', $request_method);
        monri_error($message, $bad_request_header);
    }

    // Grabbing read-only stream from the request body.
    $json = file_get_contents('php://input');

    if(!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        monri_error('Authorization header missing.', $bad_request_header);
    }

    // Strip-out the 'WP3-callback' part from the Authorization header.
    $authorization = trim(
        str_replace('WP3-callback', '', $_SERVER['HTTP_AUTHORIZATION'])
    );
    // Calculating the digest...
    $digest = hash('sha512', $merchant_key . $json);

    // ... and comparing it with one from the headers.
    if($digest !== $authorization) {
        monri_error('Authorization header missing.', $bad_request_header);
    }

    $payload = null;
    $json_malformed = 'JSON payload is malformed.';

    // Handling JSON parsing for PHP >= 7.3...
    if(class_exists('JsonException')) {
        try {
            $payload = json_decode($json, true);
        } catch (\JsonException $e) {
            monri_error($json_malformed, $bad_request_header);
        }
    }
    // ... and for PHP <= 7.2.
    else {
        $payload = json_decode($json, true);

        if($payload === null && json_last_error() !== JSON_ERROR_NONE) {
            monri_error($json_malformed, $bad_request_header);
        }
    }

    if (!isset($payload['order_number']) || !isset($payload['status'])) {
        monri_error('Order information not found in response.', array(404, 'Not Found'));
    }

    call_user_func($callback($payload));
    monri_done();
}
