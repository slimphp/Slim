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
use RuntimeException;
use Slim\App;
use Slim\Factory\Psr17\GuzzlePsr17Factory;
use Slim\Factory\Psr17\NyholmPsr17Factory;
use Slim\Factory\Psr17\Psr17Factory;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Factory\Psr17\ZendDiactorosPsr17Factory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;

class AppFactory
{
    /**
     * @var array
     */
    protected static $implementations = [
        SlimPsr17Factory::class,
        NyholmPsr17Factory::class,
        ZendDiactorosPsr17Factory::class,
        GuzzlePsr17Factory::class,
    ];

    /**
     * @param ResponseFactoryInterface|null  $responseFactory
     * @param ContainerInterface|null        $container
     * @param CallableResolverInterface|null $callableResolver
     * @param RouteCollectorInterface|null   $routeCollector
     * @param RouteResolverInterface|null    $routeResolver
     * @return App
     */
    public static function create(
        ResponseFactoryInterface $responseFactory = null,
        ContainerInterface $container = null,
        CallableResolverInterface $callableResolver = null,
        RouteCollectorInterface $routeCollector = null,
        RouteResolverInterface $routeResolver = null
    ): App {
        $responseFactory = $responseFactory ?? self::determineResponseFactory();
        return new App(
            $responseFactory,
            $container,
            $callableResolver,
            $routeCollector,
            $routeResolver
        );
    }

    /**
     * @return ResponseFactoryInterface
     * @throws RuntimeException
     */
    public static function determineResponseFactory(): ResponseFactoryInterface
    {
        /** @var Psr17Factory $implementation */
        foreach (self::$implementations as $implementation) {
            if ($implementation::isResponseFactoryAvailable()) {
                return $implementation::getResponseFactory();
            }
        }

        throw new RuntimeException('Could not detect any PSR-17 ResponseFactory implementations.');
    }
}
