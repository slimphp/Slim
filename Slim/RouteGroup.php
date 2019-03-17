<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteGroupInterface;

/**
 * A collector for Routable objects with a common middleware stack
 *
 * @package Slim
 */
class RouteGroup extends Routable implements RouteGroupInterface
{
    /**
     * middleware
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Create a new RouteGroup
     *
     * @param string                    $pattern  The pattern prefix for the group
     * @param callable                  $callable The group callable
     * @param ResponseFactoryInterface  $responseFactory
     * @param CallableResolverInterface $callableResolver
     */
    public function __construct(
        string $pattern,
        $callable,
        ResponseFactoryInterface $responseFactory,
        CallableResolverInterface $callableResolver
    ) {
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->responseFactory = $responseFactory;
        $this->callableResolver = $callableResolver;
    }

    /**
     * @param MiddlewareInterface|string|callable $middleware
     * @return RouteGroupInterface
     */
    public function add($middleware): RouteGroupInterface
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return RouteGroupInterface
     */
    public function addMiddleware(MiddlewareInterface $middleware): RouteGroupInterface
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Get the middleware registered for the group
     *
     * @return mixed[]
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Invoke the group to register any Routable objects within it.
     *
     * @param App $app The App instance to bind/pass to the group callable
     * @return self
     */
    public function __invoke(App $app = null): RouteGroupInterface
    {
        /** @var callable $callable */
        $callable = $this->callableResolver->resolve($this->callable);
        $callable($app);
        return $this;
    }
}
