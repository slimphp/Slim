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

use Psr\Http\Message\UriInterface;

interface RouteCollectorProxyInterface
{
    /**
     * Add GET route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function get(string $pattern, $callable): RouteInterface;

    /**
     * Add POST route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function post(string $pattern, $callable): RouteInterface;

    /**
     * Add PUT route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function put(string $pattern, $callable): RouteInterface;

    /**
     * Add PATCH route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function patch(string $pattern, $callable): RouteInterface;

    /**
     * Add DELETE route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function delete(string $pattern, $callable): RouteInterface;

    /**
     * Add OPTIONS route
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function options(string $pattern, $callable): RouteInterface;

    /**
     * Add route for any HTTP method
     *
     * @param  string $pattern  The route URI pattern
     * @param  callable|string  $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function any(string $pattern, $callable): RouteInterface;

    /**
     * Add route with multiple methods
     *
     * @param  string[] $methods  Numeric array of HTTP method names
     * @param  string   $pattern  The route URI pattern
     * @param  callable|string    $callable The route callback routine
     *
     * @return RouteInterface
     */
    public function map(array $methods, string $pattern, $callable): RouteInterface;

    /**
     * Add a route that sends an HTTP redirect
     *
     * @param string              $from
     * @param string|UriInterface $to
     * @param int                 $status
     *
     * @return RouteInterface
     */
    public function redirect(string $from, $to, int $status = 302): RouteInterface;

    /**
     * Route Groups
     *
     * This method accepts a route pattern and a callback. All route
     * declarations in the callback will be prepended by the group(s)
     * that it is in.
     *
     * @param string   $pattern
     * @param callable $callable
     *
     * @return RouteGroupInterface
     */
    public function group(string $pattern, $callable): RouteGroupInterface;

    /**
     * Get The Route Collector Proxy's base path
     *
     * @return string
     */
    public function getBasePath(): string;

    /**
     * Set Route Collector Proxy's base path
     *
     * @param string $path
     * @return RouteCollectorProxyInterface
     */
    public function setBasePath(string $path): RouteCollectorProxyInterface;
}

