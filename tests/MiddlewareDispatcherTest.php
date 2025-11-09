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
use Slim\Interfaces\CallableResolverInterface;
use Slim\Tests\Mocks\MockMiddlewareSlimCallable;
use Slim\Tests\Mocks\MockMiddlewareWithConstructor;
use Slim\Tests\Mocks\MockMiddlewareWithoutConstructor;
use Slim\Tests\Mocks\MockRequestHandler;
use Slim\Tests\Mocks\MockSequenceMiddleware;
use stdClass;

class MiddlewareDispatcherTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        function testProcessRequest(ServerRequestInterface $request, RequestHandlerInterface $handler)
        {
            return $handler->handle($request);
        }
    }

    public function testAddMiddleware(): void
    {
        $responseFactory = $this->getResponseFactory();
        $callable = function ($request, $handler) use ($responseFactory) {
            return $responseFactory->createResponse();
        };

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestHandlerProphecy = $this->prophesize(RequestHandlerInterface::class);

        $middlewareDispatcher = $this->createMiddlewareDispatcher($requestHandlerProphecy->reveal());
        $middlewareDispatcher->add($callable);

        $response = $middlewareDispatcher->handle($requestProphecy->reveal());
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testNamedFunctionIsResolved(): void
    {
        $handler = new MockRequestHandler();

        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, null);
        $middlewareDispatcher->addDeferred(__NAMESPACE__ . '\testProcessRequest');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertSame(1, $handler->getCalledCount());
    }

    public function testDeferredResolvedCallable(): void
    {
        $callable = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        };

        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy
            ->has('callable')
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $containerProphecy
            ->get('callable')
            ->willReturn($callable)
            ->shouldBeCalledOnce();

        $handler = new MockRequestHandler();

        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, $containerProphecy->reveal());
        $middlewareDispatcher->addDeferred('callable');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertSame(1, $handler->getCalledCount());
    }

    public function testDeferredResolvedCallableWithoutContainerAndNonAdvancedCallableResolver(): void
    {
        $callable = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        };

        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $callableResolverProphecy
            ->resolve('callable')
            ->willReturn($callable)
            ->shouldBeCalledOnce();

        $handler = new MockRequestHandler();

        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, null, $callableResolverProphecy->reveal());
        $middlewareDispatcher->addDeferred('callable');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertSame(1, $handler->getCalledCount());
    }

    public function testDeferredResolvedCallableWithDirectConstructorCall(): void
    {
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $callableResolverProphecy
            ->resolve(MockMiddlewareWithoutConstructor::class)
            ->willThrow(new RuntimeException('Callable not available from resolver'))
            ->shouldBeCalledOnce();

        $handler = new MockRequestHandler();

        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, null, $callableResolverProphecy->reveal());
        $middlewareDispatcher->addDeferred(MockMiddlewareWithoutConstructor::class);

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertSame(1, $handler->getCalledCount());
    }

    public static function deferredCallableProvider(): array
    {
        return [
            [MockMiddlewareSlimCallable::class . ':custom', new MockMiddlewareSlimCallable()],
            ['MiddlewareInstance', new MockMiddlewareWithoutConstructor()],
            ['NamedFunction', __NAMESPACE__ . '\testProcessRequest'],
            ['Callable', function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                return $handler->handle($request);
            }],
            ['MiddlewareInterfaceNotImplemented', 'MiddlewareInterfaceNotImplemented']
        ];
    }

    /**
     * @dataProvider deferredCallableProvider
     *
     * @param string $callable
     * @param callable|MiddlewareInterface
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('deferredCallableProvider')]
    public function testDeferredResolvedCallableWithContainerAndNonAdvancedCallableResolverUnableToResolveCallable(
        $callable,
        $result
    ): void {
        if ($callable === 'MiddlewareInterfaceNotImplemented') {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Middleware MiddlewareInterfaceNotImplemented is not resolvable');
        }

        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $callableResolverProphecy
            ->resolve($callable)
            ->willThrow(RuntimeException::class)
            ->shouldBeCalledOnce();

        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy
            ->has(Argument::any())
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $containerProphecy
            ->get(Argument::any())
            ->willReturn($result)
            ->shouldBeCalledOnce();

        $handler = new MockRequestHandler();

        $middlewareDispatcher = $this->createMiddlewareDispatcher(
            $handler,
            $containerProphecy->reveal(),
            $callableResolverProphecy->reveal()
        );
        $middlewareDispatcher->addDeferred($callable);

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertSame(1, $handler->getCalledCount());
    }

    public function testDeferredResolvedSlimCallable(): void
    {
        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, null);
        $middlewareDispatcher->addDeferred(MockMiddlewareSlimCallable::class . ':custom');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertSame(1, $handler->getCalledCount());
    }

    public function testDeferredResolvedClosureIsBoundToContainer(): void
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

        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, $containerProphecy->reveal());
        $middlewareDispatcher->addDeferred('callable');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    public function testAddCallableBindsClosureToContainer(): void
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $self = $this;
        $callable = function (
            ServerRequestInterface $request,
            RequestHandlerInterface $handler
        ) use (
            $self,
            $containerProphecy
        ) {
            $self->assertSame($containerProphecy->reveal(), $this);
            return $handler->handle($request);
        };

        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, $containerProphecy->reveal());
        $middlewareDispatcher->addCallable($callable);

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    public function testResolvableReturnsInstantiatedObject(): void
    {
        MockMiddlewareWithoutConstructor::$CalledCount = 0;

        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, null);
        $middlewareDispatcher->addDeferred(MockMiddlewareWithoutConstructor::class);

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertSame(1, MockMiddlewareWithoutConstructor::$CalledCount);
        $this->assertSame(1, $handler->getCalledCount());
    }

    public function testResolveThrowsExceptionWhenResolvableDoesNotImplementMiddlewareInterface(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MiddlewareInterfaceNotImplemented is not resolvable');

        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy
            ->has('MiddlewareInterfaceNotImplemented')
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $containerProphecy
            ->get('MiddlewareInterfaceNotImplemented')
            ->willReturn(new stdClass())
            ->shouldBeCalledOnce();

        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, $containerProphecy->reveal());
        $middlewareDispatcher->addDeferred('MiddlewareInterfaceNotImplemented');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    public function testResolveThrowsExceptionWithoutContainerAndUnresolvableClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/(Middleware|Callable) Unresolvable::class does not exist/');

        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, null);
        $middlewareDispatcher->addDeferred('Unresolvable::class');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    public function testResolveThrowsExceptionWithoutContainerNonAdvancedCallableResolverAndUnresolvableClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/(Middleware|Callable) Unresolvable::class does not exist/');

        $unresolvable = 'Unresolvable::class';

        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $callableResolverProphecy
            ->resolve($unresolvable)
            ->willThrow(RuntimeException::class)
            ->shouldBeCalledOnce();

        $handler = new MockRequestHandler();
        $middlewareDispatcher = $this->createMiddlewareDispatcher($handler, null, $callableResolverProphecy->reveal());
        $middlewareDispatcher->addDeferred($unresolvable);

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    public function testExecutesKernelWithEmptyMiddlewareStack(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->willReturn($responseProphecy->reveal());
        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();

        $dispatcher = $this->createMiddlewareDispatcher($kernel, null);

        $response = $dispatcher->handle($requestProphecy->reveal());

        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldHaveBeenCalled();
        $this->assertSame($responseProphecy->reveal(), $response);
    }

    public function testExecutesMiddlewareLastInFirstOut(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $requestProphecy->getHeader(Argument::type('string'))->willReturn([]);
        $requestProphecy->withAddedHeader(Argument::type('string'), Argument::type('string'))->will(function ($args) {
            $headers = $this->reveal()->getHeader($args[0]);

            $headers[] = $args[1];
            $this->getHeader($args[0])->willReturn($headers);
            $this->hasHeader($args[0])->willReturn(true);
            return $this;
        });

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getHeader(Argument::type('string'))->willReturn([]);
        $responseProphecy->withHeader(Argument::type('string'), Argument::type('array'))->will(function ($args) {
            $this->getHeader($args[0])->willReturn($args[1]);
            $this->hasHeader($args[0])->willReturn(true);
            return $this;
        });
        $responseProphecy->withAddedHeader(Argument::type('string'), Argument::type('string'))->will(function ($args) {
            $headers = $this->reveal()->getHeader($args[0]);

            $headers[] = $args[1];
            $this->getHeader($args[0])->willReturn($headers);
            $this->hasHeader($args[0])->willReturn(true);
            return $this;
        });
        $responseProphecy->withStatus(Argument::type('int'))->will(function ($args) {
            $this->getStatusCode()->willReturn($args[0]);
            return $this;
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
        $dispatcher = $this->createMiddlewareDispatcher($kernel, null);
        $dispatcher->add($middleware0Prophecy->reveal());
        $dispatcher->addMiddleware($middleware1Prophecy->reveal());
        $dispatcher->addDeferred(MockSequenceMiddleware::class);
        $dispatcher->add($middleware3Prophecy->reveal());

        $response = $dispatcher->handle($requestProphecy->reveal());

        $this->assertSame(['3', '2', '1', '0'], $response->getHeader('X-SEQ-PRE-REQ-HANDLER'));
        $this->assertSame(['0', '1', '2', '3'], $response->getHeader('X-SEQ-POST-REQ-HANDLER'));
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testDoesNotInstantiateDeferredMiddlewareInCaseOfAnEarlyReturningOuterMiddleware(): void
    {
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->willReturn($responseProphecy->reveal());

        MockSequenceMiddleware::$hasBeenInstantiated = false;
        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();
        $dispatcher = $this->createMiddlewareDispatcher($kernel, null);
        $dispatcher->addDeferred(MockSequenceMiddleware::class);
        $dispatcher->addMiddleware($middlewareProphecy->reveal());
        $response = $dispatcher->handle($requestProphecy->reveal());

        $this->assertFalse(MockSequenceMiddleware::$hasBeenInstantiated);
        $this->assertSame($responseProphecy->reveal(), $response);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldNotHaveBeenCalled();
    }

    public function testThrowsExceptionForDeferredNonMiddlewareInterfaceClasses(): void
    {
        $this->expectException(RuntimeException::class);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);

        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();
        $dispatcher = $this->createMiddlewareDispatcher($kernel, null);
        $dispatcher->addDeferred(stdClass::class);
        $dispatcher->handle($requestProphecy->reveal());

        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldNotHaveBeenCalled();
    }

    public function testCanBeExecutedMultipleTimes(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->willReturn($responseProphecy->reveal());

        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();
        $dispatcher = $this->createMiddlewareDispatcher($kernel, null);
        $dispatcher->add($middlewareProphecy->reveal());

        $response1 = $dispatcher->handle($requestProphecy->reveal());
        $response2 = $dispatcher->handle($requestProphecy->reveal());

        $this->assertSame($responseProphecy->reveal(), $response1);
        $this->assertSame($responseProphecy->reveal(), $response2);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldNotHaveBeenCalled();
    }

    public function testCanBeReExecutedRecursivelyDuringDispatch(): void
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);

        $requestProphecy->hasHeader('X-NESTED')->willReturn(false);
        $requestProphecy->withAddedHeader('X-NESTED', '1')->will(function () {
            $this->hasHeader('X-NESTED')->willReturn(true);
            return $this;
        });

        $responseProphecy->getHeader(Argument::type('string'))->willReturn([]);
        $responseProphecy->withAddedHeader(Argument::type('string'), Argument::type('string'))->will(function ($args) {
            $headers = $this->reveal()->getHeader($args[0]);

            $headers[] = $args[1];
            $this->getHeader($args[0])->willReturn($headers);
            $this->hasHeader($args[0])->willReturn(true);
            return $this;
        });

        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();
        $dispatcher = $this->createMiddlewareDispatcher($kernel, null);

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

    public function testFetchesMiddlewareFromContainer(): void
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
        $dispatcher = $this->createMiddlewareDispatcher($kernel, $container);
        $dispatcher->addDeferred('somemiddlewarename');
        $response = $dispatcher->handle($requestProphecy->reveal());

        $this->assertSame($responseProphecy->reveal(), $response);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldNotHaveBeenCalled();
    }

    public function testMiddlewareGetsInstantiatedWithContainer(): void
    {
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(MockMiddlewareWithConstructor::class)->willReturn(false);
        /** @var ContainerInterface $container */
        $container = $containerProphecy->reveal();
        /** @var RequestHandlerInterface $kernel */
        $kernel = $kernelProphecy->reveal();
        $dispatcher = $this->createMiddlewareDispatcher($kernel, $container);
        $dispatcher->addDeferred(MockMiddlewareWithConstructor::class);
        $dispatcher->handle($requestProphecy->reveal());

        $this->assertSame($containerProphecy->reveal(), MockMiddlewareWithConstructor::$container);
    }
}
