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
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\CallableResolver;
use Slim\DeferredCallable;
use Slim\Handlers\Strategies\RequestHandler;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Routing\Route;
use Slim\Routing\RouteGroup;
use Slim\Tests\Mocks\CallableTest;
use Slim\Tests\Mocks\InvocationStrategyTest;
use Slim\Tests\Mocks\MockCustomRequestHandlerInvocationStrategy;
use Slim\Tests\Mocks\MockMiddlewareWithoutConstructor;
use Slim\Tests\Mocks\MockMiddlewareWithoutInterface;
use Slim\Tests\Mocks\RequestHandlerTest;

class RouteTest extends TestCase
{
    /**
     * @param string|array        $methods
     * @param string              $pattern
     * @param Closure|string|null $callable
     * @return Route
     */
    public function createRoute($methods = 'GET', string $pattern = '/', $callable = null): Route
    {
        $callable = $callable ?? function (ServerRequestInterface $request, ResponseInterface $response) {
                return $response;
        };

        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $callableResolverProphecy
            ->resolve($callable)
            ->willReturn($callable);

        $streamProphecy = $this->prophesize(StreamInterface::class);

        $value = '';
        $streamProphecy
            ->write(Argument::type('string'))
            ->will(function ($args) use ($value) {
                $value .= $args[0];
                $this->__toString()->willReturn($value);
                return $this->reveal();
            });

        $streamProphecy
            ->__toString()
            ->willReturn($value);

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseProphecy
            ->getBody()
            ->willReturn($streamProphecy->reveal());

        $responseProphecy
            ->withStatus(Argument::type('integer'))
            ->will(function ($args) {
                $this->getStatusCode()->willReturn($args[0]);
                return $this->reveal();
            });

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse()
            ->willReturn($responseProphecy->reveal());

        $methods = is_string($methods) ? [$methods] : $methods;
        return new Route(
            $methods,
            $pattern,
            $callable,
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal()
        );
    }

    public function testConstructor()
    {
        $methods = ['GET', 'POST'];
        $pattern = '/hello/{name}';
        $callable = function ($request, $response, $args) {
            return $response;
        };
        $route = $this->createRoute($methods, $pattern, $callable);

        $this->assertEquals($methods, $route->getMethods());
        $this->assertEquals($pattern, $route->getPattern());
        $this->assertEquals($callable, $route->getCallable());
    }

    public function testGetMethodsReturnsArrayWhenConstructedWithString()
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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $route = new Route(
            ['GET'],
            '/',
            $callable,
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal()
        );

