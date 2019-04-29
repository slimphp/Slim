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
use Slim\CallableResolver;
use Slim\Factory\Psr17\GuzzlePsr17Factory;
use Slim\Factory\Psr17\NyholmPsr17Factory;
use Slim\Factory\Psr17\Psr17Factory;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Factory\Psr17\ZendDiactorosPsr17Factory;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteResolver;

final class ServiceProvider
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string[]
     */
    private $services = [];

    public function __construct()
    {
        $this->services = [
            ResponseFactoryInterface::class => [$this, 'getResponseFactory'],
            CallableResolverInterface::class => [$this, 'getCallableResolver'],
            RouteCollectorInterface::class => [$this, 'getRouteCollector'],
            RouteResolverInterface::class => [$this, 'getRouteResolver'],
        ];
    }

    public function getServices()
    {
        return $this->services;
    }

    /**
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory()
    {
        $psr17Factories = [
            SlimPsr17Factory::class,
            NyholmPsr17Factory::class,
            ZendDiactorosPsr17Factory::class,
            GuzzlePsr17Factory::class,
        ];
        /** @var Psr17Factory $psr17factory */
        foreach ($psr17Factories as $psr17factory) {
            if ($psr17factory::isResponseFactoryAvailable()) {
                return $psr17factory::getResponseFactory();
            }
        }

        throw new RuntimeException(
            "Could not detect any PSR-17 ResponseFactory implementations. " .
            "Please install a supported implementation in order to use `AppFactory::create()`. " .
            "See https://github.com/slimphp/Slim/blob/4.x/README.md for a list of supported implementations."
        );
    }

    /**
     * @param ContainerInterface $container
     * @return CallableResolver
     */
    public function getCallableResolver(ContainerInterface $container)
    {
        return new CallableResolver($container);
    }

    /**
     * @param ContainerInterface $container
     * @return RouteCollector
     */
    public function getRouteCollector(ContainerInterface $container)
    {
        return new RouteCollector(
            $container->get(ResponseFactoryInterface::class),
            $container->get(CallableResolverInterface::class),
            $this->container
        );
    }

    /**
     * @param ContainerInterface $container
     * @return RouteResolver
     */
    public function getRouteResolver(ContainerInterface $container)
    {
        return new RouteResolver($container->get(RouteCollectorInterface::class));
    }
}
