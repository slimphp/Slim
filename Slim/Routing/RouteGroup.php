<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use FastRoute\RouteCollector;
use Slim\Interfaces\MiddlewareCollectionInterface;
use Slim\Interfaces\RouteCollectionInterface;
use Slim\Interfaces\RouteInterface;

final class RouteGroup implements MiddlewareCollectionInterface, RouteCollectionInterface
{
    use MiddlewareCollectionTrait;

    use RouteCollectionTrait;

    private string $prefix;

    /**
     * @var callable
     */
    private $callback;

    private ?RouteGroup $group;

    private RouteCollector $routeCollector;

    public function __construct(
        string $prefix,
        callable $callback,
        RouteCollector $routeCollector,
        ?RouteGroup $group = null,
    ) {
        $this->prefix = $prefix;
        $this->callback = $callback;
        $this->routeCollector = $routeCollector;
        $this->group = $group;
    }

    public function __invoke(): void
    {
        // This will be invoked by FastRoute to collect the route groups
        ($this->callback)($this);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Get parent route group.
     */
    public function getRouteGroup(): ?RouteGroup
    {
        return $this->group;
    }

    /**
     * @param array<string> $methods
     * @param string $path
     * @param callable|string $handler
     */
    public function map(array $methods, string $path, callable|string $handler): RouteInterface
    {
        $routePath = $this->prefix . $path;
        $route = new Route($methods, $routePath, $handler, $this);
        $this->routeCollector->addRoute($methods, $path, $route);

        return $route;
    }

    public function group(string $path, callable $handler): RouteGroup
    {
        $routePath = $this->prefix . $path;
        $routeGroup = new RouteGroup($routePath, $handler, $this->routeCollector, $this);

        $this->routeCollector->addGroup($path, $routeGroup);

        return $routeGroup;
    }
}
