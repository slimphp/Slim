<?php

namespace Slim\Routing;

use FastRoute\RouteCollector;
use InvalidArgumentException;

final class Router
{
    use RouteCollectionTrait;

    use MiddlewareAwareTrait;

    private RouteCollector $collector;

    private string $basePath = '';

    public function __construct(RouteCollector $collector)
    {
        $this->collector = $collector;
    }

    /**
     * @param array<string> $methods
     *
     * @throws InvalidArgumentException
     */
    public function map(array $methods, string $path, callable|string $handler): Route
    {
        if (!$methods) {
            throw new InvalidArgumentException('HTTP methods array cannot be empty');
        }

        $routePattern = $this->normalizePath($path);
        $route = new Route($methods, $routePattern, $handler, null);

        $this->collector->addRoute($methods, $routePattern, $route);

        return $route;
    }

    public function group(string $path, callable $handler): RouteGroup
    {
        $routePattern = $this->normalizePath($path);
        $routeGroup = new RouteGroup($routePattern, $handler, $this);
        $this->collector->addGroup($routePattern, $routeGroup);

        return $routeGroup;
    }

    public function getRouteCollector(): RouteCollector
    {
        return $this->collector;
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Normalizes a path by ensuring:
     * - Starts with a forward slash
     * - No trailing slash (unless root path)
     * - No double slashes
     */
    private function normalizePath(string $path): string
    {
        // If path is empty or just a slash, return single slash
        if ($path === '' || $path === '/') {
            return '/';
        }

        // Ensure path starts with a slash
        $path = '/' . ltrim($path, '/');

        // Remove trailing slash unless it's the root path
        $path = rtrim($path, '/');

        // Replace multiple consecutive slashes with a single slash
        return preg_replace('#/+#', '/', $path);
    }
}
