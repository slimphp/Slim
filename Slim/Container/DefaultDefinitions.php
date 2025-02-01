<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Slim\Emitter\ResponseEmitter;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\RequestHandler\MiddlewareRequestHandler;
use Slim\Routing\Router;
use Slim\Routing\Strategies\RequestResponse;

/**
 * This class provides the default dependency definitions for a Slim application. It implements the
 * `__invoke()` method to return an array of service definitions that are used to set up the Slim
 * framework’s core components, including the application instance, middleware, request and response
 * factories, and other essential services.
 *
 * This class ensures that the Slim application can be properly instantiated with the necessary
 * components and services.
 */
final class DefaultDefinitions
{
    public function __invoke(): array
    {
        return [
            ContainerResolverInterface::class => function (ContainerInterface $container) {
                return $container->get(ContainerResolver::class);
            },

            EmitterInterface::class => function () {
                return new ResponseEmitter();
            },

            LoggerInterface::class => function () {
                return new NullLogger();
            },

            RequestHandlerInterface::class => function (ContainerInterface $container) {
                return $container->get(MiddlewareRequestHandler::class);
            },

            RequestHandlerInvocationStrategyInterface::class => function (ContainerInterface $container) {
                return $container->get(RequestResponse::class);
            },

            Router::class => function () {
                return new Router(new RouteCollector(new Std(), new GroupCountBased()));
            },
        ];
    }
}
