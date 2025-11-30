<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container\Definition;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Slim\Container\ContainerResolver;
use Slim\Emitter\ResponseEmitter;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Interfaces\DefinitionsInterface;
use Slim\Interfaces\EmitterInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Interfaces\UrlGeneratorInterface;
use Slim\Routing\Router;
use Slim\Routing\UrlGenerator;
use Slim\Strategy\RequestResponse;

/**
 * Provides service definitions for the Slim core components.
 *
 * The returned definitions include services for routing, dispatching,
 * resolving handlers, emitting responses, generating URLs.
 *
 * These defaults allow a Slim application to be instantiated with all
 * essential framework services ready for use.
 */
final class SlimDefinitions implements DefinitionsInterface
{
    public function getDefinitions(): array
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

            RequestHandlerInvocationStrategyInterface::class => function (ContainerInterface $container) {
                return $container->get(RequestResponse::class);
            },

            RequestHandlerInterface::class => function (ContainerInterface $container) {
                return $container->get(Router::class);
            },

            RouterInterface::class => function (ContainerInterface $container) {
                return $container->get(Router::class);
            },

            UrlGeneratorInterface::class => function (ContainerInterface $container) {
                return $container->get(UrlGenerator::class);
            },
        ];
    }
}
