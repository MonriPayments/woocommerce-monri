<?php

namespace Monri\Tests\Unit;

use Monri_WC_Logger;
use ReflectionProperty;

class LoggerTest extends TestCase {

	/**
	 * @covers Monri_WC_Logger::is_log_enabled
	 */
	public function test_is_log_enabled(): void {
		$this->set_plugin_settings( [ 'debug_mode' => 'yes' ] );
		$this->assertTrue( Monri_WC_Logger::is_log_enabled() );

		$this->set_plugin_settings( [ 'debug_mode' => 'no' ] );
		$this->assertFalse( Monri_WC_Logger::is_log_enabled() );
	}

	/**
	 * @covers Monri_WC_Logger::log
	 */
	public function test_log_records_message_when_enabled(): void {
		$this->set_plugin_settings( [ 'debug_mode' => 'yes' ] );

		Monri_WC_Logger::log( [ 'order_id' => '123' ], 'OrderProcess' );

		$ref = new ReflectionProperty( Monri_WC_Logger::class, 'log' );
		if ( PHP_VERSION_ID < 80100 ) {
			$ref->setAccessible( true );
		}
		$logger_instance = $ref->getValue();

		$this->assertNotNull( $logger_instance );
		$this->assertCount( 1, $logger_instance->logs );
		$this->assertSame( 'monri', $logger_instance->logs[0]['handle'] );
		$this->assertStringContainsString( '[OrderProcess]', $logger_instance->logs[0]['message'] );
		$this->assertStringContainsString( '123', $logger_instance->logs[0]['message'] );
	}

	/**
	 * @covers Monri_WC_Logger::log
	 */
	public function test_log_skips_when_disabled(): void {
		$this->set_plugin_settings( [ 'debug_mode' => 'no' ] );

		Monri_WC_Logger::log( 'Test Message', 'Test' );

		$ref = new ReflectionProperty( Monri_WC_Logger::class, 'log' );
		if ( PHP_VERSION_ID < 80100 ) {
			$ref->setAccessible( true );
		}
		$logger_instance = $ref->getValue();

		$this->assertNull( $logger_instance );
	}
}
