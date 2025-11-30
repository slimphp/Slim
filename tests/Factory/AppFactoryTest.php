<?php

declare(strict_types=1);

namespace Slim\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Container\DiContainerFactory;
use Slim\Factory\AppFactory;
use Slim\Interfaces\ContainerFactoryInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

final class AppFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset to default
        AppFactory::setContainerFactory(new DiContainerFactory());
    }

    public function testCreateUsesDefaultDiContainerFactory(): void
    {
        AppFactory::setContainerFactory(new DiContainerFactory());
        $this->assertInstanceOf(App::class, AppFactory::create());
    }

    public function testSetContainerFactoryOverridesDefault(): void
    {
        $customFactory = $this->createMock(ContainerFactoryInterface::class);

        $mockApp = $this->createMock(App::class);
        $mockContainer = $this->createMock(ContainerInterface::class);
        $mockContainer->method('get')->with(App::class)->willReturn($mockApp);
        $customFactory->method('createContainer')->willReturn($mockContainer);

        AppFactory::setContainerFactory($customFactory);

        $result = AppFactory::create();

        $this->assertSame($mockApp, $result);
    }

    public function testCreateFromContainerBuildsAppWithProvidedServices(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $appMock = $this->createMock(App::class);
        $serverRequestCreator = $this->createMock(ServerRequestCreatorInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $emitter = $this->createMock(EmitterInterface::class);

        // container->get(...) returns expected dependencies
        $container->method('get')->willReturnCallback(function (string $id) use (
            $serverRequestCreator,
            $handler,
            $router,
            $emitter,
            $appMock
        ) {
            return match ($id) {
                ServerRequestCreatorInterface::class => $serverRequestCreator,
                RequestHandlerInterface::class => $handler,
                RouterInterface::class => $router,
                EmitterInterface::class => $emitter,
                App::class => $appMock,
                default => null,
            };
        });

        $app = AppFactory::createFromContainer($container);

        $this->assertInstanceOf(App::class, $app);
    }
}
