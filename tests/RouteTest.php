<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Closure;
use Exception;
use Pimple\Container as Pimple;
use Pimple\Psr11\Container as Psr11Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Slim\CallableResolver;
use Slim\DeferredCallable;
use Slim\Handlers\Strategies\RequestHandler;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Middleware\ClosureMiddleware;
use Slim\Route;
use Slim\RouteGroup;
use Slim\Tests\Mocks\CallableTest;
use Slim\Tests\Mocks\InvocationStrategyTest;
use Slim\Tests\Mocks\MockMiddlewareWithoutConstructor;
use Slim\Tests\Mocks\MockMiddlewareWithoutInterface;
use Slim\Tests\Mocks\RequestHandlerTest;

class RouteTest extends TestCase
{
    /**
     * @param string|array $methods
     * @param string $pattern
     * @param Closure|string|null $callable
     * @return Route
     */
    public function createRoute($methods = 'GET', string $pattern = '/', $callable = null): Route
    {
        $callable = $callable ?? function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            return $response;
        };

        $responseFactory = $this->getResponseFactory();
        $callableResolver = new CallableResolver();

        $methods = is_string($methods) ? [$methods] : $methods;
        return new Route($methods, $pattern, $callable, $responseFactory, $callableResolver);
    }

    public function testConstructor()
    {
        $methods = ['GET', 'POST'];
        $pattern = '/hello/{name}';
        $callable = function ($request, $response, $args) {
            return $response;
        };
        $route = $this->createRoute($methods, $pattern, $callable);

        $this->assertAttributeEquals($methods, 'methods', $route);
        $this->assertAttributeEquals($pattern, 'pattern', $route);
        $this->assertAttributeEquals($callable, 'callable', $route);
    }

    public function testGetMethodsReturnsArrayWhenContructedWithString()
    {
        $route = $this->createRoute();

        $this->assertEquals(['GET'], $route->getMethods());
    }

    public function testGetMethods()
    {
        $methods = ['GET', 'POST'];
        $route = $this->createRoute($methods);

        $this->assertEquals($methods, $route->getMethods());
    }

    public function testGetPattern()
    {
        $route = $this->createRoute();

        $this->assertEquals('/', $route->getPattern());
    }

    public function testGetCallable()
    {
        $route = $this->createRoute();

        $this->assertTrue(is_callable($route->getCallable()));
    }

    public function testGetCallableResolver()
    {
        $callable = $callable ?? function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            return $response;
        };

        $responseFactory = $this->getResponseFactory();
        $callableResolver = new CallableResolver();

        $route = new Route(['GET'], '/', $callable, $responseFactory, $callableResolver);

        $this->assertEquals($callableResolver, $route->getCallableResolver());
    }

    public function testGetInvocationStrategy()
    {
        $callable = $callable ?? function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            return $response;
        };

        $responseFactory = $this->getResponseFactory();
        $callableResolver = new CallableResolver();
        $invocationStrategy = new RequestResponse();

        $route = new Route(['GET'], '/', $callable, $responseFactory, $callableResolver, $invocationStrategy);

        $this->assertEquals($invocationStrategy, $route->getInvocationStrategy());
    }

    public function testGetGroups()
    {
        $callable = $callable ?? function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            return $response;
        };

        $responseFactory = $this->getResponseFactory();
        $callableResolver = new CallableResolver();
        $invocationStrategy = new RequestResponse();

        $routeGroup = new RouteGroup('/group', $callable, $responseFactory, $callableResolver);
        $groups = [$routeGroup];

        $route = new Route(['GET'], '/', $callable, $responseFactory, $callableResolver, $invocationStrategy, $groups);

        $this->assertEquals($groups, $route->getGroups());
    }

    public function testArgumentSetting()
    {
        $route = $this->createRoute();
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
        $route = $this->createRoute();

        $reflection = new ReflectionClass(Route::class);
        $property = $reflection->getProperty('middlewareRunner');
        $property->setAccessible(true);
        $middlewareRunner = $property->getValue($route);

        $responseFactory = $this->getResponseFactory();
        $route->add(function ($request, $handler) use (&$bottom, $responseFactory) {
            return $responseFactory->createResponse();
        });
        $route->finalize();

        /** @var array $middleware */
        $middleware = $middlewareRunner->getMiddleware();
        $bottom = $middleware[1];

        $this->assertInstanceOf(Route::class, $bottom);
    }

    public function testAddMiddleware()
    {
        $route = $this->createRoute();
        $called = 0;

        $mw = new ClosureMiddleware(function ($request, $handler) use (&$called) {
            $called++;
            return $handler->handle($request);
        });
        $route->addMiddleware($mw);

        $request = $this->createServerRequest('/');
        $route->run($request);

        $this->assertSame($called, 1);
    }

    public function testAddMiddlewareOnGroup()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            return $response;
        };

        $responseFactory = $this->getResponseFactory();
        $callableResolver = new CallableResolver();
        $invocationStrategy = new RequestResponse();

        $called = 0;
        $mw = new ClosureMiddleware(function ($request, $handler) use (&$called) {
            $called++;
            return $handler->handle($request);
        });
        $routeGroup = new RouteGroup('/group', $callable, $responseFactory, $callableResolver);
        $routeGroup->addMiddleware($mw);
        $groups = [$routeGroup];

        $route = new Route(['GET'], '/', $callable, $responseFactory, $callableResolver, $invocationStrategy, $groups);

        $request = $this->createServerRequest('/');
        $route->run($request);

        $this->assertSame($called, 1);
    }

    public function testAddClosureMiddleware()
    {
        $route = $this->createRoute();
        $called = 0;

        $route->add(function ($request, $handler) use (&$called) {
            $called++;
            return $handler->handle($request);
        });

        $request = $this->createServerRequest('/');
        $route->run($request);

        $this->assertSame($called, 1);
    }

    public function testAddMiddlewareUsingDeferredResolution()
    {
        $route = $this->createRoute();
        $route->add(MockMiddlewareWithoutConstructor::class);

        $output = '';
        $appendToOutput = function (string $value) use (&$output) {
            $output .= $value;
        };
        $request = $this->createServerRequest('/')->withAttribute('appendToOutput', $appendToOutput);
        $route->run($request);

        $this->assertSame('Hello World', $output);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage
     * Parameter 1 of `Slim\Route::add()` must be a closure or an object/class name
     * referencing an implementation of MiddlewareInterface.
     */
    public function testAddMiddlewareAsStringNotImplementingInterfaceThrowsException()
    {
        $route = $this->createRoute();
        $route->add(new MockMiddlewareWithoutInterface());
    }

    public function testRefinalizing()
    {
        $route = $this->createRoute();
        $called = 0;

        $route->add(function ($request, $handler) use (&$called) {
            $called++;
            return $handler->handle($request);
        });

        $route->finalize();
        $route->finalize();

        $request = $this->createServerRequest('/');
        $route->run($request);

        $this->assertSame($called, 1);
    }

    public function testIdentifier()
    {
        $route = $this->createRoute();
        $this->assertEquals('route0', $route->getIdentifier());
    }

    public function testSetName()
    {
        $route = $this->createRoute();
        $this->assertEquals($route, $route->setName('foo'));
        $this->assertEquals('foo', $route->getName());
    }

    public function testControllerMethodAsStringResolvesWithoutContainer()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();

        $deferred = new DeferredCallable('\Slim\Tests\Mocks\CallableTest:toCall', $callableResolver);
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver);

        CallableTest::$CalledCount = 0;

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testControllerMethodAsStringResolvesWithContainer()
    {
        $pimple = new Pimple();
        $pimple['CallableTest'] = new CallableTest();
        $container = new Psr11Container($pimple);
        $callableResolver = new CallableResolver($container);
        $responseFactory = $this->getResponseFactory();

        $deferred = new DeferredCallable('CallableTest:toCall', $callableResolver);
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver);

        CallableTest::$CalledCount = 0;

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    /**
     * Ensure that the response returned by a route callable is the response
     * object that is returned by __invoke().
     */
    public function testProcessWhenReturningAResponse()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write('foo');
            return $response;
        };
        $route = $this->createRoute(['GET'], '/', $callable);

        CallableTest::$CalledCount = 0;

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertEquals('foo', (string) $response->getBody());
    }

    /**
     * Ensure that anything echo'd in a route callable is, by default, NOT
     * added to the response object body.
     */
    public function testRouteCallableDoesNotAppendEchoedOutput()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            echo "foo";
            return $response->withStatus(201);
        };
        $route = $this->createRoute(['GET'], '/', $callable);

        $request = $this->createServerRequest('/');

        // We capture output buffer here only to clean test CLI output
        ob_start();
        $response = $route->run($request);
        ob_end_clean();

        // Output buffer is ignored without optional middleware
        $this->assertEquals('', (string) $response->getBody());
        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Ensure that if a string is returned by a route callable, then it is
     * added to the response object that is returned by __invoke().
     */
    public function testRouteCallableAppendsCorrectOutputToResponse()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write('foo');
            return $response;
        };
        $route = $this->createRoute(['GET'], '/', $callable);

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

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
        $route = $this->createRoute(['GET'], '/', $callable);

        $request = $this->createServerRequest('/');
        $route->run($request);
    }

    /**
     * Ensure that `foundHandler` is called on actual callable
     */
    public function testInvokeDeferredCallableWithNoContainer()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $invocationStrategy = new InvocationStrategyTest();

        $deferred = '\Slim\Tests\Mocks\CallableTest:toCall';
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver, $invocationStrategy);

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals([new CallableTest(), 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }

    /**
     * Ensure that `foundHandler` is called on actual callable
     */
    public function testInvokeDeferredCallableWithContainer()
    {
        $pimple = new Pimple();
        $pimple['CallableTest'] = new CallableTest;
        $container = new Psr11Container($pimple);
        $callableResolver = new CallableResolver($container);
        $responseFactory = $this->getResponseFactory();
        $invocationStrategy = new InvocationStrategyTest();

        $deferred = '\Slim\Tests\Mocks\CallableTest:toCall';
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver, $invocationStrategy);

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals([new CallableTest(), 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }

    public function testInvokeUsesRequestHandlerStrategyForRequestHandlers()
    {
        $pimple = new Pimple();
        $pimple[RequestHandlerTest::class] = new RequestHandlerTest();
        $container = new Psr11Container($pimple);
        $callableResolver = new CallableResolver($container);
        $responseFactory = $this->getResponseFactory();

        $deferred = RequestHandlerTest::class;
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver);

        $request = $this->createServerRequest('/', 'GET');
        $route->run($request);

        /** @var InvocationStrategyInterface $strategy */
        $strategy = $pimple[RequestHandlerTest::class]::$strategy;
        $this->assertEquals(RequestHandler::class, $strategy);
    }

    /**
     * Ensure that the pattern can be dynamically changed
     */
    public function testPatternCanBeChanged()
    {
        $route = $this->createRoute();
        $route->setPattern('/hola/{nombre}');

        $this->assertEquals('/hola/{nombre}', $route->getPattern());
    }

    /**
     * Ensure that the callable can be changed
     */
    public function testChangingCallableWithNoContainer()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();

        $deferred = 'NonExistent:toCall';
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver);
        $route->setCallable('\Slim\Tests\Mocks\CallableTest:toCall'); //Then we fix it here.

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals([new CallableTest(), 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }

    /**
     * Ensure that the callable can be changed
     */
    public function testChangingCallableWithContainer()
    {
        $pimple = new Pimple();
        $pimple['CallableTest2'] = new CallableTest;
        $container = new Psr11Container($pimple);
        $callableResolver = new CallableResolver($container);
        $responseFactory = $this->getResponseFactory();
        $invocationStrategy = new InvocationStrategyTest();

        $deferred = 'NonExistent:toCall';
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver, $invocationStrategy);
        $route->setCallable('CallableTest2:toCall'); //Then we fix it here.

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals([$pimple['CallableTest2'], 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }

    public function testRouteCallableIsResolvedUsingContainerWhenCallableResolverIsPresent()
    {
        $responseFactory = $this->getResponseFactory();

        $pimple = new Pimple();
        $pimple['CallableTest3'] = new CallableTest;
        $pimple['ClosureMiddleware'] = new ClosureMiddleware(function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Hello');
            return $response;
        });
        $container = new Psr11Container($pimple);
        $callableResolver = new CallableResolver($container);
        $invocationStrategy = new InvocationStrategyTest();

        $deferred = 'CallableTest3';
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver, $invocationStrategy);
        $route->add('ClosureMiddleware');

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertEquals('Hello', (string) $response->getBody());
    }
}
