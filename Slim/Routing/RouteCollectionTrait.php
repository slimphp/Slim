<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use Slim\Interfaces\RouteInterface;

trait RouteCollectionTrait
{
    abstract public function map(array $methods, string $path, callable|string $handler): RouteInterface;

    abstract public function group(string $path, callable $handler): RouteGroup;

    public function get(string $path, callable|string $handler): RouteInterface
    {
        return $this->map(['GET'], $path, $handler);
    }

    public function post(string $path, callable|string $handler): RouteInterface
    {
        return $this->map(['POST'], $path, $handler);
    }

    public function put(string $path, callable|string $handler): RouteInterface
    {
        return $this->map(['PUT'], $path, $handler);
    }

    public function patch(string $path, callable|string $handler): RouteInterface
    {
        return $this->map(['PATCH'], $path, $handler);
    }

    public function delete(string $path, callable|string $handler): RouteInterface
    {
        return $this->map(['DELETE'], $path, $handler);
    }

    public function options(string $path, callable|string $handler): RouteInterface
    {
        return $this->map(['OPTIONS'], $path, $handler);
    }

    public function query(string $path, callable|string $handler): RouteInterface
    {
        return $this->map(['QUERY'], $path, $handler);
    }

    public function any(string $pattern, callable|string $handler): RouteInterface
    {
        return $this->map(['*'], $pattern, $handler);
    }
}
