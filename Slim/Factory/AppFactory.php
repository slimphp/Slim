<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Slim\App;
use Slim\Factory\Psr17\Psr17Factory;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Factory\Psr17\SlimHttpPsr17Factory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\MiddlewareDispatcherInterface;
use Slim\Interfaces\Psr17FactoryProviderInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;

class AppFactory
{
    /**
     * @var Psr17FactoryProviderInterface|null
     */
    protected static $psr17FactoryProvider;

    /**
     * @var ResponseFactoryInterface|null
     */
    protected static $responseFactory;

    /**
     * @var StreamFactoryInterface|null
     */
    protected static $streamFactory;

    /**
     * @var ContainerInterface|null
     */
    protected static $container;

    /**
     * @var CallableResolverInterface|null
     */
    protected static $callableResolver;

    /**
     * @var RouteCollectorInterface|null
     */
    protected static $routeCollector;

    /**
     * @var RouteResolverInterface|null
     */
    protected static $routeResolver;

    /**
     * @var MiddlewareDispatcherInterface|null
     */
    protected static $middlewareDispatcher;

    /**
     * @var bool
     */
    protected static $slimHttpDecoratorsAutomaticDetectionEnabled = true;

    /**
     * @param ResponseFactoryInterface|null         $responseFactory
     * @param ContainerInterface|null               $container
     * @param CallableResolverInterface|null        $callableResolver
     * @param RouteCollectorInterface|null          $routeCollector
     * @param RouteResolverInterface|null           $routeResolver
     * @param MiddlewareDispatcherInterface|null    $middlewareDispatcher
     * @return App
     */
    public static function create(
        ?ResponseFactoryInterface $responseFactory = null,
        ?ContainerInterface $container = null,
        ?CallableResolverInterface $callableResolver = null,
        ?RouteCollectorInterface $routeCollector = null,
        ?RouteResolverInterface $routeResolver = null,
        ?MiddlewareDispatcherInterface $middlewareDispatcher = null
    ): App {
        static::$responseFactory = $responseFactory ?? static::$responseFactory;
        return new App(
            self::determineResponseFactory(),
            $container ?? static::$container,
            $callableResolver ?? static::$callableResolver,
            $routeCollector ?? static::$routeCollector,
            $routeResolver ?? static::$routeResolver,
            $middlewareDispatcher ?? static::$middlewareDispatcher
        );
    }

    /**
     * @param ContainerInterface $container
     * @return App
     */
    public static function createFromContainer(ContainerInterface $container): App
    {
        $responseFactory = $container->has(ResponseFactoryInterface::class)
        && (
            $responseFactoryFromContainer = $container->get(ResponseFactoryInterface::class)
        ) instanceof ResponseFactoryInterface
            ? $responseFactoryFromContainer
            : self::determineResponseFactory();

        $callableResolver = $container->has(CallableResolverInterface::class)
        && (
            $callableResolverFromContainer = $container->get(CallableResolverInterface::class)
        ) instanceof CallableResolverInterface
            ? $callableResolverFromContainer
            : null;

        $routeCollector = $container->has(RouteCollectorInterface::class)
        && (
            $routeCollectorFromContainer = $container->get(RouteCollectorInterface::class)
        ) instanceof RouteCollectorInterface
            ? $routeCollectorFromContainer
            : null;

        $routeResolver = $container->has(RouteResolverInterface::class)
        && (
            $routeResolverFromContainer = $container->get(RouteResolverInterface::class)
        ) instanceof RouteResolverInterface
            ? $routeResolverFromContainer
            : null;

        $middlewareDispatcher = $container->has(MiddlewareDispatcherInterface::class)
        && (
            $middlewareDispatcherFromContainer = $container->get(MiddlewareDispatcherInterface::class)
        ) instanceof MiddlewareDispatcherInterface
            ? $middlewareDispatcherFromContainer
            : null;

        return new App(
            $responseFactory,
            $container,
            $callableResolver,
            $routeCollector,
            $routeResolver,
            $middlewareDispatcher
        );
    }

