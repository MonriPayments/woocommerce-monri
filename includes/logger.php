<?php

class Monri_WC_Logger {
	/**
	 * @var WC_Logger
	 */
	private static $log;

	/**
	 * Logging method
	 *
	 * @param mixed $message
	 * @param string|null $source
	 */
	public static function log( $message, $source = null ) {

		if ( ! is_string( $message ) ) {
			$message = print_r( $message, true );
		}

		if ( $source ) {
			$message = "[$source] $message";
		}

		if ( self::is_log_enabled() ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			self::$log->add( 'monri', $message );
		}

		if ( WP_DEBUG && WP_DEBUG_LOG ) {
			error_log( $message );
		}
	}

	public static function is_log_enabled() {
		return Monri_WC_Settings::instance()->get_option_bool( 'debug_mode' );
	}
}
