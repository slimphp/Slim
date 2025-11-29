<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

namespace Slim\Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Container\DiContainerFactory;
use Slim\Interfaces\ContainerFactoryInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

final class AppFactory
{
    private static ?ContainerFactoryInterface $containerFactory = null;

    public static function setContainerFactory(ContainerFactoryInterface $containerFactory): void
    {
        static::$containerFactory = $containerFactory;
    }

    public static function create(array $definitions = []): App
    {
        $containerBuilder = static::$containerFactory ?? new DiContainerFactory();

        return $containerBuilder->createContainer($definitions)->get(App::class);
    }

    public static function createFromContainer(ContainerInterface $container): App
    {
        return new App($container);
    }
}
