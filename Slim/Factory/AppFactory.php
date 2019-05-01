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
use Slim\App;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;

final class AppFactory
{
    /**
     * @var ServiceContainer
     */
    private $provider;

    public function __construct(ServiceContainer $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param ContainerInterface|null $container
     * @return App
     */
    public static function create(ContainerInterface $container = null)
    {
        $provider = new ServiceProvider();
        $buildContainer = new ServiceContainer($provider, $container);
        $builder = new self($buildContainer);
        return $builder->getApp();
    }

    /**
     * @return App
     */
    public function getApp()
    {
        return new App(
            $this->provider->get(ResponseFactoryInterface::class),
            $this->provider->getContainer(),
            $this->provider->get(CallableResolverInterface::class),
            $this->provider->get(RouteCollectorInterface::class),
            $this->provider->get(RouteResolverInterface::class)
        );
    }
}
