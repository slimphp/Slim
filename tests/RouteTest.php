<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use PHPUnit\Framework\TestCase;
use Pimple\Container as Pimple;
use Pimple\Psr11\Container as Psr11Container;
use Slim\CallableResolver;
use Slim\DeferredCallable;
use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Route;
use Slim\Tests\Mocks\CallableTest;
use Slim\Tests\Mocks\InvocationStrategyTest;
use Slim\Tests\Mocks\MiddlewareStub;

class RouteTest extends TestCase
{
    public function routeFactory()
    {
        $methods = ['GET', 'POST'];
        $pattern = '/hello/{name}';
        $callable = function ($req, $res, $args) {
            return $res;
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

    public function testGetMethodsReturnsArrayWhenContructedWithString()
    {
        $route = new Route('GET', '/hello', function ($req, $res, $args) {
            // Do something
        });

        $this->assertEquals(['GET'], $route->getMethods());
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
        $bottom = null;
        $mw = function ($req, $res, $next) use (&$bottom) {
            $bottom = $next;
            return $res;
        };
        $route->add($mw);
        $route->finalize();

        $route->callMiddlewareStack(
            $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->disableOriginalConstructor()->getMock()
        );

        $this->assertEquals($route, $bottom);
    }

    public function testAddMiddleware()
    {
        $route = $this->routeFactory();
        $called = 0;

        $mw = function ($req, $res, $next) use (&$called) {
            $called++;
            return $res;
        };

        $route->add($mw);
        $route->finalize();

        $route->callMiddlewareStack(
            $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->disableOriginalConstructor()->getMock()
        );

        $this->assertSame($called, 1);
    }

    public function testRefinalizing()
    {
        $route = $this->routeFactory();
        $called = 0;

        $mw = function ($req, $res, $next) use (&$called) {
            $called++;
            return $res;
        };

        $route->add($mw);

        $route->finalize();
        $route->finalize();

        $route->callMiddlewareStack(
            $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->disableOriginalConstructor()->getMock()
        );

        $this->assertSame($called, 1);
    }


    public function testIdentifier()
    {
        $route = $this->routeFactory();
        $this->assertEquals('route0', $route->getIdentifier());
    }

    public function testSetName()
    {
        $route = $this->routeFactory();
        $this->assertEquals($route, $route->setName('foo'));
        $this->assertEquals('foo', $route->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetInvalidName()
    {
        $route = $this->routeFactory();
        $route->setName(false);
    }

    public function testAddMiddlewareAsStringResolvesWithoutContainer()
    {
        $route = $this->routeFactory();

        $resolver = new CallableResolver();
        $route->setCallableResolver($resolver);
        $route->add('MiddlewareStub:run');

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [
            'user' => 'john',
            'id' => '123',
        ];
        $serverParams = $env;
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        $response = new Response;
        $result = $route->callMiddlewareStack($request, $response);

        $this->assertInstanceOf('Slim\Http\Response', $result);
    }

    public function testAddMiddlewareAsStringResolvesWithContainer()
    {
        $route = $this->routeFactory();

        $pimple = new Pimple();
        $pimple['MiddlewareStub'] = new MiddlewareStub();
        $resolver = new CallableResolver(new Psr11Container($pimple));
        $route->setCallableResolver($resolver);
        $route->add('MiddlewareStub:run');

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [
            'user' => 'john',
            'id' => '123',
        ];
        $serverParams = $env;
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        $response = new Response;
        $result = $route->callMiddlewareStack($request, $response);

        $this->assertInstanceOf('Slim\Http\Response', $result);
    }

    public function testControllerMethodAsStringResolvesWithoutContainer()
    {
        $resolver = new CallableResolver();
        $deferred = new DeferredCallable('\Slim\Tests\Mocks\CallableTest:toCall', $resolver);

        $route = new Route(['GET'], '/', $deferred);
        $route->setCallableResolver($resolver);

        $uri = Uri::createFromString('https://example.com:80');
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, new Headers(), [], Environment::mock(), $body);

        CallableTest::$CalledCount = 0;

        $result = $route->callMiddlewareStack($request, new Response);

        $this->assertInstanceOf('Slim\Http\Response', $result);
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testControllerMethodAsStringResolvesWithContainer()
    {
        $pimple = new Pimple();
        $pimple['CallableTest'] = new CallableTest();
        $resolver = new CallableResolver(new Psr11Container($pimple));

        $deferred = new DeferredCallable('CallableTest:toCall', $resolver);

        $route = new Route(['GET'], '/', $deferred);
        $route->setCallableResolver($resolver);

        $uri = Uri::createFromString('https://example.com:80');
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, new Headers(), [], Environment::mock(), $body);

        CallableTest::$CalledCount = 0;

        $result = $route->callMiddlewareStack($request, new Response);

        $this->assertInstanceOf('Slim\Http\Response', $result);
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    /**
     * Ensure that the response returned by a route callable is the response
     * object that is returned by __invoke().
     */
    public function testInvokeWhenReturningAResponse()
    {
        $callable = function ($req, $res, $args) {
            return $res->write('foo');
        };
        $route = new Route(['GET'], '/', $callable);

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env;
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('foo', (string)$response->getBody());
    }

    /**
     * Ensure that anything echo'd in a route callable is, by default, NOT
     * added to the response object body.
     */
    public function testInvokeWhenEchoingOutput()
    {
        $callable = function ($req, $res, $args) {
            echo "foo";
            return $res->withStatus(201);
        };
        $route = new Route(['GET'], '/', $callable);

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env;
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        // We capture output buffer here only to clean test CLI output
        ob_start();
        $response = $route->__invoke($request, $response);
        ob_end_clean();

        $this->assertEquals('', (string)$response->getBody()); // Output buffer ignored without optional middleware
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Ensure that if a string is returned by a route callable, then it is
     * added to the response object that is returned by __invoke().
     */
    public function testInvokeWhenReturningAString()
    {
        $callable = function ($req, $res, $args) {
            $res->write('foo');
            return $res;
        };
        $route = new Route(['GET'], '/', $callable);

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env;
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('foo', (string)$response->getBody());
    }

    /**
     * @expectedException \Exception
     */
    public function testInvokeWithException()
    {
        $callable = function ($req, $res, $args) {
            throw new \Exception();
        };
        $route = new Route(['GET'], '/', $callable);


        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env;
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);
    }

    /**
     * Ensure that `foundHandler` is called on actual callable
     */
    public function testInvokeDeferredCallableWithNoContainer()
    {
        $resolver = new CallableResolver();

        $route = new Route(['GET'], '/', '\Slim\Tests\Mocks\CallableTest:toCall');
        $route->setCallableResolver($resolver);
        $route->setInvocationStrategy(new InvocationStrategyTest());

        $uri = Uri::createFromString('https://example.com:80');
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, new Headers(), [], Environment::mock(), $body);

        $result = $route->callMiddlewareStack($request, new Response);

        $this->assertInstanceOf('Slim\Http\Response', $result);
        $this->assertEquals([new CallableTest(), 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }

    /**
     * Ensure that `foundHandler` is called on actual callable
     */
    public function testInvokeDeferredCallableWithContainer()
    {
        $pimple = new Pimple();
        $pimple['CallableTest'] = new CallableTest;
        $resolver = new CallableResolver(new Psr11Container($pimple));

        $route = new Route(['GET'], '/', '\Slim\Tests\Mocks\CallableTest:toCall');
        $route->setCallableResolver($resolver);
        $route->setInvocationStrategy(new InvocationStrategyTest());

        $uri = Uri::createFromString('https://example.com:80');
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, new Headers(), [], Environment::mock(), $body);

        $result = $route->callMiddlewareStack($request, new Response);

        $this->assertInstanceOf('Slim\Http\Response', $result);
        $this->assertEquals([new CallableTest(), 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }

    /**
     * Ensure that the pattern can be dynamically changed
     */
    public function testPatternCanBeChanged()
    {
        $route = $this->routeFactory();
        $route->setPattern('/hola/{nombre}');
        $this->assertEquals('/hola/{nombre}', $route->getPattern());
    }

    /**
     * Ensure that the callable can be changed
     */
    public function testChangingCallableWithNoContainer()
    {
        $resolver = new CallableResolver();

        $route = new Route(['GET'], '/', 'NonExistent:toCall'); //Note that this doesn't actually exist
        $route->setCallableResolver($resolver);
        $route->setInvocationStrategy(new InvocationStrategyTest());

        $route->setCallable('\Slim\Tests\Mocks\CallableTest:toCall'); //Then we fix it here.

        $uri = Uri::createFromString('https://example.com:80');
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, new Headers(), [], Environment::mock(), $body);

        $result = $route->callMiddlewareStack($request, new Response);

        $this->assertInstanceOf('Slim\Http\Response', $result);
        $this->assertEquals([new CallableTest(), 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }

    /**
     * Ensure that the callable can be changed
     */
    public function testChangingCallableWithContainer()
    {
        $pimple = new Pimple();
        $pimple['CallableTest2'] = new CallableTest;
        $resolver = new CallableResolver(new Psr11Container($pimple));

        $route = new Route(['GET'], '/', 'NonExistent:toCall'); //Note that this doesn't actually exist
        $route->setCallableResolver($resolver);
        $route->setInvocationStrategy(new InvocationStrategyTest());

        $route->setCallable('CallableTest2:toCall'); //Then we fix it here.

        $uri = Uri::createFromString('https://example.com:80');
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, new Headers(), [], Environment::mock(), $body);

        $result = $route->callMiddlewareStack($request, new Response);

        $this->assertInstanceOf('Slim\Http\Response', $result);
        $this->assertEquals([$pimple['CallableTest2'], 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }
}
