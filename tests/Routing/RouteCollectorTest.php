<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use Slim\CallableResolver;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\Route;
use Slim\Routing\RouteCollector;
use Slim\Tests\Mocks\InvocationStrategyTest;
use Slim\Tests\TestCase;

class RouteCollectorTest extends TestCase
{
    /**
     * @var RouteCollector
     */
    protected $routeCollector;

    /**
     * @var null|string
     */
    protected $cacheFile;

    public function tearDown()
    {
        if ($this->cacheFile && file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function setUp()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $this->routeCollector = new RouteCollector($responseFactory, $callableResolver);
    }

    public function testGetSetBasePath()
    {
        $basePath = '/base/path';

        $this->routeCollector->setBasePath($basePath);

        $this->assertEquals($basePath, $this->routeCollector->getBasePath());
    }

    public function testMap()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable);

        $this->assertInstanceOf(RouteInterface::class, $route);
        $this->assertAttributeContains($route, 'routes', $this->routeCollector);
    }

    public function testMapPrependsGroupPattern()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };

        $this->routeCollector->pushGroup('/prefix', function () {
        });
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $this->routeCollector->popGroup();

        $this->assertAttributeEquals('/prefix/hello/{first}/{last}', 'pattern', $route);
    }

    /**
     * Base path is ignored by relativePathFor()
     */
    public function testRelativePathFor()
    {
        $this->routeCollector->setBasePath('/base/path');

        $methods = ['GET'];
        $pattern = '/hello/{first:\w+}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/hello/josh/lockhart',
            $this->routeCollector->relativePathFor('foo', ['first' => 'josh', 'last' => 'lockhart'])
        );
    }

    public function testPathForWithNoBasePath()
    {
        $this->routeCollector->setBasePath('');

        $methods = ['GET'];
        $pattern = '/hello/{first:\w+}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/hello/josh/lockhart',
            $this->routeCollector->pathFor('foo', ['first' => 'josh', 'last' => 'lockhart'])
        );
    }

    public function testPathForWithBasePath()
    {
        $methods = ['GET'];
        $pattern = '/hello/{first:\w+}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['first'], $args['last']);
        };
        $this->routeCollector->setBasePath('/base/path');
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/base/path/hello/josh/lockhart',
            $this->routeCollector->pathFor('foo', ['first' => 'josh', 'last' => 'lockhart'])
        );
    }

    public function testPathForWithOptionalParameters()
    {
        $methods = ['GET'];
        $pattern = '/archive/{year}[/{month:[\d:{2}]}[/d/{day}]]';
        $callable = function ($request, $response, $args) {
            return $response;
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/archive/2015',
            $this->routeCollector->pathFor('foo', ['year' => '2015'])
        );
        $this->assertEquals(
            '/archive/2015/07',
            $this->routeCollector->pathFor('foo', ['year' => '2015', 'month' => '07'])
        );
        $this->assertEquals(
            '/archive/2015/07/d/19',
            $this->routeCollector->pathFor('foo', ['year' => '2015', 'month' => '07', 'day' => '19'])
        );
    }

    public function testPathForWithQueryParameters()
    {
        $methods = ['GET'];
        $pattern = '/hello/{name}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s', $args['name']);
        };
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->assertEquals(
            '/hello/josh?a=b&c=d',
            $this->routeCollector->pathFor('foo', ['name' => 'josh'], ['a' => 'b', 'c' => 'd'])
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
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->routeCollector->pathFor('foo', ['last' => 'lockhart']);
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
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');

        $this->routeCollector->pathFor('bar', ['first' => 'josh', 'last' => 'lockhart']);
    }

    public function testGetRouteInvocationStrategy()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $invocationStrategy = new InvocationStrategyTest();
        $routeCollector = new RouteCollector($responseFactory, $callableResolver, null, $invocationStrategy);

        $this->assertEquals($invocationStrategy, $routeCollector->getDefaultInvocationStrategy());
    }

    public function testGetCallableResolver()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $routeCollector = new RouteCollector($responseFactory, $callableResolver);

        $this->assertEquals($callableResolver, $routeCollector->getCallableResolver());
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

        $this->routeCollector->setBasePath('/base/path');

        $route1 = $this->routeCollector->map($methods, '/foo', $callable);
        $route1->setName('foo');

        $route2 = $this->routeCollector->map($methods, '/bar', $callable);
        $route2->setName('bar');

        $route3 = $this->routeCollector->map($methods, '/fizz', $callable);
        $route3->setName('fizz');

        $route4 = $this->routeCollector->map($methods, '/buzz', $callable);
        $route4->setName('buzz');

        $routeToRemove = $this->routeCollector->getNamedRoute('fizz');

        $routeCountBefore = count($this->routeCollector->getRoutes());
        $this->routeCollector->removeNamedRoute($routeToRemove->getName());
        $routeCountAfter = count($this->routeCollector->getRoutes());

        // Assert number of routes is now less by 1
        $this->assertEquals(
            ($routeCountBefore - 1),
            $routeCountAfter
        );

        // Simple test that the correct route was removed
        $this->assertEquals(
            $this->routeCollector->getNamedRoute('foo')->getName(),
            'foo'
        );

        $this->assertEquals(
            $this->routeCollector->getNamedRoute('bar')->getName(),
            'bar'
        );

        $this->assertEquals(
            $this->routeCollector->getNamedRoute('buzz')->getName(),
            'buzz'
        );

        // Exception thrown here, route no longer exists
        $this->routeCollector->getNamedRoute($routeToRemove->getName());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRouteRemovalNotExists()
    {
        $this->routeCollector->setBasePath('/base/path');
        $this->routeCollector->removeNamedRoute('non-existing-route-name');
    }

    public function testPathForWithModifiedRoutePattern()
    {
        $this->routeCollector->setBasePath('/base/path');

        $methods = ['GET'];
        $pattern = '/hello/{first:\w+}/{last}';
        $callable = function ($request, $response, $args) {
            echo sprintf('Hello %s %s', $args['voornaam'], $args['achternaam']);
        };

        /** @var Route $route */
        $route = $this->routeCollector->map($methods, $pattern, $callable);
        $route->setName('foo');
        $route->setPattern('/hallo/{voornaam:\w+}/{achternaam}');

        $this->assertEquals(
            '/hallo/josh/lockhart',
            $this->routeCollector->relativePathFor('foo', ['voornaam' => 'josh', 'achternaam' => 'lockhart'])
        );
    }

    /**
     * Test cache file exists but is not writable
     */
    public function testCacheFileExistsAndIsNotReadable()
    {
        $this->cacheFile = __DIR__ . '/non-readable.cache';
        file_put_contents($this->cacheFile, '<?php return []; ?>');

        $this->expectException(
            '\RuntimeException',
            sprintf('Route collector cache file `%s` is not readable', $this->cacheFile)
        );

        $this->routeCollector->setCacheFile($this->cacheFile);
    }
    /**
     * Test cache file does not exist and directory is not writable
     */
    public function testCacheFileDoesNotExistsAndDirectoryIsNotWritable()
    {
        $cacheFile = __DIR__ . '/non-writable-directory/router.cache';

        $this->expectException(
            '\RuntimeException',
            sprintf('Route collector cache file directory `%s` is not writable', dirname($cacheFile))
        );

        $this->routeCollector->setCacheFile($cacheFile);
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

        /** @var RouteCollector $routeCollector */
        $routeCollector = $this
            ->getMockBuilder(RouteCollector::class)
            ->setConstructorArgs([$this->getResponseFactory(), new CallableResolver()])
            ->setMethods(['pathFor'])
            ->getMock();
        $routeCollector->expects($this->once())->method('pathFor')->with($name, $data, $queryParams);
        $routeCollector->urlFor($name, $data, $queryParams);

        //check that our error was triggered
        $this->assertEquals($errorString, 'urlFor() is deprecated. Use pathFor() instead.');

        restore_error_handler();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLookupRouteThrowsExceptionIfRouteNotFound()
    {
        $this->routeCollector->lookupRoute("thisIsMissing");
    }
}
