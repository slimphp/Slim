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

class FlashTest extends PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        \Mockery::close();
    }

    /**
     * Test set flash message for current request
     */
    public function testSetFlashForCurrentRequest()
    {
        $dataSource = new \ArrayObject();

        $session = \Mockery::mock('\Slim\Session[isStarted,initialize]');
        $session->setDataSource($dataSource);
        $session->shouldReceive('isStarted')->once()->withNoArgs()->andReturn(false);
        $session->shouldReceive('initialize')->once()->withNoArgs()->andReturnNull();
        $session->start();

        $flash = new \Slim\Flash($session, 'slim.flash');
        $flash->now('info', 'foo');

        $this->assertEquals('foo', $flash['info']);
    }

    /**
     * Test loads flash from previous request
     */
    public function testLoadsFlashFromPreviousRequest()
    {
        $dataSource = new \ArrayObject(array(
            'slim.session' => array(
                'slim.flash' => array(
                    'info' => 'foo',
                ),
            ),
        ));

        $session = \Mockery::mock('\Slim\Session[isStarted,initialize]');
        $session->setDataSource($dataSource);
        $session->shouldReceive('isStarted')->once()->withNoArgs()->andReturn(false);
        $session->shouldReceive('initialize')->once()->withNoArgs()->andReturnNull();
        $session->start();

        $flash = new \Slim\Flash($session, 'slim.flash');

        $this->assertEquals('foo', $flash['info']);
    }

    /**
     * Test set flash message for next request
     */
    public function testSetFlashForNextRequest()
    {
        $dataSource = new \ArrayObject();

        $session = \Mockery::mock('\Slim\Session[isStarted,initialize]');
        $session->setDataSource($dataSource);
        $session->shouldReceive('isStarted')->once()->withNoArgs()->andReturn(false);
        $session->shouldReceive('initialize')->once()->withNoArgs()->andReturnNull();
        $session->start();

        $flash = new \Slim\Flash($session, 'slim.flash');
        $flash->next('info', 'foo');
        $flash->save();

        $session_flash = $session->get('slim.flash');

        $this->assertEquals('foo', $session_flash['info']);
    }

    /**
     * Test keep flash message for next request
     */
    public function testKeepFlashForNextRequest()
    {
        $dataSource = new \ArrayObject(array(
            'slim.session' => array(
                'slim.flash' => array(
                    'info' => 'foo',
                ),
            ),
        ));

        $session = \Mockery::mock('\Slim\Session[isStarted,initialize]');
        $session->setDataSource($dataSource);
        $session->shouldReceive('isStarted')->once()->withNoArgs()->andReturn(false);
        $session->shouldReceive('initialize')->once()->withNoArgs()->andReturnNull();
        $session->start();

        $flash = new \Slim\Flash($session, 'slim.flash');
        $flash->keep();
        $flash->save();

        $session->save();

        $session_flash = $session->get('slim.flash');

        $this->assertEquals('foo', $session_flash['info']);
    }

    /**
     * Test flash messages from previous request do not persist to next request
     */
    public function testFlashMessagesFromPreviousRequestDoNotPersist()
    {
        $dataSource = new \ArrayObject(array(
            'slim.session' => array(
                'slim.flash' => array(
                    'info' => 'foo',
                ),
            ),
        ));

        $session = \Mockery::mock('\Slim\Session[isStarted,initialize]');
        $session->setDataSource($dataSource);
        $session->shouldReceive('isStarted')->once()->withNoArgs()->andReturn(false);
        $session->shouldReceive('initialize')->once()->withNoArgs()->andReturnNull();
        $session->start();

        $flash = new \Slim\Flash($session, 'slim.flash');
        $flash->save();

        $session_flash = $session->get('slim.flash');

        $this->assertEmpty($session_flash);
    }

    /**
     * Test set Flash using array access
     */
    public function testFlashArrayAccess()
    {
        $dataSource = new \ArrayObject(array(
            'slim.session' => array(
                'slim.flash' => array(
                    'info' => 'foo',
                ),
            ),
        ));

        $session = \Mockery::mock('\Slim\Session[isStarted,initialize]');
        $session->setDataSource($dataSource);
        $session->shouldReceive('isStarted')->once()->withNoArgs()->andReturn(false);
        $session->shouldReceive('initialize')->once()->withNoArgs()->andReturnNull();
        $session->start();

        $flash = new \Slim\Flash($session, 'slim.flash');
        $flash['info'] = 'bar';

        $this->assertTrue(isset($flash['info']));
        $this->assertEquals('bar', $flash['info']);

        unset($flash['info']);

        $this->assertFalse(isset($flash['info']));
    }

    /**
     * Test iteration
     */
    public function testIteration()
    {
        $dataSource = new \ArrayObject(array(
            'slim.session' => array(
                'slim.flash' => array(
                    'info' => 'foo',
                    'error' => 'bar',
                ),
            ),
        ));

        $session = \Mockery::mock('\Slim\Session[isStarted,initialize]');
        $session->setDataSource($dataSource);
        $session->shouldReceive('isStarted')->once()->withNoArgs()->andReturn(false);
        $session->shouldReceive('initialize')->once()->withNoArgs()->andReturnNull();
        $session->start();

        $flash = new \Slim\Flash($session, 'slim.flash');

        $output = '';
        foreach ($flash as $key => $value) {
            $output .= $key . $value;
        }

        $this->assertEquals('infofooerrorbar', $output);
    }

    /**
     * Test countable
     */
    public function testCountable()
    {
        $dataSource = new \ArrayObject();

        $session = \Mockery::mock('\Slim\Session[isStarted,initialize]');
        $session->setDataSource($dataSource);
        $session->shouldReceive('isStarted')->once()->withNoArgs()->andReturn(false);
        $session->shouldReceive('initialize')->once()->withNoArgs()->andReturnNull();
        $session->start();

        $flash = new \Slim\Flash($session, 'slim.flash');
        $flash->now('info', 'foo');
        $flash->now('warning', 'bar');
        $flash->now('error', 'baz');

        $this->assertEquals(3, count($flash));
    }
}
