<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.5
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

class SessionTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    public function testStartWhenNotAlreadyStarted()
    {
        $dataSource = new \ArrayObject();
        $session = \Mockery::mock('\Slim\Session[isStarted,initialize]');
        $session->setDataSource($dataSource);

        // Mocked method expectations
        $session->shouldReceive('isStarted')->once()->withNoArgs()->andReturn(false);
        $session->shouldReceive('initialize')->once()->withNoArgs()->andReturnNull();

        $session->start();
    }

    public function testStartWhenAlreadyStarted()
    {
        $dataSource = new \ArrayObject();
        $session = \Mockery::mock('\Slim\Session[isStarted,initialize]');
        $session->setDataSource($dataSource);

        // Mocked method expectations
        $session->shouldReceive('isStarted')->once()->withNoArgs()->andReturn(true);
        $session->shouldReceive('initialize')->never();

        $session->start();
    }

    public function testStartWithExistingSessionData()
    {
        $dataSource = new \ArrayObject([
            'slim.session' => [
                'foo' => 'bar'
            ]
        ]);
        $session = \Mockery::mock('\Slim\Session[isStarted,initialize]');
        $session->setDataSource($dataSource);

        // Mocked method expectations
        $session->shouldReceive('isStarted')->once()->withNoArgs()->andReturn(false);
        $session->shouldReceive('initialize')->once()->withNoArgs()->andReturnNull();
        $session->start();

        $this->assertEquals('bar', $session->get('foo'));
    }

    public function testSave()
    {
        $dataSource = new \ArrayObject();
        $session = \Mockery::mock('\Slim\Session[isStarted,initialize]');
        $session->setDataSource($dataSource);

        // Mocked method expectations
        $session->shouldReceive('isStarted')->once()->withNoArgs()->andReturn(false);
        $session->shouldReceive('initialize')->once()->withNoArgs()->andReturnNull();
        $session->start();

        // Set new data
        $session->set('abc', '123');
        $session->save();

        $this->assertArrayHasKey('slim.session', $dataSource);
        $this->assertEquals('123', $dataSource['slim.session']['abc']);
    }
}