    /**
     * @return ResponseFactoryInterface
     * @throws RuntimeException
     */
    public static function determineResponseFactory(): ResponseFactoryInterface
    {
        if (static::$responseFactory) {
            if (static::$streamFactory) {
                return static::attemptResponseFactoryDecoration(static::$responseFactory, static::$streamFactory);
            }
            return static::$responseFactory;
        }

        $psr17FactoryProvider = static::$psr17FactoryProvider ?? new Psr17FactoryProvider();

        /** @var Psr17Factory $psr17factory */
        foreach ($psr17FactoryProvider->getFactories() as $psr17factory) {
            if ($psr17factory::isResponseFactoryAvailable()) {
                $responseFactory = $psr17factory::getResponseFactory();

                if (static::$streamFactory || $psr17factory::isStreamFactoryAvailable()) {
                    $streamFactory = static::$streamFactory ?? $psr17factory::getStreamFactory();
                    return static::attemptResponseFactoryDecoration($responseFactory, $streamFactory);
                }

                return $responseFactory;
            }
        }

        throw new RuntimeException(
            "Could not detect any PSR-17 ResponseFactory implementations. " .
            "Please install a supported implementation in order to use `AppFactory::create()`. " .
            "See https://github.com/slimphp/Slim/blob/4.x/README.md for a list of supported implementations."
        );
    }

    /**
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface   $streamFactory
     * @return ResponseFactoryInterface
     */
    protected static function attemptResponseFactoryDecoration(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ): ResponseFactoryInterface {
        if (
            static::$slimHttpDecoratorsAutomaticDetectionEnabled
            && SlimHttpPsr17Factory::isResponseFactoryAvailable()
        ) {
            return SlimHttpPsr17Factory::createDecoratedResponseFactory($responseFactory, $streamFactory);
        }

        return $responseFactory;
    }

    /**
     * @param Psr17FactoryProviderInterface $psr17FactoryProvider
     */
    public static function setPsr17FactoryProvider(Psr17FactoryProviderInterface $psr17FactoryProvider): void
    {
        static::$psr17FactoryProvider = $psr17FactoryProvider;
    }

    /**
     * @param ResponseFactoryInterface $responseFactory
     */
    public static function setResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        static::$responseFactory = $responseFactory;
    }

    /**
     * @param StreamFactoryInterface $streamFactory
     */
    public static function setStreamFactory(StreamFactoryInterface $streamFactory): void
    {
        static::$streamFactory = $streamFactory;
    }

    /**
     * @param ContainerInterface $container
     */
    public static function setContainer(ContainerInterface $container): void
    {
        static::$container = $container;
    }

    /**
     * @param CallableResolverInterface $callableResolver
     */
    public static function setCallableResolver(CallableResolverInterface $callableResolver): void
    {
        static::$callableResolver = $callableResolver;
    }

    /**
     * @param RouteCollectorInterface $routeCollector
     */
    public static function setRouteCollector(RouteCollectorInterface $routeCollector): void
    {
        static::$routeCollector = $routeCollector;
    }

    /**
     * @param RouteResolverInterface $routeResolver
     */
    public static function setRouteResolver(RouteResolverInterface $routeResolver): void
    {
        static::$routeResolver = $routeResolver;
    }

    /**
     * @param MiddlewareDispatcherInterface $middlewareDispatcher
     */
    public static function setMiddlewareDispatcher(MiddlewareDispatcherInterface $middlewareDispatcher): void
    {
        static::$middlewareDispatcher = $middlewareDispatcher;
    }

    /**
     * @param bool $enabled
     */
    public static function setSlimHttpDecoratorsAutomaticDetection(bool $enabled): void
    {
        static::$slimHttpDecoratorsAutomaticDetectionEnabled = $enabled;
    }
}
