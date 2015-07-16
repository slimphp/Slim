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

use Slim\Route;
use Slim\Container;

class MiddlewareStub
{
    public function run($request, $response, $next) {
        return $response; //$next($request, $response);
    }
}

class RouteTest extends PHPUnit_Framework_TestCase
{
    public function routeFactory()
    {
        $methods = ['GET', 'POST'];
        $pattern = '/hello/{name}';
        $callable = function ($req, $res, $args) {
            // Do something
        };

        return new Route($methods, $pattern, $callable);
    }

    public function testConstructor()
    {
        $methods = ['GET', 'POST'];
        $pattern = '/hello/{name}';
        $callable = function ($req, $res, $args) {
            // Do something
        };
        $route = new Route($methods, $pattern, $callable);

        $this->assertAttributeEquals($methods, 'methods', $route);
        $this->assertAttributeEquals($pattern, 'pattern', $route);
        $this->assertAttributeEquals($callable, 'callable', $route);
    }

    public function testGetMethods()
    {
        $this->assertEquals(['GET', 'POST'], $this->routeFactory()->getMethods());
    }

    public function testGetPattern()
    {
        $this->assertEquals('/hello/{name}', $this->routeFactory()->getPattern());
    }

    public function testGetCallable()
    {
        $callable = $this->routeFactory()->getCallable();

        $this->assertTrue(is_callable($callable));
    }

    public function testArgumentSetting()
    {
        $route = $this->routeFactory();
        $route->setArguments(['foo' => 'FOO', 'bar' => 'BAR']);
        $this->assertSame($route->getArguments(), ['foo' => 'FOO', 'bar' => 'BAR']);
        $route->setArgument('bar', 'bar');
        $this->assertSame($route->getArguments(), ['foo' => 'FOO', 'bar' => 'bar']);
        $route->setArgument('baz', 'BAZ');
        $this->assertSame($route->getArguments(), ['foo' => 'FOO', 'bar' => 'bar', 'baz' => 'BAZ']);
        
        $route->setArguments(['a' => 'b']);
        $this->assertSame($route->getArguments(), ['a' => 'b']);
        $this->assertSame($route->getArgument('a', 'default'), 'b');
        $this->assertSame($route->getArgument('b', 'default'), 'default');
    }


    public function testBottomMiddlewareIsRoute()
    {
        $route = $this->routeFactory();
        $mw = function ($req, $res, $next) {
            return $res;
        };
        $route->add($mw);
        $route->finalize();

        $prop = new \ReflectionProperty($route, 'stack');
        $prop->setAccessible(true);

        $this->assertEquals($route, $prop->getValue($route)->bottom());
    }

    public function testAddMiddleware()
    {
        $route = $this->routeFactory();
        $mw = function ($req, $res, $next) {
            return $res;
        };
        $route->add($mw);
        $route->finalize();

        $prop = new \ReflectionProperty($route, 'stack');
        $prop->setAccessible(true);

        $this->assertCount(2, $prop->getValue($route));
    }

    public function testSetName()
    {
        $route = $this->routeFactory();
        $this->assertEquals($route, $route->setName('foo'));
        $this->assertEquals('foo', $route->getName());
    }

    public function testSetInvalidName()
    {
        $route = $this->routeFactory();

        $this->setExpectedException('InvalidArgumentException');
        
        $route->setName(false);
    }

    public function testSetOutputBuffering()
    {
        $route = $this->routeFactory();

        $route->setOutputBuffering(false);
        $this->assertFalse($route->getOutputBuffering());

        $route->setOutputBuffering('append');
        $this->assertSame('append', $route->getOutputBuffering());

        $route->setOutputBuffering('prepend');
        $this->assertSame('prepend', $route->getOutputBuffering());
    }

    public function testSetInvalidOutputBuffering()
    {
        $route = $this->routeFactory();

        $this->setExpectedException('InvalidArgumentException');
        
        $route->setOutputBuffering('invalid');
    }

