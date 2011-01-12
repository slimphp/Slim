<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author		Josh Lockhart
 * @link		http://www.slimframework.com
 * @copyright	2011 Josh Lockhart
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

require_once '../slim/Logger.php';
require_once 'PHPUnit/Extensions/OutputTestCase.php';

class TestLogger extends Logger {
	
	protected function write( $data ) {
		print $data;
	}
	
}

class LoggerTest extends PHPUnit_Extensions_OutputTestCase {

	/**
	 * Test Logger instantiation
	 *
	 * Pre-conditions:
	 * Case A: Logger instantiated with existing directory
	 * Case B: Logger instantiated with non existing directory
	 * Case C: Logger instantiated with valid level
	 * Case D: Logger instantiated with invalid level
	 *
	 * Post-conditions:
	 * Case A: Logger created
	 * Case B: RuntimeException thrown
	 * Case C: Logger created
	 * Case D: InvalidArgumentException thrown
	 */
	public function testLoggerInstantiation() {
		//Case A
		$l1 = new Logger('./logs');
		$this->assertEquals(4, $l1->getLevel());
		//Case B
		try {
			$l2 = new Logger('./foo');
			$this->fail("Did not catch RuntimeException thrown from Logger with non-existant directory");
		} catch ( RuntimeException $e) {}
		//Case C
		$l3 = new Logger('./logs', 1);
		$this->assertEquals(1, $l3->getLevel());
		//Case D
		try {
			$l4 = new Logger('./logs', 5);
			$this->fail("Did not catch RuntimeException thrown from Logger with invalid level");
		} catch ( InvalidArgumentException $e) {}
	}
	
	/**
	 * Test debug log
	 */
	public function testLogsDebug() {
		$l = new TestLogger('./logs', 4);
		$this->expectOutputString('[DEBUG] ' . date('c') . ' - ' . "Test Info\r\n");
		$l->debug('Test Info');
	}
	
	/**
	 * Test info log
	 */
	public function testLogsInfo() {
		$l = new TestLogger('./logs', 3);
		$this->expectOutputString('[INFO] ' . date('c') . ' - ' . "Test Info\r\n");
		$l->debug('Test Info');
		$l->info('Test Info');
	}
	
	/**
	 * Test info log
	 */
	public function testLogsWarn() {
		$l = new TestLogger('./logs', 2);
		$this->expectOutputString('[WARN] ' . date('c') . ' - ' . "Test Info\r\n");
		$l->info('Test Info');
		$l->warn('Test Info');
	}
	
	/**
	 * Test info log
	 */
	public function testLogsError() {
		$l = new TestLogger('./logs', 1);
		$this->expectOutputString('[ERROR] ' . date('c') . ' - ' . "Test Info\r\n");
		$l->warn('test Info');
		$l->error('Test Info');
	}
	
	/**
	 * Test info log
	 */
	public function testLogsFatal() {
		$l = new TestLogger('./logs', 0);
		$this->expectOutputString('[FATAL] ' . date('c') . ' - ' . "Test Info\r\n");
		$l->error('Test Info');
		$l->fatal('Test Info');
	}

}
?>