<?php
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
 * This function isn't available in PHP before PHP 8+s, so we'll conduct this small consistency check
 * and implement a failsafe solution:
 */
if (!function_exists('str_starts_with')) {
    /**
     * Checks if a string ends with a given substring.
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    function str_starts_with($haystack, $needle)
    {
        $length = strlen($needle);

        if (!$length) {
            return true;
        }

        return substr($haystack, $length) === $needle;
    }
}

/**
 * This function isn't available in PHP before PHP 8+s, so we'll conduct this small consistency check
 * and implement a failsafe solution:
 */
if (!function_exists('str_contains')) {
    /**
     * Checks if a string ends with a given substring.
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    function str_contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }
}