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
use Slim\MiddlewareDispatcher;
use Slim\Tests\Mocks\MockMiddlewareSlimCallable;
use Slim\Tests\Mocks\MockMiddlewareWithConstructor;
use Slim\Tests\Mocks\MockMiddlewareWithoutConstructor;
use Slim\Tests\Mocks\MockRequestHandler;
use Slim\Tests\Mocks\MockSequenceMiddleware;
use stdClass;

class MiddlewareDispatcherTest extends TestCase
{
    public function testAddMiddleware()
    {
        $responseFactory = $this->getResponseFactory();
        $callable = function ($request, $handler) use ($responseFactory) {
            return $responseFactory->createResponse();
        };

        $middlewareDispatcher = new MiddlewareDispatcher($this->createMock(RequestHandlerInterface::class));
        $middlewareDispatcher->add($callable);

        $response = $middlewareDispatcher->handle($this->createMock(ServerRequestInterface::class));
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testNamedFunctionIsResolved()
    {
        function testProcessRequest(ServerRequestInterface $request, RequestHandlerInterface $handler)
        {
            return $handler->handle($request);
        }

        $handler = new MockRequestHandler();
        $middlewareDispatcher = new MiddlewareDispatcher($handler);
        $middlewareDispatcher->addDeferred(__NAMESPACE__ . '\testProcessRequest');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertEquals(1, $handler->getCalledCount());
    }

    public function testDeferredResolvedCallable()
    {
        $callable = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        };
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('callable')->willReturn(true);
        $containerProphecy->get('callable')->willReturn($callable);

        $handler = new MockRequestHandler();
        $middlewareDispatcher = new MiddlewareDispatcher($handler, $containerProphecy->reveal());
        $middlewareDispatcher->addDeferred('callable');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertEquals(1, $handler->getCalledCount());
    }

    public function testDeferredResolvedSlimCallable()
    {
        $handler = new MockRequestHandler();
        $middlewareDispatcher = new MiddlewareDispatcher($handler, null);
        $middlewareDispatcher->addDeferred(MockMiddlewareSlimCallable::class . ':custom');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertEquals(1, $handler->getCalledCount());
    }

    public function testDeferredResolvedClosureIsBoundToContainer()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $self = $this;
        $callable = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($self, $containerProphecy) {
            $self->assertSame($containerProphecy->reveal(), $this);
            return $handler->handle($request);
        };

        $containerProphecy->has('callable')->willReturn(true);
        $containerProphecy->get('callable')->willReturn($callable);

        $handler = new MockRequestHandler();
        $middlewareDispatcher = new MiddlewareDispatcher($handler, $containerProphecy->reveal());
        $middlewareDispatcher->addDeferred('callable');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    public function testAddCallableBindsClosureToContainer()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $self = $this;
        $callable = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($self, $containerProphecy) {
            $self->assertSame($containerProphecy->reveal(), $this);
            return $handler->handle($request);
        };

