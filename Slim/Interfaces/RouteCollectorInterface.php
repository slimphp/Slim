<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use InvalidArgumentException;
use RuntimeException;

interface RouteCollectorInterface
{
    /**
     * Get the route parser
     *
     * @return RouteParserInterface
     */
    public function getRouteParser(): RouteParserInterface;

    /**
     * Get default route invocation strategy
     *
     * @return InvocationStrategyInterface
     */
    public function getDefaultInvocationStrategy(): InvocationStrategyInterface;

    /**
     * Set default route invocation strategy
     *
     * @param InvocationStrategyInterface $strategy
     * @return RouteCollectorInterface
     */
    public function setDefaultInvocationStrategy(InvocationStrategyInterface $strategy): RouteCollectorInterface;

    /**
     * Get path to FastRoute cache file
     *
     * @return null|string
     */
    public function getCacheFile(): ?string;

    /**
     * Set path to FastRoute cache file
     *
     * @param string $cacheFile
     * @return RouteCollectorInterface
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function setCacheFile(string $cacheFile): RouteCollectorInterface;

    /**
     * Get the base path used in pathFor()
     *
     * @return string
     */
    public function getBasePath(): string;

    /**
     * Set the base path used in pathFor()
     *
     * @param string $basePath
     * @return RouteCollectorInterface
     */
    public function setBasePath(string $basePath): RouteCollectorInterface;

    /**
     * Get route objects
     *
     * @return RouteInterface[]
     */
    public function getRoutes(): array;

    /**
     * Get named route object
     *
     * @param string $name Route name
     *
     * @return RouteInterface
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function getNamedRoute(string $name): RouteInterface;

    /**
     * Remove named route
     *
     * @param string $name Route name
     * @return RouteCollectorInterface
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function removeNamedRoute(string $name): RouteCollectorInterface;

    /**
     * Lookup a route via the route's unique identifier
     *
     * @param string $identifier
     *
     * @return RouteInterface
     *
     * @throws RuntimeException   If route of identifier does not exist
     */
    public function lookupRoute(string $identifier): RouteInterface;

    /**
     * Add route group
     *
     * @param string          $pattern
     * @param string|callable $callable
     * @return RouteGroupInterface
     */
    public function group(string $pattern, $callable): RouteGroupInterface;

    /**
     * Add route
     *
     * @param string[]        $methods Array of HTTP methods
     * @param string          $pattern The route pattern
     * @param callable|string $handler The route callable
     *
     * @return RouteInterface
     */
    public function map(array $methods, string $pattern, $handler): RouteInterface;
}