    public function testAddMiddlewareAsString()
    {
        $route = $this->routeFactory();

        $container = new Container();
        $route->setContainer($container);
        $route->setCallableResolver(new \Slim\CallableResolver($container));
        $route->add('MiddlewareStub:run');

        $env = \Slim\Http\Environment::mock();
        $uri = \Slim\Http\Uri::createFromString('https://example.com:80');
        $headers = new \Slim\Http\Headers();
        $cookies = [
            'user' => 'john',
            'id' => '123',
        ];
        $serverParams = $env->all();
        $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
        $request = new \Slim\Http\Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        $response = new \Slim\Http\Response;
        $result = $route->callMiddlewareStack($request, $response);

        $this->assertInstanceOf('Slim\Http\Response', $result);
    }

    // TODO: Test adding controller callables with "Foo:bar" syntax

    /**
     * Ensure that the response returned by a route callable is the response
     * object that is returned by __invoke().
     */
    public function testInvokeWhenReturningAResponse()
    {
        $callable = function ($req, $res, $args) {
            return $res->write('foo');
        };
        $c = new Container();
        $route = new Route(['GET'], '/', $callable);

        $env = \Slim\Http\Environment::mock();
        $uri = \Slim\Http\Uri::createFromString('https://example.com:80');
        $headers = new \Slim\Http\Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
        $request = new \Slim\Http\Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new \Slim\Http\Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('foo', (string)$response->getBody());
    }

    /**
     * Ensure that anything echo'd in a route callable is added to the response
     * object that is returned by __invoke().
     */
    public function testInvokeWhenEchoingOutput()
    {
        $callable = function ($req, $res, $args) {
            echo "foo";
            return $res->withStatus(201);
        };
        $route = new Route(['GET'], '/', $callable);

        $env = \Slim\Http\Environment::mock();
        $uri = \Slim\Http\Uri::createFromString('https://example.com:80');
        $headers = new \Slim\Http\Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
        $request = new \Slim\Http\Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new \Slim\Http\Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('foo', (string)$response->getBody());
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Ensure that if a string is returned by a route callable, then it is
     * added to the response object that is returned by __invoke().
     */
    public function testInvokeWhenReturningAString()
    {
        $callable = function ($req, $res, $args) {
            return "foo";
        };
        $route = new Route(['GET'], '/', $callable);

        $env = \Slim\Http\Environment::mock();
        $uri = \Slim\Http\Uri::createFromString('https://example.com:80');
        $headers = new \Slim\Http\Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
        $request = new \Slim\Http\Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new \Slim\Http\Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('foo', (string)$response->getBody());
    }

    /**
     * Ensure that if `outputBuffering` property is set to `prepend` correct response
     * body is returned by __invoke().
     */
    public function testInvokeWhenPrependingOutputBuffer()
    {
        $callable = function ($req, $res, $args) {
            echo 'foo';
            return $res->write('bar');
        };
        $route = new Route(['GET'], '/', $callable);
        $route->setOutputBuffering('prepend');

        $env = \Slim\Http\Environment::mock();
        $uri = \Slim\Http\Uri::createFromString('https://example.com:80');
        $headers = new \Slim\Http\Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
        $request = new \Slim\Http\Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new \Slim\Http\Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('foobar', (string)$response->getBody());
    }

    /**
     * Ensure that if `outputBuffering` property is set to `false` correct response
     * body is returned by __invoke().
     */
    public function testInvokeWhenDisablingOutputBuffer()
    {
        ob_start();
        $callable = function ($req, $res, $args) {
            echo 'foo';
            return $res->write('bar');
        };
        $route = new Route(['GET'], '/', $callable);
        $route->setOutputBuffering(false);

        $env = \Slim\Http\Environment::mock();
        $uri = \Slim\Http\Uri::createFromString('https://example.com:80');
        $headers = new \Slim\Http\Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
        $request = new \Slim\Http\Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new \Slim\Http\Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('bar', (string)$response->getBody());

        $output = ob_get_clean();
        $this->assertEquals('foo', $output);
    }
}
