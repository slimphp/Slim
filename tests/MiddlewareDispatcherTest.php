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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\MiddlewareDispatcher;
use Slim\Tests\Mocks\MockMiddlewareWithoutConstructor;
use Slim\Tests\Mocks\MockRequestHandler;
use stdClass;

/**
 * Class MiddlewareDispatcherTest
 * @package Slim\Tests
 */
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
        $pimple = new Pimple();
        $pimple['callable'] = function () {
            return function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                return $handler->handle($request);
            };
        };
        $container = new Psr11Container($pimple);

        $handler = new MockRequestHandler();
        $middlewareDispatcher = new MiddlewareDispatcher($handler, $container);
        $middlewareDispatcher->addDeferred('callable');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);

        $this->assertEquals(1, $handler->getCalledCount());
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
     * @expectedExceptionMessage MiddlewareInterfaceNotImplemented is not resolvable
     */
    public function testResolveThrowsExceptionWhenResolvableDoesNotImplementMiddlewareInterface()
    {
        $pimple = new Pimple();
        $pimple['MiddlewareInterfaceNotImplemented'] = new stdClass();
        $container = new Psr11Container($pimple);

        $handler = new MockRequestHandler();
        $middlewareDispatcher = new MiddlewareDispatcher($handler, $container);
        $middlewareDispatcher->addDeferred('MiddlewareInterfaceNotImplemented');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unresolvable::class is not resolvable
     */
    public function testResolveThrowsExceptionWithoutContainerAndUnresolvableClass()
    {
        $handler = new MockRequestHandler();
        $middlewareDispatcher = new MiddlewareDispatcher($handler);
        $middlewareDispatcher->addDeferred('Unresolvable::class');

        $request = $this->createServerRequest('/');
        $middlewareDispatcher->handle($request);
    }
}
