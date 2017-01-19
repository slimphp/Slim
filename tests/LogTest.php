<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011-2017 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.6.4
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

class MyWriter
{
    public function write( $object, $level )
    {
        echo (string) $object;

        return true;
    }
}

class LogTest extends PHPUnit_Framework_TestCase
{
    public function testEnabled()
    {
        $log = new \Slim\Log(new MyWriter());
        $this->assertTrue($log->isEnabled()); //<-- Default case
        $log->setEnabled(true);
        $this->assertTrue($log->isEnabled());
        $log->setEnabled(false);
        $this->assertFalse($log->isEnabled());
    }

    public function testGetLevel()
    {
        $log = new \Slim\Log(new MyWriter());
        $this->assertEquals(\Slim\Log::DEBUG, $log->getLevel());
    }

    public function testSetLevel()
    {
        $log = new \Slim\Log(new MyWriter());
        $log->setLevel(\Slim\Log::WARN);
        $this->assertEquals(\Slim\Log::WARN, $log->getLevel());
    }

    public function testSetInvalidLevel()
    {
        $this->setExpectedException('InvalidArgumentException');
        $log = new \Slim\Log(new MyWriter());
        $log->setLevel(\Slim\Log::DEBUG + 1);
    }

    public function testLogDebug()
    {
        $this->expectOutputString('Debug');
        $log = new \Slim\Log(new MyWriter());
        $result = $log->debug('Debug');
        $this->assertTrue($result);
    }

    public function testLogDebugExcludedByLevel()
    {
        $log = new \Slim\Log(new MyWriter());
        $log->setLevel(\Slim\Log::INFO);
        $this->assertFalse($log->debug('Debug'));
    }

    public function testLogInfo()
    {
        $this->expectOutputString('Info');
        $log = new \Slim\Log(new MyWriter());
        $result = $log->info('Info');
        $this->assertTrue($result);
    }

    public function testLogInfoExcludedByLevel()
    {
        $log = new \Slim\Log(new MyWriter());
        $log->setLevel(\Slim\Log::NOTICE);
        $this->assertFalse($log->info('Info'));
    }

    public function testLogNotice()
    {
        $this->expectOutputString('Notice');
        $log = new \Slim\Log(new MyWriter());
        $result = $log->notice('Notice');
        $this->assertTrue($result);
    }

    public function testLogNoticeExcludedByLevel()
    {
        $log = new \Slim\Log(new MyWriter());
        $log->setLevel(\Slim\Log::WARN);
        $this->assertFalse($log->info('Info'));
    }

    public function testLogWarn()
    {
        $this->expectOutputString('Warn');
        $log = new \Slim\Log(new MyWriter());
        $result = $log->warning('Warn');
        $this->assertTrue($result);
    }

    public function testLogWarnExcludedByLevel()
    {
        $log = new \Slim\Log(new MyWriter());
        $log->setLevel(\Slim\Log::ERROR);
        $this->assertFalse($log->warning('Warn'));
    }

    public function testLogError()
    {
        $this->expectOutputString('Error');
        $log = new \Slim\Log(new MyWriter());
        $result = $log->error('Error');
        $this->assertTrue($result);
    }

    public function testLogErrorExcludedByLevel()
    {
        $log = new \Slim\Log(new MyWriter());
        $log->setLevel(\Slim\Log::CRITICAL);
        $this->assertFalse($log->error('Error'));
    }

    public function testLogCritical()
    {
        $this->expectOutputString('Critical');
        $log = new \Slim\Log(new MyWriter());
        $result = $log->critical('Critical');
        $this->assertTrue($result);
    }

    public function testLogCriticalExcludedByLevel()
    {
        $log = new \Slim\Log(new MyWriter());
        $log->setLevel(\Slim\Log::ALERT);
        $this->assertFalse($log->critical('Critical'));
    }

    public function testLogAlert()
    {
        $this->expectOutputString('Alert');
        $log = new \Slim\Log(new MyWriter());
        $result = $log->alert('Alert');
        $this->assertTrue($result);
    }

    public function testLogAlertExcludedByLevel()
    {
        $log = new \Slim\Log(new MyWriter());
        $log->setLevel(\Slim\Log::EMERGENCY);
        $this->assertFalse($log->alert('Alert'));
    }

    public function testLogEmergency()
    {
        $this->expectOutputString('Emergency');
        $log = new \Slim\Log(new MyWriter());
        $result = $log->emergency('Emergency');
        $this->assertTrue($result);
    }

    public function testInterpolateMessage()
    {
        $this->expectOutputString('Hello Slim !');
        $log = new \Slim\Log(new MyWriter());
        $result = $log->debug(
            'Hello {framework} !',
            array('framework' => "Slim")
        );
        $this->assertTrue($result);
    }

    public function testGetAndSetWriter()
    {
        $writer1 = new MyWriter();
        $writer2 = new MyWriter();
        $log = new \Slim\Log($writer1);
        $this->assertSame($writer1, $log->getWriter());
        $log->setWriter($writer2);
        $this->assertSame($writer2, $log->getWriter());
    }
}
