<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use RuntimeException;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteCollectorProxy;
use Slim\Tests\TestCase;

class RouteCollectorTest extends TestCase
{
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

    public function testGetSetBasePath()
    {
        $basePath = '/app';

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeCollector->setBasePath($basePath);

        $this->assertEquals($basePath, $routeCollector->getBasePath());
    }

    public function testMap()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $route = $routeCollector->map(['GET'], '/', function () {
        });

        $routes = $routeCollector->getRoutes();
        $this->assertSame($route, $routes[$route->getIdentifier()]);
    }

    public function testMapPrependsGroupPattern()
    {
        $self = $this;

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);

        $callable = function (RouteCollectorProxy $proxy) use ($self) {
            $route = $proxy->get('/test', function () {
            });

            $self->assertEquals('/prefix/test', $route->getPattern());
        };

        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $callableResolverProphecy
            ->resolve($callable)
            ->willReturn($callable)
            ->shouldBeCalledOnce();

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeCollector->group('/prefix', $callable);
    }

    public function testGetRouteInvocationStrategy()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $invocationStrategyProphecy = $this->prophesize(InvocationStrategyInterface::class);

        $routeCollector = new RouteCollector(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            $containerProphecy->reveal(),
            $invocationStrategyProphecy->reveal()
        );

        $this->assertSame($invocationStrategyProphecy->reveal(), $routeCollector->getDefaultInvocationStrategy());
    }

    public function testRemoveNamedRoute()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeCollector->setBasePath('/base/path');

        $route = $routeCollector->map(['GET'], '/test', function () {
        });
        $route->setName('test');

        $routes = $routeCollector->getRoutes();
        $this->assertCount(1, $routes);

        $routeCollector->removeNamedRoute('test');
        $routes = $routeCollector->getRoutes();
        $this->assertCount(0, $routes);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRemoveNamedRouteWithARouteThatDoesNotExist()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeCollector->removeNamedRoute('missing');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLookupRouteThrowsExceptionIfRouteNotFound()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeCollector->lookupRoute('missing');
    }

    /**
     * Test cache file exists but is not writable
     */
    public function testCacheFileExistsAndIsNotReadable()
    {
        $this->cacheFile = __DIR__ . '/non-readable.cache';
        file_put_contents($this->cacheFile, '<?php return []; ?>');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Route collector cache file `%s` is not readable', $this->cacheFile));

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeCollector->setCacheFile($this->cacheFile);
    }

    /**
     * Test cache file does not exist and directory is not writable
     */
    public function testCacheFileDoesNotExistsAndDirectoryIsNotWritable()
    {
        $cacheFile = __DIR__ . '/non-writable-directory/router.cache';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Route collector cache file directory `%s` is not writable',
            dirname($cacheFile)
        ));

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeCollector->setCacheFile($cacheFile);
    }

    /**
     * Test cacheFile via construct is set
     */
    public function testSetCacheFileViaConstructor()
    {
        $cacheFile = __DIR__ . '/router.cache';

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector(
            $responseFactoryProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            null,
            null,
            null,
            $cacheFile
        );
        $this->assertEquals($cacheFile, $routeCollector->getCacheFile());
    }
}
