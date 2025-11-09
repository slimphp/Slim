<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Factory;

use GuzzleHttp\Psr7\HttpFactory;
use Laminas\Diactoros\ResponseFactory as LaminasDiactorosResponseFactory;
use HttpSoft\Message\ResponseFactory as HttpSoftResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use Slim\Factory\AppFactory;
use Slim\Factory\Psr17\GuzzlePsr17Factory;
use Slim\Factory\Psr17\HttpSoftPsr17Factory;
use Slim\Factory\Psr17\LaminasDiactorosPsr17Factory;
use Slim\Factory\Psr17\NyholmPsr17Factory;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Factory\Psr17\SlimHttpPsr17Factory;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Http\Response as DecoratedResponse;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Psr7\Factory\ResponseFactory as SlimResponseFactory;
use Slim\Routing\RouteCollector;
use Slim\Tests\Mocks\MockPsr17FactoryWithoutStreamFactory;
use Slim\Tests\TestCase;
use stdClass;

class AppFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflectionClass = new ReflectionClass(SlimHttpPsr17Factory::class);
        $reflectionClass->setStaticPropertyValue('responseFactoryClass', DecoratedResponseFactory::class);
    }

    public static function provideImplementations()
    {
        return [
            [SlimPsr17Factory::class, SlimResponseFactory::class],
            [HttpSoftPsr17Factory::class, HttpSoftResponseFactory::class],
            [NyholmPsr17Factory::class, Psr17Factory::class],
            [GuzzlePsr17Factory::class, HttpFactory::class],
            [LaminasDiactorosPsr17Factory::class, LaminasDiactorosResponseFactory::class],
        ];
    }

    /**
     * @dataProvider provideImplementations
     * @param string $psr17factory
     * @param string $expectedResponseFactoryClass
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideImplementations')]
    public function testCreateAppWithAllImplementations(string $psr17factory, string $expectedResponseFactoryClass)
    {
        Psr17FactoryProvider::setFactories([$psr17factory]);
        AppFactory::setSlimHttpDecoratorsAutomaticDetection(false);

        $app = AppFactory::create();

        $routeCollector = $app->getRouteCollector();

        $responseFactoryProperty = new ReflectionProperty(RouteCollector::class, 'responseFactory');
        $this->setAccessible($responseFactoryProperty);

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

    public function testDetermineResponseFactoryThrowsRuntimeExceptionIfDecoratedNotInstanceOfResponseInterface()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Slim\\Factory\\Psr17\\SlimHttpPsr17Factory could not instantiate a decorated response factory.'
        );

        $reflectionClass = new ReflectionClass(SlimHttpPsr17Factory::class);
        $reflectionClass->setStaticPropertyValue('responseFactoryClass', SlimHttpPsr17Factory::class);

        Psr17FactoryProvider::setFactories([SlimPsr17Factory::class]);
        AppFactory::setSlimHttpDecoratorsAutomaticDetection(true);

        AppFactory::create();
    }

    /**
     * @runInSeparateProcess - Psr17FactoryProvider::setFactories breaks other tests
     */
    public function testDetermineResponseFactoryThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);

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

    /**
     * @runInSeparateProcess - Psr17FactoryProvider::setFactories breaks other tests
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testResponseFactoryIsStillReturnedIfStreamFactoryIsNotAvailable()
    {
        Psr17FactoryProvider::setFactories([MockPsr17FactoryWithoutStreamFactory::class]);
        AppFactory::setSlimHttpDecoratorsAutomaticDetection(true);

        $app = AppFactory::create();

        $this->assertInstanceOf(SlimResponseFactory::class, $app->getResponseFactory());
    }

    /**
     * @runInSeparateProcess - AppFactory::setResponseFactory breaks other tests
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAppIsCreatedWithInstancesFromSetters()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);
        $routeResolverProphecy = $this->prophesize(RouteResolverInterface::class);
        $middlewareDispatcherProphecy = $this->prophesize(MiddlewareDispatcherInterface::class);

        $routeCollectorProphecy->getRouteParser()->willReturn($routeParserProphecy);

        AppFactory::setSlimHttpDecoratorsAutomaticDetection(false);
        AppFactory::setResponseFactory($responseFactoryProphecy->reveal());
        AppFactory::setContainer($containerProphecy->reveal());
        AppFactory::setCallableResolver($callableResolverProphecy->reveal());
        AppFactory::setRouteCollector($routeCollectorProphecy->reveal());
        AppFactory::setRouteResolver($routeResolverProphecy->reveal());
        AppFactory::setMiddlewareDispatcher($middlewareDispatcherProphecy->reveal());

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

        $this->assertSame(
            $middlewareDispatcherProphecy->reveal(),
            $app->getMiddlewareDispatcher()
        );
    }

    /**
     * @runInSeparateProcess - AppFactory::create saves $responseFactory into static::$responseFactory,
     *                         this breaks other tests
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
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

    /**
     * @runInSeparateProcess - AppFactory::setResponseFactory breaks other tests
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
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
        $this->setAccessible($streamFactoryProperty);

        $this->assertSame($streamFactoryProphecy->reveal(), $streamFactoryProperty->getValue($response));
    }

    public function testCreateAppWithContainerUsesContainerDependenciesWhenPresent()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);
        $routeResolverProphecy = $this->prophesize(RouteResolverInterface::class);
        $routeParserProphecy = $this->prophesize(RouteParserInterface::class);

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeCollectorProphecy
            ->getRouteParser()
            ->willReturn($routeParserProphecy->reveal())
            ->shouldBeCalledOnce();

        $middlewareDispatcherProphecy = $this->prophesize(MiddlewareDispatcherInterface::class);

        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy
            ->has(ResponseFactoryInterface::class)
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $containerProphecy
            ->get(ResponseFactoryInterface::class)
            ->willReturn($responseFactoryProphecy->reveal())
            ->shouldBeCalledOnce();

        $containerProphecy
            ->has(CallableResolverInterface::class)
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $containerProphecy
            ->get(CallableResolverInterface::class)
            ->willReturn($callableResolverProphecy->reveal())
            ->shouldBeCalledOnce();

        $containerProphecy
            ->has(RouteCollectorInterface::class)
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $containerProphecy
            ->get(RouteCollectorInterface::class)
            ->willReturn($routeCollectorProphecy->reveal())
            ->shouldBeCalledOnce();

        $containerProphecy
            ->has(RouteResolverInterface::class)
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $containerProphecy
            ->get(RouteResolverInterface::class)
            ->willReturn($routeResolverProphecy->reveal())
            ->shouldBeCalledOnce();

        $containerProphecy
            ->has(MiddlewareDispatcherInterface::class)
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $containerProphecy
            ->get(MiddlewareDispatcherInterface::class)
            ->willReturn($middlewareDispatcherProphecy->reveal())
            ->shouldBeCalledOnce();

        AppFactory::setSlimHttpDecoratorsAutomaticDetection(false);
        $app = AppFactory::createFromContainer($containerProphecy->reveal());

        $this->assertSame($app->getResponseFactory(), $responseFactoryProphecy->reveal());
        $this->assertSame($app->getContainer(), $containerProphecy->reveal());
        $this->assertSame($app->getCallableResolver(), $callableResolverProphecy->reveal());
        $this->assertSame($app->getRouteCollector(), $routeCollectorProphecy->reveal());
        $this->assertSame($app->getRouteResolver(), $routeResolverProphecy->reveal());
        $this->assertSame($app->getMiddlewareDispatcher(), $middlewareDispatcherProphecy->reveal());
    }

    public function testCreateAppWithEmptyContainer()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);

        $containerProphecy
            ->has(ResponseFactoryInterface::class)
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $containerProphecy
            ->has(CallableResolverInterface::class)
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $containerProphecy
            ->has(RouteCollectorInterface::class)
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $containerProphecy
            ->has(RouteResolverInterface::class)
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $containerProphecy
            ->has(MiddlewareDispatcherInterface::class)
            ->willReturn(false)
            ->shouldBeCalledOnce();

        AppFactory::setSlimHttpDecoratorsAutomaticDetection(false);
        AppFactory::createFromContainer($containerProphecy->reveal());
    }
}