        $this->assertSame($callableResolverProphecy->reveal(), $route->getCallableResolver());
    }

    public function testGetInvocationStrategy()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $strategyProphecy = $this->prophesize(InvocationStrategyInterface::class);

        $route = new Route(
            ['GET'],
            '/',
            $callable,
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            $containerProphecy->reveal(),
            $strategyProphecy->reveal()
        );

        $this->assertSame($strategyProphecy->reveal(), $route->getInvocationStrategy());
    }

    public function testSetInvocationStrategy()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $strategyProphecy = $this->prophesize(InvocationStrategyInterface::class);

        $route = new Route(
            ['GET'],
            '/',
            $callable,
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal()
        );
        $route->setInvocationStrategy($strategyProphecy->reveal());

        $this->assertSame($strategyProphecy->reveal(), $route->getInvocationStrategy());
    }

    public function testGetGroups()
    {
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $strategyProphecy = $this->prophesize(InvocationStrategyInterface::class);
        $routeCollectorProxyProphecy = $this->prophesize(RouteCollectorProxyInterface::class);

        $routeGroup = new RouteGroup(
            '/group',
            $callable,
            $callableResolverProphecy->reveal(),
            $routeCollectorProxyProphecy->reveal()
        );
        $groups = [$routeGroup];

        $route = new Route(
            ['GET'],
            '/',
            $callable,
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            null,
            $strategyProphecy->reveal(),
            $groups
        );

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

        $mw = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use (&$called) {
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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };

        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $callableResolverProphecy
            ->resolve($callable)
            ->willReturn($callable)
            ->shouldBeCalledOnce();

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse()
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeCollectorProxyProphecy = $this->prophesize(RouteCollectorProxyInterface::class);
        $strategy = new RequestResponse();

        $called = 0;
        $mw = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use (&$called) {
            $called++;
            return $handler->handle($request);
        };

        $routeGroup = new RouteGroup(
            '/group',
            $callable,
            $callableResolverProphecy->reveal(),
            $routeCollectorProxyProphecy->reveal()
        );
        $routeGroup->add($mw);
        $groups = [$routeGroup];

        $route = new Route(
            ['GET'],
            '/',
            $callable,
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            null,
            $strategy,
            $groups
        );

        $request = $this->createServerRequest('/');
        $route->run($request);

        $this->assertSame($called, 1);
    }

    public function testAddClosureMiddleware()
    {
        $route = $this->createRoute();
        $called = 0;

        $route->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use (&$called) {
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
        $self = $this;

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $callable = 'CallableTest:toCall';
        $deferred = new DeferredCallable($callable, $callableResolverProphecy->reveal());

        $callableResolverProphecy
            ->resolve($callable)
            ->willReturn(function (
                ServerRequestInterface $request,
                ResponseInterface $response
            ) use (
                $self,
                $responseProphecy
            ) {
                $self->assertSame($responseProphecy->reveal(), $response);
                return $response;
            })
            ->shouldBeCalledOnce();

        $callableResolverProphecy
            ->resolve($deferred)
            ->willReturn($deferred)
            ->shouldBeCalledOnce();

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse()
            ->willReturn($responseProphecy->reveal());

        $route = new Route(
            ['GET'],
            '/',
            $deferred,
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal()
        );

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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('foo');
            return $response;
        };
        $route = $this->createRoute(['GET'], '/', $callable);

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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $callable = function (ServerRequestInterface $request, ResponseInterface $response) {
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
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse()
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledOnce();

        $callableResolver = new CallableResolver();
        $invocationStrategy = new InvocationStrategyTest();

        $deferred = '\Slim\Tests\Mocks\CallableTest:toCall';
        $route = new Route(
            ['GET'],
            '/',
            $deferred,
            $responseFactoryProphecy->reveal(),
            $callableResolver,
            null,
            $invocationStrategy
        );

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
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse()
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledOnce();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('\Slim\Tests\Mocks\CallableTest')->willReturn(true);
        $containerProphecy->get('\Slim\Tests\Mocks\CallableTest')->willReturn(new CallableTest());

        $callableResolver = new CallableResolver($containerProphecy->reveal());
        $strategy = new InvocationStrategyTest();

        $deferred = '\Slim\Tests\Mocks\CallableTest:toCall';
        $route = new Route(
            ['GET'],
            '/',
            $deferred,
            $responseFactoryProphecy->reveal(),
            $callableResolver,
            $containerProphecy->reveal(),
            $strategy
        );

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals([new CallableTest(), 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }

    public function testInvokeUsesRequestHandlerStrategyForRequestHandlers()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse()
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledOnce();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(RequestHandlerTest::class)->willReturn(true);
        $containerProphecy->get(RequestHandlerTest::class)->willReturn(new RequestHandlerTest());

        $callableResolver = new CallableResolver($containerProphecy->reveal());

        $deferred = RequestHandlerTest::class;
        $route = new Route(
            ['GET'],
            '/',
            $deferred,
            $responseFactoryProphecy->reveal(),
            $callableResolver,
            $containerProphecy->reveal()
        );

        $request = $this->createServerRequest('/', 'GET');
        $route->run($request);

        /** @var InvocationStrategyInterface $strategy */
        $strategy = $containerProphecy
            ->reveal()
            ->get(RequestHandlerTest::class)::$strategy;

        $this->assertEquals(RequestHandler::class, $strategy);
    }

    public function testInvokeUsesUserSetStrategyForRequestHandlers()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse()
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledOnce();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(RequestHandlerTest::class)->willReturn(true);
        $containerProphecy->get(RequestHandlerTest::class)->willReturn(new RequestHandlerTest());

        $callableResolver = new CallableResolver($containerProphecy->reveal());

        $deferred = RequestHandlerTest::class;
        $route = new Route(
            ['GET'],
            '/',
            $deferred,
            $responseFactoryProphecy->reveal(),
            $callableResolver,
            $containerProphecy->reveal()
        );

        $strategy = new MockCustomRequestHandlerInvocationStrategy();
        $route->setInvocationStrategy($strategy);

        $request = $this->createServerRequest('/', 'GET');
        $route->run($request);

        $this->assertEquals(1, $strategy::$CalledCount);
    }

    public function testRequestHandlerStrategyAppendsRouteArgumentsAsAttributesToRequest()
    {
        $self = $this;

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse()
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledOnce();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(RequestHandlerTest::class)->willReturn(true);
        $containerProphecy->get(RequestHandlerTest::class)->willReturn(new RequestHandlerTest());

        $callableResolver = new CallableResolver($containerProphecy->reveal());

        $deferred = RequestHandlerTest::class;
        $route = new Route(
            ['GET'],
            '/',
            $deferred,
            $responseFactoryProphecy->reveal(),
            $callableResolver,
            $containerProphecy->reveal()
        );

        $strategy = new RequestHandler(true);
        $route->setInvocationStrategy($strategy);
        $route->setArguments(['id' => 1]);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->withAttribute(Argument::type('string'), Argument::any())->will(function ($args) use ($self) {
            $name = $args[0];
            $value = $args[1];

            $self->assertEquals('id', $name);
            $self->assertEquals(1, $value);

            return $this;
        })->shouldBeCalledOnce();

        $route->run($requestProphecy->reveal());
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
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse()
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledOnce();

        $callableResolver = new CallableResolver();

        $deferred = 'NonExistent:toCall';
        $route = new Route(
            ['GET'],
            '/',
            $deferred,
            $responseFactoryProphecy->reveal(),
            $callableResolver
        );
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
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse()
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledOnce();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('CallableTest2')->willReturn(true);
        $containerProphecy->get('CallableTest2')->willReturn(new CallableTest());

        $callableResolver = new CallableResolver($containerProphecy->reveal());
        $strategy = new InvocationStrategyTest();

        $deferred = 'NonExistent:toCall';
        $route = new Route(
            ['GET'],
            '/',
            $deferred,
            $responseFactoryProphecy->reveal(),
            $callableResolver,
            $containerProphecy->reveal(),
            $strategy
        );
        $route->setCallable('CallableTest2:toCall'); //Then we fix it here.

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(
            [$containerProphecy->reveal()->get('CallableTest2'), 'toCall'],
            InvocationStrategyTest::$LastCalledFor
        );
    }

    public function testRouteCallableIsResolvedUsingContainerWhenCallableResolverIsPresent()
    {
        $streamProphecy = $this->prophesize(StreamInterface::class);

        $value = '';
        $streamProphecy
            ->write(Argument::type('string'))
            ->will(function ($args) use ($value) {
                $value .= $args[0];
                $this->__toString()->willReturn($value);
                return $this->reveal();
            });

        $streamProphecy
            ->__toString()
            ->willReturn($value);

        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseProphecy
            ->getBody()
            ->willReturn($streamProphecy->reveal())
            ->shouldBeCalledTimes(2);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse()
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledOnce();

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('CallableTest3')->willReturn(true);
        $containerProphecy->get('CallableTest3')->willReturn(new CallableTest());
        $containerProphecy->has('ClosureMiddleware')->willReturn(true);
        $containerProphecy->get('ClosureMiddleware')->willReturn(function () use ($responseFactoryProphecy) {
            $response = $responseFactoryProphecy->reveal()->createResponse();
            $response->getBody()->write('Hello');
            return $response;
        });

        $callableResolver = new CallableResolver($containerProphecy->reveal());
        $strategy = new InvocationStrategyTest();

        $deferred = 'CallableTest3';
        $route = new Route(
            ['GET'],
            '/',
            $deferred,
            $responseFactoryProphecy->reveal(),
            $callableResolver,
            $containerProphecy->reveal(),
            $strategy
        );
        $route->add('ClosureMiddleware');

        $request = $this->createServerRequest('/');
        $response = $route->run($request);

        $this->assertEquals('Hello', (string) $response->getBody());
    }
}
