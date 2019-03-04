<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use InvalidArgumentException;
use RuntimeException;
use Slim\Routing\Route;

interface RouteCollectorInterface
{
    /**
     * Add route
     *
     * @param string[] $methods Array of HTTP methods
     * @param string   $pattern The route pattern
     * @param callable $handler The route callable
     *
     * @return RouteInterface
     */
    public function map(array $methods, string $pattern, $handler): RouteInterface;

    /**
     * Add a route group to the array
     *
     * @param string   $pattern The group pattern
     * @param callable $callable A group callable
     *
     * @return RouteGroupInterface
     */
    public function group(string $pattern, $callable): RouteGroupInterface;

    /**
     * Get route objects
     *
     * @return Route[]
     */
    public function getRoutes(): array;

    /**
     * Get named route object
     *
     * @param string $name        Route name
     *
     * @return RouteInterface
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function getNamedRoute(string $name): RouteInterface;

    /**
     * Remove named route
     *
     * @param string $name        Route name
     * @return RouteCollectorInterface
     *
     * @throws RuntimeException   If named route does not exist
     */
    public function removeNamedRoute(string $name): RouteCollectorInterface;

    /**
     * @param string $identifier
     *
     * @return RouteInterface
     */
    public function lookupRoute(string $identifier): RouteInterface;

    /**
     * Build the path for a named route excluding the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function relativePathFor(string $name, array $data = [], array $queryParams = []): string;

    /**
     * Build the path for a named route including the base path
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function pathFor(string $name, array $data = [], array $queryParams = []): string;

    /**
     * Build the path for a named route.
     *
     * This method is deprecated. Use pathFor() from now on.
     *
     * @param string $name        Route name
     * @param array  $data        Named argument replacement data
     * @param array  $queryParams Optional query string parameters
     *
     * @return string
     *
     * @throws RuntimeException         If named route does not exist
     * @throws InvalidArgumentException If required data not provided
     */
    public function urlFor(string $name, array $data = [], array $queryParams = []): string;

    /**
     * Set the base path used in pathFor()
     *
     * @param string $basePath
     *
     * @return RouteCollectorInterface
     */
    public function setBasePath(string $basePath): RouteCollectorInterface;

    /**
     * Set default route invocation strategy
     *
     * @param InvocationStrategyInterface $strategy
     */
    public function setDefaultInvocationStrategy(InvocationStrategyInterface $strategy);

    /**
     * @return null|string
     */
    public function getCacheFile(): ?string;

    /**
     * Set path to fast route cache file. If this is false then route caching is disabled.
     *
     * @param string|null $cacheFile
     * @return self
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function setCacheFile(?string $cacheFile): RouteCollectorInterface;
}
