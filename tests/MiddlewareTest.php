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


class MiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $callback = function(){};
        $next = function(){};
        $mw = new \Slim\Middleware($callback, $next);
        $this->assertEquals($callback, $mw->getCallable());
        $this->assertEquals($next, $mw->getNext());
    }

    public function testInvokeExceptionIfBadReturnedValue()
    {
        $callback = function(){return new stdClass(); };
        $next = function(){};
        $mw = new \Slim\Middleware($callback, $next);
        $error_message = "A midlleware should return an instance of Psr\\Http\\Message\\ResponseInterface, stdClass given";
        $this->setExpectedException('UnexpectedValueException', $error_message);
        $req = $this->getMock('Psr\Http\Message\RequestInterface');
        $resp = $this->getMock('Psr\Http\Message\ResponseInterface');
        call_user_func_array($mw, [$req, $resp]);
    }
}
