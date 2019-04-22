<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Factory;

use Http\Factory\Guzzle\ResponseFactory as GuzzleResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use ReflectionProperty;
use Slim\Factory\AppFactory;
use Slim\Factory\Psr17\GuzzlePsr17Factory;
use Slim\Factory\Psr17\NyholmPsr17Factory;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Factory\Psr17\ZendDiactorosPsr17Factory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Psr7\Factory\ResponseFactory as SlimResponseFactory;
use Slim\Routing\RouteCollector;
use Slim\Tests\TestCase;
use Zend\Diactoros\ResponseFactory as ZendDiactorosResponseFactory;

class AppFactoryTest extends TestCase
{
    public function provideImplementations()
    {
        return [
            [SlimPsr17Factory::class, SlimResponseFactory::class],
            [NyholmPsr17Factory::class, Psr17Factory::class],
            [GuzzlePsr17Factory::class, GuzzleResponseFactory::class],
            [ZendDiactorosPsr17Factory::class, ZendDiactorosResponseFactory::class],
        ];
    }

    /**
     * @dataProvider provideImplementations
     * @param string $psr17factory
     * @param string $expectedResponseFactoryClass
     */
    public function testCreateAppWithAllImplementations(string $psr17factory, string $expectedResponseFactoryClass)
    {
        $psr17FactoriesProperty = new ReflectionProperty(AppFactory::class, 'psr17Factories');
        $psr17FactoriesProperty->setAccessible(true);
        $psr17FactoriesProperty->setValue([$psr17factory]);

        $app = AppFactory::create();

        $routeCollector = $app->getRouteCollector();

        $responseFactoryProperty = new ReflectionProperty(RouteCollector::class, 'responseFactory');
        $responseFactoryProperty->setAccessible(true);

        $responseFactory = $responseFactoryProperty->getValue($routeCollector);

        $this->assertInstanceOf($expectedResponseFactoryClass, $responseFactory);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDetermineResponseFactoryThrowsRuntimeException()
    {
        $psr17FactoriesProperty = new ReflectionProperty(AppFactory::class, 'psr17Factories');
        $psr17FactoriesProperty->setAccessible(true);
        $psr17FactoriesProperty->setValue([]);

        AppFactory::create();
    }

    public function testAppIsCreatedWithInstancesFromSetters()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeResolverProphecy = $this->prophesize(RouteResolverInterface::class);

        AppFactory::setResponseFactory($responseFactoryProphecy->reveal());
        AppFactory::setContainer($containerProphecy->reveal());
        AppFactory::setCallableResolver($callableResolverProphecy->reveal());
        AppFactory::setRouteCollector($routeCollectorProphecy->reveal());
        AppFactory::setRouteResolver($routeResolverProphecy->reveal());

        $app = AppFactory::create();

        $this->assertSame(
            $responseFactoryProphecy->reveal(),
            $app->getResponseFactory()
        );

        $this->assertSame(
            $containerProphecy->reveal(),
            $app->getContainer()
        );

        $this->assertSame(
            $callableResolverProphecy->reveal(),
            $app->getCallableResolver()
        );

        $this->assertSame(
            $routeCollectorProphecy->reveal(),
            $app->getRouteCollector()
        );

        $this->assertSame(
            $routeResolverProphecy->reveal(),
            $app->getRouteResolver()
        );
    }

    public function testAppIsCreatedWithInjectedInstancesFromFunctionArguments()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeResolverProphecy = $this->prophesize(RouteResolverInterface::class);

        $app = AppFactory::create(
            $responseFactoryProphecy->reveal(),
            $containerProphecy->reveal(),
            $callableResolverProphecy->reveal(),
            $routeCollectorProphecy->reveal(),
            $routeResolverProphecy->reveal()
        );

        $this->assertSame(
            $responseFactoryProphecy->reveal(),
            $app->getResponseFactory()
        );

        $this->assertSame(
            $containerProphecy->reveal(),
            $app->getContainer()
        );

        $this->assertSame(
            $callableResolverProphecy->reveal(),
            $app->getCallableResolver()
        );

        $this->assertSame(
            $routeCollectorProphecy->reveal(),
            $app->getRouteCollector()
        );

        $this->assertSame(
            $routeResolverProphecy->reveal(),
            $app->getRouteResolver()
        );
    }
}
