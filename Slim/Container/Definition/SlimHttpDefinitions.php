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
use RuntimeException;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Http\Factory\DecoratedUriFactory;
use Slim\Http\ServerRequest;
use Slim\Interfaces\DefinitionsInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

final class SlimHttpDefinitions implements DefinitionsInterface
{
    /**
     * Callable used to check whether a class exists.
     *
     * @var callable(string): bool
     */
    private $classExists = 'class_exists';

    public function getDefinitions(): array
    {
        $that = $this;

        return [
            ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
                $serverRequestFactory = $container->get(ServerRequestFactory::class);

                return new class ($serverRequestFactory) implements ServerRequestFactoryInterface {
                    private ServerRequestFactory $serverRequestFactory;

                    public function __construct(ServerRequestFactory $serverRequestFactory)
                    {
                        $this->serverRequestFactory = $serverRequestFactory;
                    }

                    /**
                     * @param array<string, mixed> $serverParams
                     * @param string $method
                     * @param mixed $uri
                     */
                    public function createServerRequest(
                        string $method,
                        $uri,
                        array $serverParams = [],
                    ): ServerRequestInterface {
                        return new ServerRequest(
                            $this->serverRequestFactory->createServerRequest($method, $uri, $serverParams),
                        );
                    }
                };
            },
            ServerRequestCreatorInterface::class => function () {
                return new class implements ServerRequestCreatorInterface {
                    public function createServerRequestFromGlobals(): ServerRequestInterface
                    {
                        return new ServerRequest(ServerRequestFactory::createFromGlobals());
                    }
                };
            },
            ResponseFactoryInterface::class => function (ContainerInterface $container) use ($that) {
                $responseFactory = null;

                $responseFactoryClasses = [
                    \Slim\Psr7\Factory\ResponseFactory::class,
                    \Nyholm\Psr7\Factory\Psr17Factory::class,
                    \Laminas\Diactoros\ResponseFactory::class,
                    \GuzzleHttp\Psr7\HttpFactory::class,
                    \HttpSoft\Message\ResponseFactory::class,
                ];

                foreach ($responseFactoryClasses as $responseFactoryClass) {
                    if (call_user_func($that->classExists, $responseFactoryClass)) {
                        $responseFactory = $container->get($responseFactoryClass);
                        break;
                    }
                }

                if ($responseFactory instanceof ResponseFactoryInterface) {
                    /* @var StreamFactoryInterface $streamFactory */
                    $streamFactory = $container->get(StreamFactoryInterface::class);
                    $responseFactory = new DecoratedResponseFactory($responseFactory, $streamFactory);
                }

                return $responseFactory ?? throw new RuntimeException(
                    'Could not detect any PSR-17 ResponseFactory implementations. ' .
                    'Please install a supported implementation. ' .
                    'See https://github.com/slimphp/Slim/blob/5.x/README.md for a list of supported implementations.',
                );
            },
            StreamFactoryInterface::class => function (ContainerInterface $container) use ($that) {
                $factoryClasses = [
                    \Slim\Psr7\Factory\StreamFactory::class,
                    \Nyholm\Psr7\Factory\Psr17Factory::class,
                    \Laminas\Diactoros\StreamFactory::class,
                    \GuzzleHttp\Psr7\HttpFactory::class,
                    \HttpSoft\Message\StreamFactory::class,
                ];

                foreach ($factoryClasses as $factoryClass) {
                    if (call_user_func($that->classExists, $factoryClass)) {
                        return $container->get($factoryClass);
                    }
                }

                throw new RuntimeException('Could not instantiate a StreamFactory.');
            },
            UriFactoryInterface::class => function (ContainerInterface $container) use ($that) {
                $uriFactory = null;

                $uriFactoryClasses = [
                    \Slim\Psr7\Factory\UriFactory::class,
                    \Nyholm\Psr7\Factory\Psr17Factory::class,
                    \Laminas\Diactoros\UriFactory::class,
                    \GuzzleHttp\Psr7\HttpFactory::class,
                    \HttpSoft\Message\UriFactory::class,
                ];

                foreach ($uriFactoryClasses as $uriFactoryClass) {
                    if (call_user_func($that->classExists, $uriFactoryClass)) {
                        $uriFactory = $container->get($uriFactoryClass);
                        break;
                    }
                }

                if ($uriFactory instanceof UriFactoryInterface) {
                    $uriFactory = new DecoratedUriFactory($uriFactory);
                }

                if ($uriFactory) {
                    return $uriFactory;
                }

                throw new RuntimeException('Could not instantiate a UriFactory.');
            },
            UploadedFileFactoryInterface::class => function (ContainerInterface $container) use ($that) {
                $factoryClasses = [
                    \Slim\Psr7\Factory\UploadedFileFactory::class,
                    \Nyholm\Psr7\Factory\Psr17Factory::class,
                    \Laminas\Diactoros\UploadedFileFactory::class,
                    \GuzzleHttp\Psr7\HttpFactory::class,
                    \HttpSoft\Message\UploadedFileFactory::class,
                ];

                foreach ($factoryClasses as $factoryClass) {
                    if (call_user_func($that->classExists, $factoryClass)) {
                        return $container->get($factoryClass);
                    }
                }

                throw new RuntimeException('Could not instantiate a UploadedFileFactory.');
            },
        ];
    }
}
