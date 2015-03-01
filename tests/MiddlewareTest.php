<?php
use Slim\Collection;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Uri;

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

class MyMiddleware implements \Slim\Interfaces\MiddlewareInterface
{
    public function __invoke(
      \Psr\Http\Message\RequestInterface $request,
      \Psr\Http\Message\ResponseInterface $response,
      callable $next = null
    ) {
        $response->write("Hello\n");
        return $response;
    }
}

class MiddlewareTest extends PHPUnit_Framework_TestCase
{
    public function requestFactory()
    {
        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = new Collection([
          'user' => 'john',
          'id' => '123'
        ]);
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $body);

        return $request;
    }

    public function testInvokes()
    {
        $mw1 = function ($request, $response, $next) {
            $response->write("World\n");
            $response = $next($request, $response, $next);
            return $response;
        };
        $mw2 = new MyMiddleware();
        $middleware = new \Slim\Middleware($mw1, $mw2);

        $response = new \Slim\Http\Response();
        $middleware->__invoke($this->requestFactory(), $response);
        $body = (string) $response->getBody();
        $this->assertContains('Hello', $body);
        $this->assertContains('World', $body);
    }

    public function testSetNextMiddleware()
    {
        $mw1 = function ($request, $response, $next) {
            $response->write("World\n");
            $response = $next($request, $response, $next);
            return $response;
        };
        $mw2 = new MyMiddleware();
        $middleware = new \Slim\Middleware($mw1, $mw2);

        $this->assertAttributeSame($mw2, 'next', $middleware);
    }
}
