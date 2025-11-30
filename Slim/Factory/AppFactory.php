<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

namespace Slim\Factory;

use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Container\DiContainerFactory;
use Slim\Interfaces\ContainerFactoryInterface;

final class AppFactory
{
    /**
     * The container factory used to create Slim's DI container.
     *
     * If null, DiContainerFactory will be used as a default.
     *
     * @var ContainerFactoryInterface|null
     */
    private static ?ContainerFactoryInterface $containerFactory = null;

    /**
     * Set a custom container factory to build the application container.
     *
     * @param ContainerFactoryInterface $containerFactory
     *
     * @return void
     */
    public static function setContainerFactory(ContainerFactoryInterface $containerFactory): void
    {
        static::$containerFactory = $containerFactory;
    }

    /**
     * Create a new Slim application instance.
     *
     * @param array<string, mixed> $definitions Optional container definitions.
     *
     * @return App
     */
    public static function create(array $definitions = []): App
    {
        $containerBuilder = static::$containerFactory ?? new DiContainerFactory();

        return $containerBuilder->createContainer($definitions)->get(App::class);
    }

    /**
     * Create an application using an existing container instance.
     *
     * @param ContainerInterface $container
     *
     * @return App
     */
    public static function createFromContainer(ContainerInterface $container): App
    {
        return new App($container);
    }
}
