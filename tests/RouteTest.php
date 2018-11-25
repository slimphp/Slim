<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Pimple\Container as Pimple;
use Pimple\Psr11\Container as Psr11Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\CallableResolver;
use Slim\DeferredCallable;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Route;
use Slim\Tests\Mocks\CallableTest;
use Slim\Tests\Mocks\InvocationStrategyTest;
use Slim\Tests\Mocks\MiddlewareStub;
use Slim\Tests\Mocks\RequestHandlerTest;
use Exception;

class RouteTest extends TestCase
{
    public function routeFactory()
    {
        $methods = ['GET', 'POST'];
        $pattern = '/hello/{name}';
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            return $response;
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
        $mw = function (ServerRequestInterface $request, ResponseInterface $response, $next) use (&$bottom) {
            $bottom = $next;
            return $response;
        };
        $route->add($mw);
        $route->finalize();

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();
        $route->callMiddlewareStack($request, $response);

        $this->assertEquals($route, $bottom);
    }

    public function testAddMiddleware()
    {
        $route = $this->routeFactory();
        $called = 0;

        $mw = function (ServerRequestInterface $request, ResponseInterface $response, $next) use (&$called) {
            $called++;
            return $response;
        };

        $route->add($mw);
        $route->finalize();

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();
        $route->callMiddlewareStack($request, $response);

        $this->assertSame($called, 1);
    }

    public function testRefinalizing()
    {
        $route = $this->routeFactory();
        $called = 0;

        $mw = function (ServerRequestInterface $request, ResponseInterface $response, $next) use (&$called) {
            $called++;
            return $response;
        };

        $route->add($mw);

        $route->finalize();
        $route->finalize();

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();
        $route->callMiddlewareStack($request, $response);

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

    public function testAddMiddlewareAsStringResolvesWithoutContainer()
    {
        $route = $this->routeFactory();

        $resolver = new CallableResolver();
        $route->setCallableResolver($resolver);
        $route->add('MiddlewareStub:run');

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();
        $result = $route->callMiddlewareStack($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testAddMiddlewareAsStringResolvesWithContainer()
    {
        $route = $this->routeFactory();

        $pimple = new Pimple();
        $pimple['MiddlewareStub'] = new MiddlewareStub();
        $resolver = new CallableResolver(new Psr11Container($pimple));
        $route->setCallableResolver($resolver);
        $route->add('MiddlewareStub:run');

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();
        $result = $route->callMiddlewareStack($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testControllerMethodAsStringResolvesWithoutContainer()
    {
        $resolver = new CallableResolver();
        $deferred = new DeferredCallable('\Slim\Tests\Mocks\CallableTest:toCall', $resolver);

        $route = new Route(['GET'], '/', $deferred);
        $route->setCallableResolver($resolver);

        CallableTest::$CalledCount = 0;

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();
        $result = $route->callMiddlewareStack($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
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

        CallableTest::$CalledCount = 0;

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();
        $result = $route->callMiddlewareStack($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    /**
     * Ensure that the response returned by a route callable is the response
     * object that is returned by __invoke().
     */
    public function testInvokeWhenReturningAResponse()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write('foo');
            return $response;
        };
        $route = new Route(['GET'], '/', $callable);

        CallableTest::$CalledCount = 0;

        $request = $this->createServerRequest('/');
        $response = $route->__invoke($request, $this->createResponse());

        $this->assertEquals('foo', (string) $response->getBody());
    }

    /**
     * Ensure that anything echo'd in a route callable is, by default, NOT
     * added to the response object body.
     */
    public function testInvokeWhenEchoingOutput()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            echo "foo";
            return $response->withStatus(201);
        };
        $route = new Route(['GET'], '/', $callable);

        $request = $this->createServerRequest('/');

        // We capture output buffer here only to clean test CLI output
        ob_start();
        $response = $route->__invoke($request, $this->createResponse());
        ob_end_clean();

        // Output buffer is ignored without optional middleware
        $this->assertEquals('', (string) $response->getBody());
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Ensure that if a string is returned by a route callable, then it is
     * added to the response object that is returned by __invoke().
     */
    public function testInvokeWhenReturningAString()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write('foo');
            return $response;
        };
        $route = new Route(['GET'], '/', $callable);

        $request = $this->createServerRequest('/');
        $response = $route->__invoke($request, $this->createResponse());

        $this->assertEquals('foo', (string) $response->getBody());
    }

    /**
     * @expectedException \Exception
     */
    public function testInvokeWithException()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            throw new Exception();
        };
        $route = new Route(['GET'], '/', $callable);

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();

        $route->__invoke($request, $response);
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

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();
        $result = $route->callMiddlewareStack($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
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

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();
        $result = $route->callMiddlewareStack($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals([new CallableTest(), 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }

    public function testInvokeUsesRequestHandlerStrategyForRequestHandlers()
    {
        $pimple = new Pimple();
        $pimple[RequestHandlerTest::class] = new RequestHandlerTest();
        $resolver = new CallableResolver(new Psr11Container($pimple));

        $route = new Route(['GET'], '/', RequestHandlerTest::class);
        $route->setCallableResolver($resolver);

        $request = $this->createServerRequest('/', 'GET');
        $response = $this->createResponse();
        $route->callMiddlewareStack($request, $response);

        /** @var InvocationStrategyInterface $strategy */
        $strategy = $pimple[RequestHandlerTest::class]::$strategy;
        $this->assertEquals('Slim\Handlers\Strategies\RequestHandler', $strategy);
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

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();
        $result = $route->callMiddlewareStack($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
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

        $request = $this->createServerRequest('/');
        $response = $this->createResponse();
        $result = $route->callMiddlewareStack($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals([$pimple['CallableTest2'], 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }
}
