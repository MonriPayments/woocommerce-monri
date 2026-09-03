<?php

namespace Monri\Tests\Unit;

use Monri_WC_Settings;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;
use Brain\Monkey;
use Mockery;

abstract class TestCase extends PolyfillTestCase {

	/**
	 * Set up the mock environment before each test.
	 */
	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();
		global $test_wp_options, $test_wc_orders;
		$test_wp_options = [];
		$test_wc_orders = [];
	}

	/**
	 * Tear down the mock environment after each test and reset singletons.
	 */
	protected function tear_down(): void {
		Monkey\tearDown();
		Mockery::close();
		$this->reset_singletons();
		parent::tear_down();
	}

	/**
	 * Reset singleton instances to ensure isolated test runs.
	 */
	protected function reset_singletons(): void {
		if ( class_exists( '\Monri_WC_Settings' ) ) {
			$ref = new \ReflectionProperty( Monri_WC_Settings::class, 'instance' );
			if ( PHP_VERSION_ID < 80100 ) {
				$ref->setAccessible( true );
			}
			$ref->setValue( null, null );
		}

		if ( class_exists( '\Monri_WC_Api' ) ) {
			$ref = new \ReflectionProperty( \Monri_WC_Api::class, 'instance' );
			if ( PHP_VERSION_ID < 80100 ) {
				$ref->setAccessible( true );
			}
			$ref->setValue( null, null );
		}

		if ( class_exists( '\Monri_WSPay_WC_Api' ) ) {
			$ref = new \ReflectionProperty( \Monri_WSPay_WC_Api::class, 'instance' );
			if ( PHP_VERSION_ID < 80100 ) {
				$ref->setAccessible( true );
			}
			$ref->setValue( null, null );
		}

		if ( class_exists( '\Monri_WC_Logger' ) ) {
			$ref = new \ReflectionProperty( \Monri_WC_Logger::class, 'log' );
			if ( PHP_VERSION_ID < 80100 ) {
				$ref->setAccessible( true );
			}
			$ref->setValue( null, null );
		}
	}

	/**
	 * Helper to set option in settings mock.
	 *
	 * @param array $settings
	 */
	protected function set_plugin_settings( array $settings ): void {
		global $test_wp_options;
		$test_wp_options[ Monri_WC_Settings::SETTINGS_KEY ] = $settings;
		$test_wp_options[ 'woocommerce_monri_settings' ] = $settings;
	}

	/**
	 * Helper to set custom WordPress option.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	protected function set_wp_option( string $key, $value ): void {
		global $test_wp_options;
		$test_wp_options[ $key ] = $value;
	}

	/**
	 * Helper to get custom WordPress option.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	protected function get_wp_option( string $key, $default = false ) {
		global $test_wp_options;
		return $test_wp_options[ $key ] ?? $default;
	}
}
