<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container\Definition;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Slim\Psr7\Factory\UriFactory;

final class SlimPsr7Definitions
{
    public function getDefinitions(): array
    {
        return [
            ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(ServerRequestFactory::class);
            },
            ServerRequestCreatorInterface::class => function () {
                return new class implements ServerRequestCreatorInterface {
                    public function createServerRequestFromGlobals(): ServerRequestInterface
                    {
                        return ServerRequestFactory::createFromGlobals();
                    }
                };
            },
            ResponseFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(ResponseFactory::class);
            },
            StreamFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(StreamFactory::class);
            },
            UriFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(UriFactory::class);
            },
            UploadedFileFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(UploadedFileFactory::class);
            },
        ];
    }
}
