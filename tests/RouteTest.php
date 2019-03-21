<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use Closure;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\CallableResolver;
use Slim\DeferredCallable;
use Slim\Handlers\Strategies\RequestHandler;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\InvocationStrategyInterface;
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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            return $response;
        };

        $responseFactory = $this->getResponseFactory();
        $callableResolver = new CallableResolver();

        $route = new Route(['GET'], '/', $callable, $responseFactory, $callableResolver);

        $this->assertEquals($callableResolver, $route->getCallableResolver());
    }

    public function testGetInvocationStrategy()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            return $response;
        };

        $responseFactory = $this->getResponseFactory();
        $callableResolver = new CallableResolver();
        $strategy = new RequestResponse();

        $route = new Route(['GET'], '/', $callable, $responseFactory, $callableResolver, null, $strategy);

        $this->assertEquals($strategy, $route->getInvocationStrategy());
    }

    public function testGetGroups()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            return $response;
        };

        $responseFactory = $this->getResponseFactory();
        $callableResolver = new CallableResolver();
        $strategy = new RequestResponse();

        $routeGroup = new RouteGroup('/group', $callable, $callableResolver);
        $groups = [$routeGroup];

        $route = new Route(['GET'], '/', $callable, $responseFactory, $callableResolver, null, $strategy, $groups);

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

    public function testAddMiddleware()
    {
        $route = $this->createRoute();
        $called = 0;

        $mw = function ($request, $handler) use (&$called) {
            $called++;
            return $handler->handle($request);
        };
        $route->add($mw);

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
        $strategy = new RequestResponse();

        $called = 0;
        $mw = function ($request, $handler) use (&$called) {
            $called++;
            return $handler->handle($request);
        };
        $routeGroup = new RouteGroup('/group', $callable, $callableResolver);
        $routeGroup->add($mw);
        $groups = [$routeGroup];

        $route = new Route(['GET'], '/', $callable, $responseFactory, $callableResolver, null, $strategy, $groups);

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

    public function testAddMiddlewareAsStringNotImplementingInterfaceThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'A middleware must be an object/class name referencing an implementation of ' .
            'MiddlewareInterface or a callable with a matching signature.'
        );

        $route = $this->createRoute();
        $route->add(new MockMiddlewareWithoutInterface());
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
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('CallableTest')->willReturn(true);
        $containerProphecy->get('CallableTest')->willReturn(new CallableTest());

        $callableResolver = new CallableResolver($containerProphecy->reveal());
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
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver, null, $invocationStrategy);

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
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('\Slim\Tests\Mocks\CallableTest')->willReturn(true);
        $containerProphecy->get('\Slim\Tests\Mocks\CallableTest')->willReturn(new CallableTest());
        $container = $containerProphecy->reveal();

        $callableResolver = new CallableResolver($container);
        $responseFactory = $this->getResponseFactory();
        $strategy = new InvocationStrategyTest();

        $deferred = '\Slim\Tests\Mocks\CallableTest:toCall';
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver, $container, $strategy);

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals([new CallableTest(), 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }

    public function testInvokeUsesRequestHandlerStrategyForRequestHandlers()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(RequestHandlerTest::class)->willReturn(true);
        $containerProphecy->get(RequestHandlerTest::class)->willReturn(new RequestHandlerTest());
        $container = $containerProphecy->reveal();

        $callableResolver = new CallableResolver($container);
        $responseFactory = $this->getResponseFactory();

        $deferred = RequestHandlerTest::class;
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver, $container);

        $request = $this->createServerRequest('/', 'GET');
        $route->run($request);

        /** @var InvocationStrategyInterface $strategy */
        $strategy = $container->get(RequestHandlerTest::class)::$strategy;
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
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver, null);
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
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('CallableTest2')->willReturn(true);
        $containerProphecy->get('CallableTest2')->willReturn(new CallableTest());
        $container = $containerProphecy->reveal();

        $callableResolver = new CallableResolver($container);
        $responseFactory = $this->getResponseFactory();
        $strategy = new InvocationStrategyTest();

        $deferred = 'NonExistent:toCall';
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver, $container, $strategy);
        $route->setCallable('CallableTest2:toCall'); //Then we fix it here.

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals([$container->get('CallableTest2'), 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }

    public function testRouteCallableIsResolvedUsingContainerWhenCallableResolverIsPresent()
    {
        $responseFactory = $this->getResponseFactory();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('CallableTest3')->willReturn(true);
        $containerProphecy->get('CallableTest3')->willReturn(new CallableTest());
        $containerProphecy->has('ClosureMiddleware')->willReturn(true);
        $containerProphecy->get('ClosureMiddleware')->willReturn(function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Hello');
            return $response;
        });
        $container = $containerProphecy->reveal();

        $callableResolver = new CallableResolver($container);
        $strategy = new InvocationStrategyTest();

        $deferred = 'CallableTest3';
        $route = new Route(['GET'], '/', $deferred, $responseFactory, $callableResolver, $container, $strategy);
        $route->add('ClosureMiddleware');

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertEquals('Hello', (string) $response->getBody());
    }
}
