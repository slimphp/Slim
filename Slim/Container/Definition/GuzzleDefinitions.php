<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container\Definition;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

final class GuzzleDefinitions
{
    public function getDefinitions(): array
    {
        return [
            ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(HttpFactory::class);
            },
            ServerRequestCreatorInterface::class => function () {
                return new class implements ServerRequestCreatorInterface {
                    public function createServerRequestFromGlobals(): ServerRequestInterface
                    {
                        return ServerRequest::fromGlobals();
                    }
                };
            },
            ResponseFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(HttpFactory::class);
            },
            StreamFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(HttpFactory::class);
            },
            UriFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(HttpFactory::class);
            },
            UploadedFileFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(HttpFactory::class);
            },
        ];
    }
}
