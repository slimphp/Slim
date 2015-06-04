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
use Slim\Http\Middleware;

class MiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function middlewareFactory()
    {
        $middleware = $this->getMockForAbstractClass('Slim\Http\Middleware');

        return $middleware;
    }

    public function requestFactory()
    {
        return $this->getMockBuilder('Slim\Http\Request')
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    public function responseFactory()
    {
        return $this->getMockBuilder('Slim\Http\Response')
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    /*******************************************************************************
     * __invoke()
     ******************************************************************************/

    public function testResponseInterfaceReturned()
    {
        $callable = function ($req, $res) {
            return $res;
        };

        $req = $this->requestFactory();
        $res = $this->responseFactory();

        $middleware = $this->middlewareFactory();

        $return = $middleware($req, $res, $callable);

        $this->assertContains('Psr\Http\Message\ResponseInterface', class_implements($return));
    }

    public function testResponsePassedThroughToEnd()
    {
        $callable = function ($req, $res) {
            $res->changed = true;
            return $res;
        };

        $req = $this->requestFactory();
        $res = $this->responseFactory();

        $middleware = $this->middlewareFactory();

        $return = $middleware($req, $res, $callable);

        $this->assertSame($res, $return);
    }

    /*******************************************************************************
     * before()
     ******************************************************************************/

    public function testArrayReturnedFromBefore()
    {
        $req = $this->requestFactory();
        $res = $this->responseFactory();

        $middleware = $this->middlewareFactory();

        $return = $middleware->before($req, $res);

        $this->assertInternalType('array', $return);
    }

    public function testTwoValuesReturnedFromBefore()
    {
        $req = $this->requestFactory();
        $res = $this->responseFactory();

        $middleware = $this->middlewareFactory();

        $return = $middleware->before($req, $res);

        $this->assertCount(2, $return);
    }

    public function testFirstValueIsRequestInterfaceFromBefore()
    {
        $req = $this->requestFactory();
        $res = $this->responseFactory();

        $middleware = $this->middlewareFactory();

        $return = $middleware->before($req, $res);

        $this->assertContains('Psr\Http\Message\RequestInterface', class_implements($return[0]));
        $this->assertSame($req, $return[0]);
    }

    public function testSecondValueIsResponseInterfaceFromBefore()
    {
        $req = $this->requestFactory();
        $res = $this->responseFactory();

        $middleware = $this->middlewareFactory();

        $return = $middleware->before($req, $res);

        $this->assertContains('Psr\Http\Message\ResponseInterface', class_implements($return[1]));
        $this->assertSame($res, $return[1]);
    }

    /*******************************************************************************
     * after()
     ******************************************************************************/

    public function testReturnValueIsResponseInterfaceFromAfter()
    {
        $req = $this->requestFactory();
        $res = $this->responseFactory();

        $middleware = $this->middlewareFactory();

        $return = $middleware->after($req, $res);

        $this->assertContains('Psr\Http\Message\ResponseInterface', class_implements($return));
        $this->assertSame($res, $return);
    }

}
