<?php

class Monri_WC_Logger {
	/**
	 * @var ?WC_Logger
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
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- This is a logger class, so it does that.
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
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is a logger class, so it does that.
			error_log( $message );
		}
	}

	public static function is_log_enabled() {
		return Monri_WC_Settings::instance()->get_option_bool( 'debug_mode' );
	}
}
