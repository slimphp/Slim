<?php

declare(strict_types=1);

namespace Slim\Interfaces;

use Slim\Routing\Route;
use Slim\Routing\RouteGroup;

/**
 * Collection interface for defining and grouping HTTP routes.
 *
 * Each method registers a route for one or more HTTP methods
 * and returns the corresponding Route instance.
 */
interface RouteCollectionInterface
{
    /**
     * Register a GET route.
     *
     * @param string $path Route path.
     * @param callable|string $handler Route handler or controller action.
     *
     * @return Route
     */
    public function get(string $path, callable|string $handler): Route;

    /**
     * Register a POST route.
     *
     * @param string $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function post(string $path, callable|string $handler): Route;

    /**
     * Register a PUT route.
     *
     * @param string $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function put(string $path, callable|string $handler): Route;

    /**
     * Register a PATCH route.
     *
     * @param string $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function patch(string $path, callable|string $handler): Route;

    /**
     * Register a DELETE route.
     *
     * @param string $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function delete(string $path, callable|string $handler): Route;

    /**
     * Register an OPTIONS route.
     *
     * @param string $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function options(string $path, callable|string $handler): Route;

    /**
     * Register a route for any HTTP method.
     *
     * @param string $path
     * @param callable|string $handler
     *
     * @return Route
     */
    public function any(string $path, callable|string $handler): Route;

    /**
     * Register a route with multiple HTTP methods.
     *
     * @param list<string> $methods List of HTTP methods.
     * @param string $path Route path.
     * @param callable|string $handler Route handler.
     *
     * @return Route
     */
    public function map(array $methods, string $path, callable|string $handler): Route;

    /**
     * Register a group of routes under a common path prefix.
     *
     * @param string $path Base path for the group.
     * @param callable $handler Function receiving the RouteGroup.
     *
     * @return RouteGroup
     */
    public function group(string $path, callable $handler): RouteGroup;
}
