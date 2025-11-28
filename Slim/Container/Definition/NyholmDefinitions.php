<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container\Definition;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;

final class NyholmDefinitions
{
    public function getDefinitions(): array
    {
        return [
            ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(Psr17Factory::class);
            },
            ServerRequestCreatorInterface::class => function (ContainerInterface $container) {
                $serverRequestCreator = $container->get(ServerRequestCreator::class);

                return new class ($serverRequestCreator) implements ServerRequestCreatorInterface {
                    private ServerRequestCreator $serverRequestCreator;

                    public function __construct(ServerRequestCreator $serverRequestCreator)
                    {
                        $this->serverRequestCreator = $serverRequestCreator;
                    }

                    public function createServerRequestFromGlobals(): ServerRequestInterface
                    {
                        return $this->serverRequestCreator->fromGlobals();
                    }
                };
            },
            ResponseFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(Psr17Factory::class);
            },
            StreamFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(Psr17Factory::class);
            },
            UriFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(Psr17Factory::class);
            },
            UploadedFileFactoryInterface::class => function (ContainerInterface $container) {
                return $container->get(Psr17Factory::class);
            },
        ];
    }
}