        $handler = new MockRequestHandler();
        $middlewareDispatcher = new MiddlewareDispatcher($handler, $containerProphecy->reveal());
        $middlewareDispatcher->addCallable($callable);

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    public function testResolvableReturnsInstantiatedObject()
    {
        MockMiddlewareWithoutConstructor::$CalledCount = 0;

        $handler = new MockRequestHandler();
        $middlewareDispatcher = new MiddlewareDispatcher($handler);
        $middlewareDispatcher->addDeferred(MockMiddlewareWithoutConstructor::class);

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertEquals(1, MockMiddlewareWithoutConstructor::$CalledCount);
        $this->assertEquals(1, $handler->getCalledCount());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Middleware MiddlewareInterfaceNotImplemented is not resolvable
     */
    public function testResolveThrowsExceptionWhenResolvableDoesNotImplementMiddlewareInterface()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('MiddlewareInterfaceNotImplemented')->willReturn(true);
        $containerProphecy->get('MiddlewareInterfaceNotImplemented')->willReturn(new stdClass());

        $handler = new MockRequestHandler();
        $middlewareDispatcher = new MiddlewareDispatcher($handler, $containerProphecy->reveal());
        $middlewareDispatcher->addDeferred('MiddlewareInterfaceNotImplemented');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Middleware Unresolvable::class does not exist
     */
    public function testResolveThrowsExceptionWithoutContainerAndUnresolvableClass()
    {
        $handler = new MockRequestHandler();
        $middlewareDispatcher = new MiddlewareDispatcher($handler);
        $middlewareDispatcher->addDeferred('Unresolvable::class');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    public function testExecutesKernelWithEmptyMiddlewareStack()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->willReturn($responseProphecy->reveal());

        $dispatcher = new MiddlewareDispatcher($kernelProphecy->reveal());

        $response = $dispatcher->handle($requestProphecy->reveal());

        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldHaveBeenCalled();
        $this->assertEquals($responseProphecy->reveal(), $response);
    }

    public function testExecutesMiddlewareLastInFirstOut()
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

        $dispatcher = new MiddlewareDispatcher($kernelProphecy->reveal());
        $dispatcher->add($middleware0Prophecy->reveal());
        $dispatcher->addMiddleware($middleware1Prophecy->reveal());
        $dispatcher->addDeferred(MockSequenceMiddleware::class);
        $dispatcher->add($middleware3Prophecy->reveal());

        $response = $dispatcher->handle($requestProphecy->reveal());

        $this->assertSame(['3', '2', '1', '0'], $response->getHeader('X-SEQ-PRE-REQ-HANDLER'));
        $this->assertSame(['0', '1', '2', '3'], $response->getHeader('X-SEQ-POST-REQ-HANDLER'));
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testDoesNotInstantiateDeferredMiddlewareInCaseOfAnEarlyReturningOuterMiddleware()
    {
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->willReturn($responseProphecy->reveal());

        MockSequenceMiddleware::$hasBeenInstantiated = false;
        $dispatcher = new MiddlewareDispatcher($kernelProphecy->reveal());
        $dispatcher->addDeferred(MockSequenceMiddleware::class);
        $dispatcher->addMiddleware($middlewareProphecy->reveal());
        $response = $dispatcher->handle($requestProphecy->reveal());

        $this->assertFalse(MockSequenceMiddleware::$hasBeenInstantiated);
        $this->assertEquals($responseProphecy->reveal(), $response);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldNotHaveBeenCalled();
    }

    public function testThrowsExceptionForDeferredNonMiddlewareInterfaceClasses()
    {
        $this->expectException(\RuntimeException::class);

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);

        $dispatcher = new MiddlewareDispatcher($kernelProphecy->reveal());
        $dispatcher->addDeferred(\stdClass::class);
        $dispatcher->handle($requestProphecy->reveal());

        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldNotHaveBeenCalled();
    }

    public function testCanBeExcutedMultipleTimes()
    {
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->willReturn($responseProphecy->reveal());

        $dispatcher = new MiddlewareDispatcher($kernelProphecy->reveal());
        $dispatcher->add($middlewareProphecy->reveal());

        $response1 = $dispatcher->handle($requestProphecy->reveal());
        $response2 = $dispatcher->handle($requestProphecy->reveal());

        $this->assertEquals($responseProphecy->reveal(), $response1);
        $this->assertEquals($responseProphecy->reveal(), $response2);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldNotHaveBeenCalled();
    }

    public function testCanBeReExecutedRecursivelyDuringDispatch()
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

        $dispatcher = new MiddlewareDispatcher($kernelProphecy->reveal());

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

    public function testFetchesMiddlewareFromContainer()
    {
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $middlewareProphecy = $this->prophesize(MiddlewareInterface::class);
        $middlewareProphecy->process(Argument::cetera())->willReturn($responseProphecy->reveal());

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('somemiddlewarename')->willReturn(true);
        $containerProphecy->get('somemiddlewarename')->willReturn($middlewareProphecy->reveal());

        $dispatcher = new MiddlewareDispatcher($kernelProphecy->reveal(), $containerProphecy->reveal());
        $dispatcher->addDeferred('somemiddlewarename');
        $response = $dispatcher->handle($requestProphecy->reveal());

        $this->assertEquals($responseProphecy->reveal(), $response);
        $kernelProphecy->handle(Argument::type(ServerRequestInterface::class))->shouldNotHaveBeenCalled();
    }

    public function testMiddlewareGetsInstantiatedWithContainer()
    {
        $kernelProphecy = $this->prophesize(RequestHandlerInterface::class);
        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has(MockMiddlewareWithConstructor::class)->willReturn(false);

        $dispatcher = new MiddlewareDispatcher($kernelProphecy->reveal(), $containerProphecy->reveal());
        $dispatcher->addDeferred(MockMiddlewareWithConstructor::class);
        $dispatcher->handle($requestProphecy->reveal());

        $this->assertSame($containerProphecy->reveal(), MockMiddlewareWithConstructor::$container);
    }
}
