<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use Psr\Http\Message\ResponseFactoryInterface;
use ReflectionMethod;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Routing\Dispatcher;
use Slim\Routing\FastRouteDispatcher;
use Slim\Routing\RouteCollector;
use Slim\Routing\RoutingResults;
use Slim\Tests\TestCase;

use function dirname;
use function microtime;
use function uniqid;
use function unlink;

class DispatcherTest extends TestCase
{
    public function testCreateDispatcher()
    {
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $dispatcher = new Dispatcher($routeCollector);

        $method = new ReflectionMethod(Dispatcher::class, 'createDispatcher');
        $this->setAccessible($method);

        $this->assertInstanceOf(FastRouteDispatcher::class, $method->invoke($dispatcher));
    }

    /**
     * Test cached routes file is created & that it holds our routes.
     */
    public function testRouteCacheFileCanBeDispatched()
    {
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $dispatcher = new Dispatcher($routeCollector);

        $route = $routeCollector->map(['GET'], '/', function () {
        });
        $route->setName('foo');

        $cacheFile = __DIR__ . '/' . uniqid((string) microtime(true));
        $routeCollector->setCacheFile($cacheFile);

        $method = new ReflectionMethod(Dispatcher::class, 'createDispatcher');
        $this->setAccessible($method);
        $method->invoke($dispatcher);
        $this->assertFileExists($cacheFile, 'cache file was not created');

        $routeCollector2 = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeCollector2->setCacheFile($cacheFile);
        $dispatcher2 = new Dispatcher($routeCollector2);

        $method = new ReflectionMethod(Dispatcher::class, 'createDispatcher');
        $this->setAccessible($method);
        $method->invoke($dispatcher2);

        /** @var RoutingResults $result */
        $result = $dispatcher2->dispatch('GET', '/');
        $this->assertSame(FastRouteDispatcher::FOUND, $result->getRouteStatus());

        unlink($cacheFile);
    }

    /**
     * Calling createDispatcher as second time should give you back the same
     * dispatcher as when you called it the first time.
     */
    public function testCreateDispatcherReturnsSameDispatcherASecondTime()
    {
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $dispatcher = new Dispatcher($routeCollector);

        $method = new ReflectionMethod(Dispatcher::class, 'createDispatcher');
        $this->setAccessible($method);

        $fastRouteDispatcher = $method->invoke($dispatcher);
        $fastRouteDispatcher2 = $method->invoke($dispatcher);

        $this->assertSame($fastRouteDispatcher, $fastRouteDispatcher2);
    }

    public function testGetAllowedMethods()
    {
        $methods = ['GET', 'POST', 'PUT'];
        $uri = '/';

        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeCollector->map($methods, $uri, function () {
        });

        $dispatcher = new Dispatcher($routeCollector);
        $results = $dispatcher->getAllowedMethods('/');

        $this->assertSame($methods, $results);
    }

    public function testDispatch()
    {
        $methods = ['GET', 'POST'];
        $uri = '/hello/{name}';

        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $callable = function () {
        };
        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $route = $routeCollector->map($methods, $uri, $callable);

        $dispatcher = new Dispatcher($routeCollector);
        $results = $dispatcher->dispatch('GET', '/hello/Foo%20Bar');

        $this->assertSame(RoutingResults::FOUND, $results->getRouteStatus());
        $this->assertSame('GET', $results->getMethod());
        $this->assertSame('/hello/Foo%20Bar', $results->getUri());
        $this->assertSame($route->getIdentifier(), $results->getRouteIdentifier());
        $this->assertSame(['name' => 'Foo Bar'], $results->getRouteArguments());
        $this->assertSame(['name' => 'Foo%20Bar'], $results->getRouteArguments(false));
        $this->assertSame($methods, $results->getAllowedMethods());
        $this->assertSame($dispatcher, $results->getDispatcher());
    }
}
