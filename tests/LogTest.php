<?php
/**
 * Slim
 *
 * A simple PHP framework for PHP 5 or newer
 *
 * @author		Josh Lockhart <info@joshlockhart.com>
 * @link		http://slim.joshlockhart.com
 * @copyright	2010 Josh Lockhart
 * 
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

require_once '../slim/Log.php';
require_once 'PHPUnit/Extensions/OutputTestCase.php';

class Logger {
	
	public function debug( $object ) {
		return 'debug';
	}
	
	public function info( $object ) {
		return 'info';
	}
	
	public function warn( $object ) {
		return 'warn';
	}
	
	public function error( $object ) {
		return 'error';
	}
	
	public function fatal( $object ) {
		return 'fatal';
	}
	
}

class LogTest extends PHPUnit_Extensions_OutputTestCase {

	/**
	 * Test Log adapter Logger
	 *
	 * Pre-conditions:
	 * Logger instantiated;
	 *
	 * Post-conditions:
	 * Logger set as Log logger
	 */
	public function testSetsLogger() {
		$logger = new Logger();
		Log::setLogger($logger);
		$this->assertSame($logger, Log::getLogger());
	}
	
	/**
	 * Test Log adapter methods
	 *
	 * Pre-conditions
	 * Logger instantiated and set;
	 *
	 * Post-conditions:
	 * Expected responses are returned proving Logger was called;
	 */
	public function testLoggerMethods() {
		Log::setLogger(new Logger());
		$this->assertEquals('debug', Log::debug('Test'));
		$this->assertEquals('info', Log::info('Test'));
		$this->assertEquals('warn', Log::warn('Test'));
		$this->assertEquals('error', Log::error('Test'));
		$this->assertEquals('fatal', Log::fatal('Test'));
	}
	
	/**
	 * Test Log adapter methods if no logger set
	 *
	 * Pre-conditions
	 * Logger not set
	 *
	 * Post-conditions:
	 * All calls to adapter return false
	 */
	public function testLoggerMethodsIfNoLogger() {
		Log::setLogger(null);
		$this->assertFalse(Log::debug('Test'));
		$this->assertFalse(Log::info('Test'));
		$this->assertFalse(Log::warn('Test'));
		$this->assertFalse(Log::error('Test'));
		$this->assertFalse(Log::fatal('Test'));
	}

}
?>