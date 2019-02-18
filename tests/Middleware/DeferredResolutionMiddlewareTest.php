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
use ReflectionClass;
use Slim\Middleware\DeferredResolutionMiddleware;
use Slim\Tests\Mocks\MockMiddlewareWithoutConstructor;
use Slim\Tests\TestCase;
use stdClass;

/**
 * Class DeferredResolutionMiddlewareTest
 * @package Slim\Tests\Middleware
 */
class DeferredResolutionMiddlewareTest extends TestCase
{
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
     * @expectedExceptionMessage Middleware MiddlewareInterfaceNotImplemented does not implement MiddlewareInterface
     */
    public function testResolveThrowsExceptionWhenResolvableDoesNotImplementMiddlewareInterface()
    {
        $pimple = new Pimple();
        $pimple->offsetSet('MiddlewareInterfaceNotImplemented', new stdClass());
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
     * @expectedExceptionMessage Middleware Unresolvable::class does not exist
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
