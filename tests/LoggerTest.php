<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@joshlockhart.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.5.0
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

set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());

require_once 'Slim/Logger.php';

class LoggerTest extends PHPUnit_Framework_TestCase {

    protected $logDir;

    public function setUp() {
        $this->logDir = dirname(__FILE__) . '/logs';
    }

    public function tearDown() {
        @unlink($this->logDir . '/' . strftime('%Y-%m-%d') . '.log');
    }

    /**
     * Test Logger instantiation
     *
     * Pre-conditions:
     * Case A: Logger instantiated with existing directory
     * Case B: Logger instantiated with non existing directory
     * Case C: Logger instantiated with non existing directory
     * Case D: Logger instantiated with valid level
     * Case E: Logger instantiated with invalid level
     *
     * Post-conditions:
     * Case A: Logger level is 4
     * Case B: RuntimeException not thrown during instantiation with invalid log directory
     * Case C: RuntimeException thrown during log method invocation with invalid log directory
     * Case D: Logger level is 1
     * Case E: InvalidArgumentException thrown
     */
    public function testLoggerInstantiation() {
        //Case A
        $l1 = new Slim_Logger($this->logDir);
        $this->assertEquals(4, $l1->getLevel());
        //Case B
        try {
            $l2 = new Slim_Logger('./foo');
        } catch ( RuntimeException $e) {
            $this->fail('Instantiating Slim_Logger with bad log directory should only fail when invoking Slim_Logger::log');
        }
        //Case C
        try {
            $l2->warn('Foo');
            $this->fail('Did not catch RuntimeException when invoking Slim_Logger::log with invalid log directory');
        } catch ( RuntimeException $e ) {}
        //Case D
        $l3 = new Slim_Logger($this->logDir, 1);
        $this->assertEquals(1, $l3->getLevel());
        //Case E
        try {
            $l4 = new Slim_Logger($this->logDir, 5);
            $this->fail("Did not catch RuntimeException thrown from Logger with invalid level");
        } catch ( InvalidArgumentException $e) {}
    }

    /**
     * Test debug log
     */
    public function testLogsDebug() {
        $l = new Slim_Logger($this->logDir, 4);
        $message = '[DEBUG] ' . date('c') . ' - ' . "Test Info\r\n";
        $l->debug('Test Info');
        $this->assertEquals(file_get_contents($l->getFile()), $message);
    }

    /**
     * Test info log
     */
    public function testLogsInfo() {
        $l = new Slim_Logger($this->logDir, 3);
        $message = '[INFO] ' . date('c') . ' - ' . "Test Info\r\n";
        $l->debug('Test Info');
        $l->info('Test Info');
        $this->assertEquals(file_get_contents($l->getFile()), $message);
    }

    /**
     * Test info log
     */
    public function testLogsWarn() {
        $l = new Slim_Logger($this->logDir, 2);
        $message = '[WARN] ' . date('c') . ' - ' . "Test Info\r\n";
        $l->info('Test Info');
        $l->warn('Test Info');
        $this->assertEquals(file_get_contents($l->getFile()), $message);
    }

    /**
     * Test info log
     */
    public function testLogsError() {
        $l = new Slim_Logger($this->logDir, 1);
        $message = '[ERROR] ' . date('c') . ' - ' . "Test Info\r\n";
        $l->warn('test Info');
        $l->error('Test Info');
        $this->assertEquals(file_get_contents($l->getFile()), $message);
    }

    /**
     * Test info log
     */
    public function testLogsFatal() {
        $l = new Slim_Logger($this->logDir, 0);
        $message = '[FATAL] ' . date('c') . ' - ' . "Test Info\r\n";
        $l->error('Test Info');
        $l->fatal('Test Info');
        $this->assertEquals(file_get_contents($l->getFile()), $message);
    }

}
