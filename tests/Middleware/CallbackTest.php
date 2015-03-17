<?php
/**
 * Slim - a micro PHP 5 framework.
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 *
 * @link        http://www.slimframework.com
 *
 * @license     http://www.slimframework.com/license
 *
 * @version     2.6.1
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
class CallbackTest extends PHPUnit_Framework_TestCase
{
    /**
     * Ensures constructor throws InvalidArgumentException if not callable is provided.
     */
    public function testConstructThrowsOnInvalidCallback()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Invalid callback provided; not callable'
        );
        new \Slim\Middleware\Callback('I\'m not callable!');
    }

    /**
     * Ensures callback is set in object.
     */
    public function testCallbackIsSet()
    {
        $callback = function () {};
        $mw       = new \Slim\Middleware\Callback($callback);
        $this->assertAttributeSame($callback, 'callback', $mw);
    }

    /**
     * Ensures callback get Method.
     */
    public function testGetCallback()
    {
        $callback = function () {};
        $mw       = new \Slim\Middleware\Callback($callback);
        $this->assertSame($callback, $mw->getCallback());
    }

    /**
     * Ensures call method provides callback with proper arguments.
     */
    public function testCallMethodPassArgsToCallback()
    {
        $called = false;
        $that   = $this;
        $mw2    = $this->getMockMiddleware();
        $app    = $this->getMockSlimApp();
        $mw1    = new \Slim\Middleware\Callback(
            function ($appArg, $nextArg) use ($that, $app, $mw2, &$called) {
                $that->assertSame($app, $appArg);
                $that->assertSame($mw2, $nextArg);
                $called = true;
            }
        );

        $mw1->setApplication($app);
        $mw2->setApplication($app);
        $mw1->setNextMiddleware($mw2);
        $mw1->call();
        $this->assertTrue($called);
    }

    protected function getMockSlimApp()
    {
        return $this->getMockBuilder('Slim\\Slim')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getMockMiddleware()
    {
        return $this->getMockForAbstractClass('Slim\\Middleware');
    }
}
