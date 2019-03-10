<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use Psr\Http\Server\MiddlewareInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorProxyInterface;
use Slim\Interfaces\RouteGroupInterface;

class RouteGroup implements RouteGroupInterface
{
    /**
     * @var string
     */
    protected $pattern;

    /**
     * @var callable|string
     */
    protected $callable;

    /**
     * @var CallableResolverInterface
     */
    protected $callableResolver;

    /**
     * @var RouteCollectorProxyInterface
     */
    protected $routeCollectorProxy;

    /**
     * Middleware
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Create a new RouteGroup
     *
     * @param string                        $pattern
     * @param callable|string               $callable
     * @param CallableResolverInterface     $callableResolver
     * @param RouteCollectorProxyInterface  $routeCollectorProxy
     */
    public function __construct(
        string $pattern,
        $callable,
        CallableResolverInterface $callableResolver,
        RouteCollectorProxyInterface $routeCollectorProxy
    ) {
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->callableResolver = $callableResolver;
        $this->routeCollectorProxy = $routeCollectorProxy;
    }

    /**
     * {@inheritdoc}
     */
    public function collectRoutes(): RouteGroupInterface
    {
        $callable = $this->callableResolver->resolve($this->callable);
        $callable($this->routeCollectorProxy);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function add($middleware): RouteGroupInterface
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addMiddleware(MiddlewareInterface $middleware): RouteGroupInterface
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * {@inheritdoc}
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }
}
