<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Slim\Container;
use Slim\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    /** @var Router */
    protected $router;

    public function setUp()
    {
        $this->router = new Router;
    }

    public function testMap()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->router->map($methods, $pattern, $callable);

        $this->assertInstanceOf('\Slim\Interfaces\RouteInterface', $route);
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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Route pattern must be a string
     */
    public function testMapWithInvalidPatternType()
    {
        $methods = ['GET'];
        $pattern = ['foo'];
        $callable = function ($request, $response, $args) {
        };

        $this->router->map($methods, $pattern, $callable);
    }

    /**
     * Base path is ignored by relativePathFor()
     *
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSettingInvalidBasePath()
    {
        $this->router->setBasePath(['invalid']);
    }

    public function testCreateDispatcher()
    {
        $class = new \ReflectionClass($this->router);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);
        $this->assertInstanceOf('\FastRoute\Dispatcher', $method->invoke($this->router));
    }

    public function testSetDispatcher()
    {
        $this->router->setDispatcher(\FastRoute\simpleDispatcher(function ($r) {
            $r->addRoute('GET', '/', function () {
            });
        }));
        $class = new \ReflectionClass($this->router);
        $prop = $class->getProperty('dispatcher');
        $prop->setAccessible(true);
        $this->assertInstanceOf('\FastRoute\Dispatcher', $prop->getValue($this->router));
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
        $route = $this->router->map($methods, $pattern, $callable);
        $route->setName('foo');

        $route->setPattern('/hallo/{voornaam:\w+}/{achternaam}');

        $this->assertEquals(
            '/hallo/josh/lockhart',
            $this->router->relativePathFor('foo', ['voornaam' => 'josh', 'achternaam' => 'lockhart'])
        );
    }

    /**
     * Test cacheFile may be set to false
     */
    public function testSettingCacheFileToFalse()
    {
        $this->router->setCacheFile(false);

        $class = new \ReflectionClass($this->router);
        $property = $class->getProperty('cacheFile');
        $property->setAccessible(true);

        $this->assertFalse($property->getValue($this->router));
    }

    /**
     * Test cacheFile should be a string or false
     */
    public function testSettingInvalidCacheFileValue()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Router cacheFile must be a string'
        );
        $this->router->setCacheFile(['invalid']);
    }

    /**
     * Test if cacheFile is not a writable directory
     */
    public function testSettingInvalidCacheFileNotExisting()
    {
        $this->setExpectedException(
            '\RuntimeException',
            'Router cacheFile directory must be writable'
        );

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
        $class = new \ReflectionClass($this->router);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);

        $dispatcher = $method->invoke($this->router);
        $this->assertInstanceOf('\FastRoute\Dispatcher', $dispatcher);
        $this->assertFileExists($cacheFile, 'cache file was not created');

        // instantiate a new router & load the cached routes file & see if
        // we can dispatch to the route we cached.
        $router2 = new Router();
        $router2->setCacheFile($cacheFile);

        $class = new \ReflectionClass($router2);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);

        $dispatcher2 = $method->invoke($this->router);
        $result = $dispatcher2->dispatch('GET', '/hello/josh/lockhart');
        $this->assertSame(\FastRoute\Dispatcher::FOUND, $result[0]);

        unlink($cacheFile);
    }

    /**
     * Calling createDispatcher as second time should give you back the same
     * dispatcher as when you called it the first time.
     */
    public function testCreateDispatcherReturnsSameDispatcherASecondTime()
    {
        $class = new \ReflectionClass($this->router);
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

        //create a router that mocks the pathFor with expected args
        $router = $this->getMockBuilder('\Slim\Router')->setMethods(['pathFor'])->getMock();
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
