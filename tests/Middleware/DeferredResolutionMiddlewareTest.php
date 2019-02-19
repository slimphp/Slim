<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Middleware;

use Pimple\Container as Pimple;
use Pimple\Psr11\Container as Psr11Container;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use Slim\Middleware\ClosureMiddleware;
use Slim\Middleware\DeferredResolutionMiddleware;
use Slim\Tests\Mocks\MockMiddlewareWithoutConstructor;
use Slim\Tests\Mocks\MockRequestHandler;
use Slim\Tests\TestCase;
use stdClass;

/**
 * Class DeferredResolutionMiddlewareTest
 * @package Slim\Tests\Middleware
 */
class DeferredResolutionMiddlewareTest extends TestCase
{
    public function testNamedFunctionIsResolved()
    {
        function testProcessRequest(ServerRequestInterface $request, RequestHandlerInterface $handler)
        {
            return $handler->handle($request);
        }

        $deferredResolutionMiddleware = new DeferredResolutionMiddleware(__NAMESPACE__ . '\testProcessRequest');

        $reflection = new ReflectionClass(DeferredResolutionMiddleware::class);
        $method = $reflection->getMethod('resolve');
        $method->setAccessible(true);

        /** @var MiddlewareInterface $result */
        $result = $method->invoke($deferredResolutionMiddleware);
        $this->assertInstanceOf(ClosureMiddleware::class, $result);

        $request = $this->createServerRequest('/');
        $handler = new MockRequestHandler();

        $result->process($request, $handler);
        $this->assertEquals(1, $handler->getCalledCount());
    }

    public function testDeferredResolvedCallableGetsWrappedInsideClosureMiddleware()
    {
        $pimple = new Pimple();
        $pimple['callable'] = function () {
            return function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                return $handler->handle($request);
            };
        };
        $container = new Psr11Container($pimple);

        $deferredResolutionMiddleware = new DeferredResolutionMiddleware('callable', $container);

        $reflection = new ReflectionClass(DeferredResolutionMiddleware::class);
        $method = $reflection->getMethod('resolve');
        $method->setAccessible(true);

        /** @var MiddlewareInterface $result */
        $result = $method->invoke($deferredResolutionMiddleware);
        $this->assertInstanceOf(ClosureMiddleware::class, $result);

        $request = $this->createServerRequest('/');
        $handler = new MockRequestHandler();

        $result->process($request, $handler);
        $this->assertEquals(1, $handler->getCalledCount());
    }

    public function testResolvableReturnsInstantiatedObject()
    {
        $reflection = new ReflectionClass(DeferredResolutionMiddleware::class);
        $deferredResolutionMiddlewareWrapper = new DeferredResolutionMiddleware(
            MockMiddlewareWithoutConstructor::class
        );

        $method = $reflection->getMethod('resolve');
        $method->setAccessible(true);
        $result = $method->invoke($deferredResolutionMiddlewareWrapper);

        $this->assertInstanceOf(MockMiddlewareWithoutConstructor::class, $result);
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

        $reflection = new ReflectionClass(DeferredResolutionMiddleware::class);
        $deferredResolutionMiddlewareWrapper = new DeferredResolutionMiddleware(
            'MiddlewareInterfaceNotImplemented',
            $container
        );

        $method = $reflection->getMethod('resolve');
        $method->setAccessible(true);
        $method->invoke($deferredResolutionMiddlewareWrapper);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unresolvable::class is not resolvable
     */
    public function testResolveThrowsExceptionWithoutContainerAndUnresolvableClass()
    {
        $reflection = new ReflectionClass(DeferredResolutionMiddleware::class);
        $deferredResolutionMiddlewareWrapper = new DeferredResolutionMiddleware('Unresolvable::class');

        $method = $reflection->getMethod('resolve');
        $method->setAccessible(true);
        $method->invoke($deferredResolutionMiddlewareWrapper);
    }
}
