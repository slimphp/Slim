<?php

namespace Slim\Interfaces;

use FastRoute\RouteCollector;
use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Routing\RouteGroup;

interface RouterInterface
{
    public function get(string $path, callable|string $handler): RouteInterface;

    public function post(string $path, callable|string $handler): RouteInterface;

    public function put(string $path, callable|string $handler): RouteInterface;

    public function patch(string $path, callable|string $handler): RouteInterface;

    public function delete(string $path, callable|string $handler): RouteInterface;

    public function options(string $path, callable|string $handler): RouteInterface;

    public function any(string $pattern, callable|string $handler): RouteInterface;

    /**
     * @param array<string> $methods
     * @param string $path
     * @param callable|string $handler
     *
     * @throws InvalidArgumentException
     */
    public function map(array $methods, string $path, callable|string $handler): RouteInterface;

    public function group(string $path, callable $handler): RouteGroup;

    public function getRouteCollector(): RouteCollector;

    public function setBasePath(string $basePath): void;

    public function getBasePath(): ?string;

    /**
     * @return array<MiddlewareInterface|callable|string>
     */
    public function getMiddleware(): array;

    public function add(MiddlewareInterface|callable|string $middleware): RouterInterface;

    public function addMiddleware(MiddlewareInterface $middleware): RouterInterface;
}
