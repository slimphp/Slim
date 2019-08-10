<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Tests\Mocks\MockMiddlewareSlimCallable;
use Slim\Tests\Mocks\MockMiddlewareWithConstructor;
use Slim\Tests\Mocks\MockMiddlewareWithoutConstructor;
use Slim\Tests\Mocks\MockRequestHandler;
use Slim\Tests\Mocks\MockSequenceMiddleware;
use stdClass;

class MiddlewareDispatcherTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        function testProcessRequest(ServerRequestInterface $request, RequestHandlerInterface $handler)
        {
            return $handler->handle($request);
        }
    }

    /**
     * Provide a boolean flag to indicate whether the test case should use the
     * advanced callable resolver or the non-advanced callable resolver
     *
     * @return array
     */
    public function useAdvancedCallableResolverDataProvider(): array
    {
        return [[true], [false]];
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testAddMiddleware(bool $useAdvancedCallableResolver)
    {
        $responseFactory = $this->getResponseFactory();
        $callable = function ($request, $handler) use ($responseFactory) {
            return $responseFactory->createResponse();
        };

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $this->createMock(RequestHandlerInterface::class),
            null,
            $useAdvancedCallableResolver
        );
        $middlewareDispatcher->add($callable);

        $response = $middlewareDispatcher->handle($this->createMock(ServerRequestInterface::class));
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testNamedFunctionIsResolved(bool $useAdvancedCallableResolver)
    {
        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, null, $useAdvancedCallableResolver);
        $middlewareDispatcher->addDeferred(__NAMESPACE__ . '\testProcessRequest');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertEquals(1, $handler->getCalledCount());
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testDeferredResolvedCallable(bool $useAdvancedCallableResolver)
    {
        $callable = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        };
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('callable')->willReturn(true);
        $containerProphecy->get('callable')->willReturn($callable);
        /** @var ContainerInterface $container */
        $container = $containerProphecy->reveal();

        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, $container, $useAdvancedCallableResolver);
        $middlewareDispatcher->addDeferred('callable');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertEquals(1, $handler->getCalledCount());
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testDeferredResolvedSlimCallable(bool $useAdvancedCallableResolver)
    {
        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, null, $useAdvancedCallableResolver);
        $middlewareDispatcher->addDeferred(MockMiddlewareSlimCallable::class . ':custom');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertEquals(1, $handler->getCalledCount());
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testDeferredResolvedClosureIsBoundToContainer(bool $useAdvancedCallableResolver)
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $self = $this;
        $callable = function (
            ServerRequestInterface $request,
            RequestHandlerInterface $handler
        ) use ($self) {
            $self->assertInstanceOf(ContainerInterface::class, $this);

            return $handler->handle($request);
        };

        $containerProphecy->has('callable')->willReturn(true);
        $containerProphecy->get('callable')->willReturn($callable);
        /** @var ContainerInterface $container */
        $container = $containerProphecy->reveal();

        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, $container, $useAdvancedCallableResolver);
        $middlewareDispatcher->addDeferred('callable');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testAddCallableBindsClosureToContainer(bool $useAdvancedCallableResolver)
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        /** @var ContainerInterface $container */
        $container = $containerProphecy->reveal();

        $self = $this;
        $callable = function (
            ServerRequestInterface $request,
            RequestHandlerInterface $handler
        ) use (
            $self,
            $container
        ) {
            $self->assertInstanceOf(ContainerInterface::class, $this);
            $self->assertSame($container, $this);
            return $handler->handle($request);
        };

        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, $container, $useAdvancedCallableResolver);
        $middlewareDispatcher->addCallable($callable);

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testResolvableReturnsInstantiatedObject(bool $useAdvancedCallableResolver)
    {
        MockMiddlewareWithoutConstructor::$CalledCount = 0;

        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, null, $useAdvancedCallableResolver);
        $middlewareDispatcher->addDeferred(MockMiddlewareWithoutConstructor::class);

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertEquals(1, MockMiddlewareWithoutConstructor::$CalledCount);
        $this->assertEquals(1, $handler->getCalledCount());
    }

    /**
     * @dataProvider             useAdvancedCallableResolverDataProvider
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage MiddlewareInterfaceNotImplemented is not resolvable
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testResolveThrowsExceptionWhenResolvableDoesNotImplementMiddlewareInterface(
        bool $useAdvancedCallableResolver
    ) {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('MiddlewareInterfaceNotImplemented')->willReturn(true);
        $containerProphecy->get('MiddlewareInterfaceNotImplemented')->willReturn(new stdClass());
        /** @var ContainerInterface $container */
        $container = $containerProphecy->reveal();

        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, $container, $useAdvancedCallableResolver);
        $middlewareDispatcher->addDeferred('MiddlewareInterfaceNotImplemented');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    /**
     * @dataProvider                   useAdvancedCallableResolverDataProvider
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegExp /(Middleware|Callable) Unresolvable::class does not exist/
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testResolveThrowsExceptionWithoutContainerAndUnresolvableClass(bool $useAdvancedCallableResolver)
    {
        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, null, $useAdvancedCallableResolver);
        $middlewareDispatcher->addDeferred('Unresolvable::class');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testExecutesKernelWithEmptyMiddlewareStack(bool $useAdvancedCallableResolver)
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->willReturn($responseProphecy->reveal());
        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();

        $dispatcher = $this->createMiddlewareDispatcher($kernel, null, $useAdvancedCallableResolver);

        $response = $dispatcher->handle($requestProphecy->reveal());

        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldHaveBeenCalled();
        $this->assertEquals($responseProphecy->reveal(), $response);
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testExecutesMiddlewareLastInFirstOut(bool $useAdvancedCallableResolver)
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getHeader(Argument::type('string'))->willReturn([]);
        $requestProphecy->withAddedHeader(Argument::type('string'), Argument::type('string'))->will(function ($args) {
            $headers = $this->reveal()->getHeader($args[0]);

            $clone = clone $this;
            $headers[] = $args[1];
            $clone->getHeader($args[0])->willReturn($headers);
            $clone->hasHeader($args[0])->willReturn(true);
            return $clone;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getHeader(Argument::type('string'))->willReturn([]);
        $responseProphecy->withHeader(Argument::type('string'), Argument::type('array'))->will(function ($args) {
            $clone = clone $this;
            $clone->getHeader($args[0])->willReturn($args[1]);
            $clone->hasHeader($args[0])->willReturn(true);
            return $clone;
        });
        $responseProphecy->withAddedHeader(Argument::type('string'), Argument::type('string'))->will(function ($args) {
            $headers = $this->reveal()->getHeader($args[0]);

            $clone = clone $this;
            $headers[] = $args[1];
            $clone->getHeader($args[0])->willReturn($headers);
            $clone->hasHeader($args[0])->willReturn(true);
            return $clone;
        });
        $responseProphecy->withStatus(Argument::type('int'))->will(function ($args) {
            $clone = clone $this;
            $clone->getStatusCode()->willReturn($args[0]);
            return $clone;
        });

        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))
            ->will(function ($args) use ($responseProphecy): ResponseInterface {
                $request = $args[0];
                return $responseProphecy->reveal()
                    ->withStatus(204)
                    ->withHeader('X-SEQ-PRE-REQ-HANDLER', $request->getHeader('X-SEQ-PRE-REQ-HANDLER'));
            });

        $middleware0Prophecy = $this->prophesize(MiddlewareInterface::class);
        $middleware0Prophecy
            ->process(
                Argument::type(ServerRequestInterface::class),
                Argument::type(RequestHandlerInterface::class)
            )
            ->will(function ($args): ResponseInterface {
                return $args[1]->handle($args[0]->withAddedHeader('X-SEQ-PRE-REQ-HANDLER', '0'))
                    ->withAddedHeader('X-SEQ-POST-REQ-HANDLER', '0');
            });

        $middleware1Prophecy = $this->prophesize(MiddlewareInterface::class);
        $middleware1Prophecy
            ->process(
                Argument::type(ServerRequestInterface::class),
                Argument::type(RequestHandlerInterface::class)
            )
            ->will(function ($args): ResponseInterface {
                return $args[1]->handle($args[0]->withAddedHeader('X-SEQ-PRE-REQ-HANDLER', '1'))
                    ->withAddedHeader('X-SEQ-POST-REQ-HANDLER', '1');
            });

        MockSequenceMiddleware::$id = '2';

        $middleware3Prophecy = $this->prophesize(MiddlewareInterface::class);
        $middleware3Prophecy
            ->process(
                Argument::type(ServerRequestInterface::class),
                Argument::type(RequestHandlerInterface::class)
            )
            ->will(function ($args): ResponseInterface {
                return $args[1]->handle($args[0]->withAddedHeader('X-SEQ-PRE-REQ-HANDLER', '3'))
                    ->withAddedHeader('X-SEQ-POST-REQ-HANDLER', '3');
            });

        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();
        $dispatcher = $this->createMiddlewareDispatcher($kernel, null, $useAdvancedCallableResolver);
        $dispatcher->add($middleware0Prophecy->reveal());
        $dispatcher->addMiddleware($middleware1Prophecy->reveal());
        $dispatcher->addDeferred(MockSequenceMiddleware::class);
        $dispatcher->add($middleware3Prophecy->reveal());

        $response = $dispatcher->handle($requestProphecy->reveal());

        $this->assertSame(['3', '2', '1', '0'], $response->getHeader('X-SEQ-PRE-REQ-HANDLER'));
        $this->assertSame(['0', '1', '2', '3'], $response->getHeader('X-SEQ-POST-REQ-HANDLER'));
        $this->assertSame(204, $response->getStatusCode());
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testDoesNotInstantiateDeferredMiddlewareInCaseOfAnEarlyReturningOuterMiddleware(
        bool $useAdvancedCallableResolver
    ) {
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->willReturn($responseProphecy->reveal());

        MockSequenceMiddleware::$hasBeenInstantiated = false;
        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();
        $dispatcher = $this->createMiddlewareDispatcher($kernel, null, $useAdvancedCallableResolver);
        $dispatcher->addDeferred(MockSequenceMiddleware::class);
        $dispatcher->addMiddleware($middlewareProphecy->reveal());
        $response = $dispatcher->handle($requestProphecy->reveal());

        $this->assertFalse(MockSequenceMiddleware::$hasBeenInstantiated);
        $this->assertEquals($responseProphecy->reveal(), $response);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldNotHaveBeenCalled();
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testThrowsExceptionForDeferredNonMiddlewareInterfaceClasses(bool $useAdvancedCallableResolver)
    {
        $this->expectException(RuntimeException::class);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);

        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();
        $dispatcher = $this->createMiddlewareDispatcher($kernel, null, $useAdvancedCallableResolver);
        $dispatcher->addDeferred(\stdClass::class);
        $dispatcher->handle($requestProphecy->reveal());

        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldNotHaveBeenCalled();
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testCanBeExcutedMultipleTimes(bool $useAdvancedCallableResolver)
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->willReturn($responseProphecy->reveal());

        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();
        $dispatcher = $this->createMiddlewareDispatcher($kernel, null, $useAdvancedCallableResolver);
        $dispatcher->add($middlewareProphecy->reveal());

        $response1 = $dispatcher->handle($requestProphecy->reveal());
        $response2 = $dispatcher->handle($requestProphecy->reveal());

        $this->assertEquals($responseProphecy->reveal(), $response1);
        $this->assertEquals($responseProphecy->reveal(), $response2);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldNotHaveBeenCalled();
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testCanBeReExecutedRecursivelyDuringDispatch(bool $useAdvancedCallableResolver)
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);

        $requestProphecy->hasHeader('X-NESTED')->willReturn(false);
        $requestProphecy->withAddedHeader('X-NESTED', '1')->will(function () {
            $clone = clone $this;
            $clone->hasHeader('X-NESTED')->willReturn(true);
            return $clone;
        });

        $responseProphecy->getHeader(Argument::type('string'))->willReturn([]);
        $responseProphecy->withAddedHeader(Argument::type('string'), Argument::type('string'))->will(function ($args) {
            $headers = $this->reveal()->getHeader($args[0]);

            $clone = clone $this;
            $headers[] = $args[1];
            $clone->getHeader($args[0])->willReturn($headers);
            $clone->hasHeader($args[0])->willReturn(true);
            return $clone;
        });

        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();
        $dispatcher = $this->createMiddlewareDispatcher($kernel, null, $useAdvancedCallableResolver);

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy
            ->process(
                Argument::type(ServerRequestInterface::class),
                Argument::type(RequestHandlerInterface::class)
            )
            ->will(function ($args) use ($dispatcher, $responseProphecy): ResponseInterface {
                $request = $args[0];
                if ($request->hasHeader('X-NESTED')) {
                    return $responseProphecy->reveal()->withAddedHeader('X-TRACE', 'nested');
                }

                $response = $dispatcher->handle($request->withAddedHeader('X-NESTED', '1'));

                return $response->withAddedHeader('X-TRACE', 'outer');
            });
        $dispatcher->add($middlewareProphecy->reveal());

        $response = $dispatcher->handle($requestProphecy->reveal());

        $this->assertSame(['nested', 'outer'], $response->getHeader('X-TRACE'));
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testFetchesMiddlewareFromContainer(bool $useAdvancedCallableResolver)
    {
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->willReturn($responseProphecy->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('somemiddlewarename')->willReturn(true);
        $containerProphecy->get('somemiddlewarename')->willReturn($middlewareProphecy->reveal());
        /** @var ContainerInterface $container */
        $container = $containerProphecy->reveal();
        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();
        $dispatcher = $this->createMiddlewareDispatcher($kernel, $container, $useAdvancedCallableResolver);
        $dispatcher->addDeferred('somemiddlewarename');
        $response = $dispatcher->handle($requestProphecy->reveal());

        $this->assertEquals($responseProphecy->reveal(), $response);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldNotHaveBeenCalled();
    }

    /**
     * @dataProvider useAdvancedCallableResolverDataProvider
     *
     * @param bool $useAdvancedCallableResolver
     */
    public function testMiddlewareGetsInstantiatedWithContainer(bool $useAdvancedCallableResolver)
    {
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(MockMiddlewareWithConstructor::class)->willReturn(false);
        /** @var ContainerInterface $container */
        $container = $containerProphecy->reveal();
        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();
        $dispatcher = $this->createMiddlewareDispatcher($kernel, $container, $useAdvancedCallableResolver);
        $dispatcher->addDeferred(MockMiddlewareWithConstructor::class);
        $dispatcher->handle($requestProphecy->reveal());

        $this->assertSame($containerProphecy->reveal(), MockMiddlewareWithConstructor::$container);
    }
}
