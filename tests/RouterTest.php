<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

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
     * Test cacheFile should be a string
     */
    public function testSettingInvalidCacheFileNotString()
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
     * Test if cache is enabled but cache file is not set
     */
    public function testCacheFileNotSetButCacheEnabled()
    {
        $this->setExpectedException(
            '\RuntimeException',
            'Router cache enabled but cacheFile not set'
        );

        $this->router->setCacheDisabled(false);

        $class = new \ReflectionClass($this->router);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);
        $method->invoke($this->router);
    }

    /**
     * Test route dispatcher is created in case of route cache
     */
    public function testCreateDispatcherWithRouteCache()
    {
        $cacheFile = dirname(__FILE__) . '/' . uniqid(microtime(true));
        $this->router->setCacheDisabled(false);
        $this->router->setCacheFile($cacheFile);
        $class = new \ReflectionClass($this->router);
        $method = $class->getMethod('createDispatcher');
        $method->setAccessible(true);
        $this->assertInstanceOf('\FastRoute\Dispatcher', $method->invoke($this->router));
        unlink($cacheFile);
    }
}
