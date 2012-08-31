<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     1.6.7
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

class Foo_Middleware extends \Slim\Middleware
{
    public function call()
    {
        echo "Before";
        $this->next->call();
        echo "After";
    }
}

class MiddlewareContainerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Set Middleware using the add method
     */
    public function testSetMiddlewareUsingAddMethod()
    {
        $app = new \Slim\Slim();
        $mw = new Foo_Middleware();
        $app['middleware']->add($mw);
        $this->assertSame($app, $mw->getApplication());
    }

    /**
     * Set Middleware using the array access method
     */
    public function testSetMiddlewareUsingArrayMethod()
    {
        $app = new \Slim\Slim();
        $mw = new Foo_Middleware();
        $app['middleware'][] = $mw;
        $this->assertSame($app, $mw->getApplication());
    }

    /**
     * Set not valid Middleware
     */
    public function testSetNotValidMiddleware()
    {
        $this->setExpectedException('InvalidArgumentException');
        $app = new \Slim\Slim();
        $mw = 'foo';
        $app['middleware'][] = $mw;
        $this->assertSame($app, $mw->getApplication());
    }
}
