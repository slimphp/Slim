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

require_once 'Slim/Log.php';

class MyLogger {

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

class LogTest extends PHPUnit_Framework_TestCase {

    /**
     * Test Log enabling and disabling
     *
     * Pre-conditions:
     * None
     *
     * Post-conditions:
     * A) Logging enabled
     * B) Logging disabled
     * C) Logging enabled
     */
    public function testEnableAndDisableLogging() {
        //Case A
        $log = new Slim_Log();
        $this->assertTrue($log->isEnabled());
        //Case B
        $log->setEnabled(false);
        $this->assertFalse($log->isEnabled());
        //Case C
        $log->setEnabled(true);
        $this->assertTrue($log->isEnabled());
    }

    /**
     * Test Log adapter Logger
     *
     * Pre-conditions:
     * None
     *
     * Post-conditions:
     * Logger is set correctly
     */
    public function testSetsLogger() {
        $log = new Slim_Log();
        $logger = new MyLogger();
        $log->setLogger($logger);
        $this->assertSame($logger, $log->getLogger());
    }

    /**
     * Test Log adapter methods
     *
     * Pre-conditions
     * Log instantiated with MyLogger instance
     *
     * Post-conditions:
     * A) All Log adapter methods return expected results
     * B) All Log adapter methods return false
     */
    public function testLoggerMethods() {
        $log = new Slim_Log();
        $logger = new MyLogger();
        $log->setLogger($logger);
        //Case A: Logging enabled
        $this->assertEquals('debug', $log->debug('Test'));
        $this->assertEquals('info', $log->info('Test'));
        $this->assertEquals('warn', $log->warn('Test'));
        $this->assertEquals('error', $log->error('Test'));
        $this->assertEquals('fatal', $log->fatal('Test'));
        //Case B: Logging disabled
        $log->setEnabled(false);
        $this->assertFalse($log->debug('Test'));
        $this->assertFalse($log->info('Test'));
        $this->assertFalse($log->warn('Test'));
        $this->assertFalse($log->error('Test'));
        $this->assertFalse($log->fatal('Test'));
    }

    /**
     * Test Log adapter methods if no logger set
     *
     * Pre-conditions
     * Log instantiated without associated Logger
     *
     * Post-conditions:
     * All Log adapter methods return false
     */
    public function testLoggerMethodsIfNoLogger() {
        $log = new Slim_Log();
        $this->assertFalse($log->debug('Test'));
        $this->assertFalse($log->info('Test'));
        $this->assertFalse($log->warn('Test'));
        $this->assertFalse($log->error('Test'));
        $this->assertFalse($log->fatal('Test'));
    }

}
