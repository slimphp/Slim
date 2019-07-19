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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use ReflectionProperty;
use RuntimeException;
use Slim\Factory\AppFactory;
use Slim\Factory\Psr17\GuzzlePsr17Factory;
use Slim\Factory\Psr17\NyholmPsr17Factory;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Factory\Psr17\ZendDiactorosPsr17Factory;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Http\Response as DecoratedResponse;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Psr7\Factory\ResponseFactory as SlimResponseFactory;
use Slim\Routing\RouteCollector;
use Slim\Tests\Mocks\MockPsr17FactoryWithoutStreamFactory;
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
        Psr17FactoryProvider::setFactories([$psr17factory]);
        AppFactory::setSlimHttpDecoratorsAutomaticDetection(false);

        $app = AppFactory::create();

        $routeCollector = $app->getRouteCollector();

        $responseFactoryProperty = new ReflectionProperty(RouteCollector::class, 'responseFactory');
        $responseFactoryProperty->setAccessible(true);

        $responseFactory = $responseFactoryProperty->getValue($routeCollector);

        $this->assertInstanceOf($expectedResponseFactoryClass, $responseFactory);
    }

    public function testDetermineResponseFactoryReturnsDecoratedFactory()
    {
        Psr17FactoryProvider::setFactories([SlimPsr17Factory::class]);
        AppFactory::setSlimHttpDecoratorsAutomaticDetection(true);

        $app = AppFactory::create();

        $this->assertInstanceOf(DecoratedResponseFactory::class, $app->getResponseFactory());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testDetermineResponseFactoryThrowsRuntimeException()
    {
        Psr17FactoryProvider::setFactories([]);
        AppFactory::create();
    }

    public function testSetPsr17FactoryProvider()
    {
        $psr17FactoryProvider = new Psr17FactoryProvider();
        $psr17FactoryProvider::setFactories([SlimPsr17Factory::class]);

        AppFactory::setPsr17FactoryProvider($psr17FactoryProvider);
        AppFactory::setSlimHttpDecoratorsAutomaticDetection(false);

        $this->assertInstanceOf(SlimResponseFactory::class, AppFactory::determineResponseFactory());
    }

    public function testResponseFactoryIsStillReturnedIfStreamFactoryIsNotAvailable()
    {
        Psr17FactoryProvider::setFactories([MockPsr17FactoryWithoutStreamFactory::class]);
        AppFactory::setSlimHttpDecoratorsAutomaticDetection(true);

        $app = AppFactory::create();

        $this->assertInstanceOf(SlimResponseFactory::class, $app->getResponseFactory());
    }

    public function testAppIsCreatedWithInstancesFromSetters()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);
        $routeResolverProphecy = $this->prophesize(RouteResolverInterface::class);

        $routeCollectorProphecy->getRouteParser()->willReturn($routeParserProphecy);

        AppFactory::setSlimHttpDecoratorsAutomaticDetection(false);
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
        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);
        $routeResolverProphecy = $this->prophesize(RouteResolverInterface::class);

        $routeCollectorProphecy->getRouteParser()->willReturn($routeParserProphecy->reveal());

        AppFactory::setSlimHttpDecoratorsAutomaticDetection(false);

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

    public function testResponseAndStreamFactoryIsBeingInjectedInDecoratedResponseFactory()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactoryProphecy
            ->createResponse(200, '')
            ->willReturn($responseProphecy->reveal())
            ->shouldBeCalledOnce();

        $streamFactoryProphecy = $this->prophesize(StreamFactoryInterface::class);

        AppFactory::setResponseFactory($responseFactoryProphecy->reveal());
        AppFactory::setStreamFactory($streamFactoryProphecy->reveal());
        AppFactory::setSlimHttpDecoratorsAutomaticDetection(true);

        $app = AppFactory::create();

        $responseFactory = $app->getResponseFactory();
        $response = $responseFactory->createResponse();

        $streamFactoryProperty = new ReflectionProperty(DecoratedResponse::class, 'streamFactory');
        $streamFactoryProperty->setAccessible(true);

        $this->assertSame($streamFactoryProphecy->reveal(), $streamFactoryProperty->getValue($response));
    }
}
