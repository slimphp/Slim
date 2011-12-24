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

require 'Slim/Log.php';

class MyApp {
    public function call ( &$env ) {
        if ( isset($env['slim.log']) ) {
            return array(200, array(), 'True');
        } else {
            return array(500, array(), 'False');
        } 
    }
}

class MyWriter {
    public function write( $object ) {
        echo (string)$object;
        return true;
    }
}

class LogTest extends PHPUnit_Extensions_OutputTestCase {
    public function testEnabled() {
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $this->assertTrue($log->isEnabled()); //<-- Default case
        $log->setEnabled(true);
        $this->assertTrue($log->isEnabled());
        $log->setEnabled(false);
        $this->assertFalse($log->isEnabled());
    }

    public function testGetLevel() {
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $this->assertEquals(4, $log->getLevel());
    }

    public function testSetLevel() {
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $log->setLevel(2);
        $this->assertEquals(2, $log->getLevel());
    }

    public function testSetInvalidLevel() {
        $this->setExpectedException('InvalidArgumentException');
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $log->setLevel(5);
    }

    public function testLogDebug() {
        $this->expectOutputString('DEBUG: Debug');
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $result = $log->debug('Debug');
        $this->assertTrue($result);
    }

    public function testLogDebugExcludedByLevel() {
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $log->setLevel(3);
        $this->assertFalse($log->debug('Debug'));
    }

    public function testLogInfo() {
        $this->expectOutputString('INFO: Info');
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $result = $log->info('Info');
        $this->assertTrue($result);
    }

    public function testLogInfoExcludedByLevel() {
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $log->setLevel(2);
        $this->assertFalse($log->info('Info'));
    }

    public function testLogWarn() {
        $this->expectOutputString('WARN: Warn');
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $result = $log->warn('Warn');
        $this->assertTrue($result);
    }

    public function testLogWarnExcludedByLevel() {
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $log->setLevel(1);
        $this->assertFalse($log->warn('Warn'));
    }

    public function testLogError() {
        $this->expectOutputString('ERROR: Error');
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $result = $log->error('Error');
        $this->assertTrue($result);
    }

    public function testLogErrorExcludedByLevel() {
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $log->setLevel(0);
        $this->assertFalse($log->error('Error'));
    }

    public function testLogFatal() {
        $this->expectOutputString('FATAL: Fatal');
        $log = new Slim_Log(new MyApp(), new MyWriter());
        $result = $log->fatal('Fatal');
        $this->assertTrue($result);
    }

    public function testCall() {
        $env = array();
        $log = new Slim_Log(new MyApp(), new MyWriter());
        list($status, $header, $body) = $log->call($env);
        $this->assertEquals(200, $status);
    }
}
?>