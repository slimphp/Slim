<?php

namespace Slim\Interfaces;

use FastRoute\RouteCollector;
use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Routing\Route;
use Slim\Routing\RouteGroup;
use Slim\Routing\Router;

interface RouterInterface
{
    public function get(string $path, callable|string $handler): Route;

    public function post(string $path, callable|string $handler): Route;

    public function put(string $path, callable|string $handler): Route;

    public function patch(string $path, callable|string $handler): Route;

    public function delete(string $path, callable|string $handler): Route;

    public function options(string $path, callable|string $handler): Route;

    public function any(string $pattern, callable|string $handler): Route;

    /**
     * @param array<string> $methods
     * @param string $path
     * @param callable|string $handler
     *
     * @throws InvalidArgumentException
     */
    public function map(array $methods, string $path, callable|string $handler): Route;

    public function group(string $path, callable $handler): RouteGroup;

    public function getRouteCollector(): RouteCollector;

    public function setBasePath(string $basePath): void;

    public function getBasePath(): string;

    /**
     * @return array<MiddlewareInterface|callable|string>
     */
    public function getMiddleware(): array;

    public function add(MiddlewareInterface|callable|string $middleware): Router;

    public function addMiddleware(MiddlewareInterface $middleware): Router;
}
