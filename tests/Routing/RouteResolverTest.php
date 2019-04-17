<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use FastRoute\RouteCollector as FRouteCollector;
use ReflectionClass;
use Slim\CallableResolver;
use Slim\Routing\Dispatcher;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteResolver;
use Slim\Routing\RoutingResults;
use Slim\Tests\TestCase;

class RouteResolverTest extends TestCase
{
    /**
     * @var RouteCollector
     */
    protected $routeCollector;

    /**
     * @var RouteResolver
     */
    protected $routeResolver;

    public function setUp()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $this->routeCollector = new RouteCollector($responseFactory, $callableResolver);
        $this->routeResolver = new RouteResolver($this->routeCollector);
    }

    public function testCreateDispatcher()
    {
        $class = new ReflectionClass($this->routeResolver);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);

        $this->assertInstanceOf(Dispatcher::class, $method->invoke($this->routeResolver));
    }

    public function testSetDispatcher()
    {
        /** @var Dispatcher $dispatcher */
        $dispatcher = \FastRoute\simpleDispatcher(function (FRouteCollector $r) {
        }, ['dispatcher' => Dispatcher::class]);

        $this->routeResolver->setDispatcher($dispatcher);

        $class = new ReflectionClass($this->routeResolver);
        $prop = $class->getProperty('dispatcher');
        $prop->setAccessible(true);

        $this->assertEquals($dispatcher, $prop->getValue($this->routeResolver));
    }

    /**
     * Test cached routes file is created & that it holds our routes.
     */
    public function testRouteCacheFileCanBeDispatched()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable)->setName('foo');

        $cacheFile = dirname(__FILE__) . '/' . uniqid((string) microtime(true));
        $this->routeCollector->setCacheFile($cacheFile);

        $class = new ReflectionClass($this->routeResolver);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);

        $dispatcher = $method->invoke($this->routeResolver);
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $this->assertFileExists($cacheFile, 'cache file was not created');

        // instantiate a new router & load the cached routes file & see if
        // we can dispatch to the route we cached.
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();

        $routeCollector2 = new RouteCollector($responseFactory, $callableResolver);
        $routeCollector2->setCacheFile($cacheFile);

        $routeResolver2 = new RouteResolver($routeCollector2);

        $class = new ReflectionClass($routeResolver2);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);

        $dispatcher2 = $method->invoke($routeResolver2);

        /** @var RoutingResults $result */
        $result = $dispatcher2->dispatch('GET', '/hello/josh/lockhart');
        $this->assertSame(Dispatcher::FOUND, $result->getRouteStatus());

        unlink($cacheFile);
    }

    /**
     * Calling createDispatcher as second time should give you back the same
     * dispatcher as when you called it the first time.
     */
    public function testCreateDispatcherReturnsSameDispatcherASecondTime()
    {
        $class = new ReflectionClass($this->routeResolver);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);

        $dispatcher = $method->invoke($this->routeResolver);
        $dispatcher2 = $method->invoke($this->routeResolver);
        $this->assertSame($dispatcher2, $dispatcher);
    }
}
