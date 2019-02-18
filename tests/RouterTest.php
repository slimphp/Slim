<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use FastRoute\RouteCollector;
use Grpc\Call;
use ReflectionClass;
use Slim\CallableResolver;
use Slim\Dispatcher;
use Slim\Interfaces\RouteInterface;
use Slim\Route;
use Slim\RoutingResults;
use Slim\Router;
use Slim\Tests\Mocks\InvocationStrategyTest;

class RouterTest extends TestCase
{
    /** @var Router */
    protected $router;

    public function setUp()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $this->router = new Router($responseFactory, $callableResolver);
    }

    public function testMap()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->router->map($methods, $pattern, $callable);

        $this->assertInstanceOf(RouteInterface::class, $route);
        $this->assertAttributeContains($route, 'routes', $this->router);
    }

    public function testMapPrependsGroupPattern()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };

        $this->router->pushGroup('/prefix', function () {
        });
        $route = $this->router->map($methods, $pattern, $callable);
        $this->router->popGroup();

        $this->assertAttributeEquals('/prefix/hello/{first}/{last}', 'pattern', $route);
    }

    /**
     * Base path is ignored by relativePathFor()
     */
    public function testRelativePathFor()
    {
        $this->router->setBasePath('/base/path');

        $methods = ['GET'];
        $pattern = '/hello/{first:\w+}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->router->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/hello/josh/lockhart',
            $this->router->relativePathFor('foo', ['first' => 'josh', 'last' => 'lockhart'])
        );
    }

    public function testPathForWithNoBasePath()
    {
        $this->router->setBasePath('');

        $methods = ['GET'];
        $pattern = '/hello/{first:\w+}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->router->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/hello/josh/lockhart',
            $this->router->pathFor('foo', ['first' => 'josh', 'last' => 'lockhart'])
        );
    }

    public function testPathForWithBasePath()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first:\w+}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $this->router->setBasePath('/base/path');
        $route = $this->router->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/base/path/hello/josh/lockhart',
            $this->router->pathFor('foo', ['first' => 'josh', 'last' => 'lockhart'])
        );
    }

    public function testPathForWithOptionalParameters()
    {
        $methods = ['GET'];
        $pattern = '/archive/{year}[/{month:[\d:{2}]}[/d/{day}]]';
        $callable = function ($request, $response, $args) {
            return $response;
        };
        $route = $this->router->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/archive/2015',
            $this->router->pathFor('foo', ['year' => '2015'])
        );
        $this->assertEquals(
            '/archive/2015/07',
            $this->router->pathFor('foo', ['year' => '2015', 'month' => '07'])
        );
        $this->assertEquals(
            '/archive/2015/07/d/19',
            $this->router->pathFor('foo', ['year' => '2015', 'month' => '07', 'day' => '19'])
        );
    }

    public function testPathForWithQueryParameters()
    {
        $methods = ['GET'];
        $pattern = '/hello/{name}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s', $args['name']);
        };
        $route = $this->router->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/hello/josh?a=b&c=d',
            $this->router->pathFor('foo', ['name' => 'josh'], ['a' => 'b', 'c' => 'd'])
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPathForWithMissingSegmentData()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->router->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->router->pathFor('foo', ['last' => 'lockhart']);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testPathForRouteNotExists()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->router->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->router->pathFor('bar', ['first' => 'josh', 'last' => 'lockhart']);
    }

    public function testCreateDispatcher()
    {
        $class = new ReflectionClass($this->router);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);
        $this->assertInstanceOf(Dispatcher::class, $method->invoke($this->router));
    }

    public function testSetDispatcher()
    {
        /** @var Dispatcher $dispatcher */
        $dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) {
        }, ['dispatcher' => Dispatcher::class]);
        $this->router->setDispatcher($dispatcher);

        $class = new ReflectionClass($this->router);
        $prop = $class->getProperty('dispatcher');
        $prop->setAccessible(true);

        $this->assertEquals($dispatcher, $prop->getValue($this->router));
    }

    public function testGetRouteInvocationStrategy()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $invocationStrategy = new InvocationStrategyTest();
        $router = new Router($responseFactory, $callableResolver, $invocationStrategy);

        $this->assertEquals($invocationStrategy, $router->getDefaultInvocationStrategy());
    }

    public function testGetCallableResolver()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $router = new Router($responseFactory, $callableResolver);

        $this->assertEquals($callableResolver, $router->getCallableResolver());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRemoveRoute()
    {
        $methods = ['GET'];
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello ignore me');
        };

        $this->router->setBasePath('/base/path');

        $route1 = $this->router->map($methods, '/foo', $callable);
        $route1->setName('foo');

        $route2 = $this->router->map($methods, '/bar', $callable);
        $route2->setName('bar');

        $route3 = $this->router->map($methods, '/fizz', $callable);
        $route3->setName('fizz');

        $route4 = $this->router->map($methods, '/buzz', $callable);
        $route4->setName('buzz');

        $routeToRemove = $this->router->getNamedRoute('fizz');

        $routeCountBefore = count($this->router->getRoutes());
        $this->router->removeNamedRoute($routeToRemove->getName());
        $routeCountAfter = count($this->router->getRoutes());

        // Assert number of routes is now less by 1
        $this->assertEquals(
            ($routeCountBefore - 1),
            $routeCountAfter
        );

        // Simple test that the correct route was removed
        $this->assertEquals(
            $this->router->getNamedRoute('foo')->getName(),
            'foo'
        );

        $this->assertEquals(
            $this->router->getNamedRoute('bar')->getName(),
            'bar'
        );

        $this->assertEquals(
            $this->router->getNamedRoute('buzz')->getName(),
            'buzz'
        );

        // Exception thrown here, route no longer exists
        $this->router->getNamedRoute($routeToRemove->getName());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRouteRemovalNotExists()
    {
        $this->router->setBasePath('/base/path');
        $this->router->removeNamedRoute('non-existing-route-name');
    }

    public function testPathForWithModifiedRoutePattern()
    {
        $this->router->setBasePath('/base/path');

        $methods = ['GET'];
        $pattern = '/hello/{first:\w+}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['voornaam'], $args['achternaam']);
        };

        /** @var Route $route */
        $route = $this->router->map($methods, $pattern, $callable);
        $route->setName('foo');
        $route->setPattern('/hallo/{voornaam:\w+}/{achternaam}');

        $this->assertEquals(
            '/hallo/josh/lockhart',
            $this->router->relativePathFor('foo', ['voornaam' => 'josh', 'achternaam' => 'lockhart'])
        );
    }

    /**
     * Test if cacheFile is not a writable directory
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Router cacheFile directory must be writable
     */
    public function testSettingInvalidCacheFileNotExisting()
    {
        $this->router->setCacheFile(
            dirname(__FILE__) . uniqid(microtime(true)) . '/' . uniqid(microtime(true))
        );
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
        $route = $this->router->map($methods, $pattern, $callable)->setName('foo');

        $cacheFile = dirname(__FILE__) . '/' . uniqid(microtime(true));
        $this->router->setCacheFile($cacheFile);
        $class = new ReflectionClass($this->router);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);

        $dispatcher = $method->invoke($this->router);
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
        $this->assertFileExists($cacheFile, 'cache file was not created');

        // instantiate a new router & load the cached routes file & see if
        // we can dispatch to the route we cached.
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $router2 = new Router($responseFactory, $callableResolver);
        $router2->setCacheFile($cacheFile);

        $class = new ReflectionClass($router2);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);

        $dispatcher2 = $method->invoke($this->router);

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
        $class = new ReflectionClass($this->router);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);

        $dispatcher = $method->invoke($this->router);
        $dispatcher2 = $method->invoke($this->router);
        $this->assertSame($dispatcher2, $dispatcher);
    }

    /**
     * Test that the router urlFor will proxy into a pathFor method, and trigger
     * the user deprecated warning
     */
    public function testUrlForAliasesPathFor()
    {
        //create a temporary error handler, store the error str in this value
        $errorString = null;

        set_error_handler(function ($no, $str) use (&$errorString) {
            $errorString = $str;
        }, E_USER_DEPRECATED);

        //create the parameters we expect
        $name = 'foo';
        $data = ['name' => 'josh'];
        $queryParams = ['a' => 'b', 'c' => 'd'];

        /** @var Router $router */
        $router = $this
            ->getMockBuilder(Router::class)
            ->setConstructorArgs([$this->getResponseFactory(), new CallableResolver()])
            ->setMethods(['pathFor'])
            ->getMock();
        $router->expects($this->once())->method('pathFor')->with($name, $data, $queryParams);
        $router->urlFor($name, $data, $queryParams);

        //check that our error was triggered
        $this->assertEquals($errorString, 'urlFor() is deprecated. Use pathFor() instead.');

        restore_error_handler();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLookupRouteThrowsExceptionIfRouteNotFound()
    {
        $this->router->lookupRoute("thisIsMissing");
    }
}
