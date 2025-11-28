<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container\Definition;

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use HttpSoft\Message\StreamFactory;
use HttpSoft\Message\UploadedFileFactory;
use HttpSoft\Message\UriFactory;
use HttpSoft\ServerRequest\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

final class HttpSoftDefinitions
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
                        return ServerRequestCreator::createFromGlobals();
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
