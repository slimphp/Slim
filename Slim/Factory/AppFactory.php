<?php

namespace Slim\Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Container\DiContainerFactory;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Routing\Router;

final class AppFactory
{
    public static function create(array $definitions = []): App
    {
        $containerBuilder = new DiContainerFactory();

        return $containerBuilder->createContainer($definitions)->get(App::class);
    }

    public static function createFromContainer(ContainerInterface $container): App
    {
        return new App(
            $container,
            $container->get(ServerRequestCreatorInterface::class),
            $container->get(RequestHandlerInterface::class),
            $container->get(Router::class),
            $container->get(EmitterInterface::class)
        );
    }
}
