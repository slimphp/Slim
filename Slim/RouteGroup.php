<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use Closure;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Interfaces\RouteGroupInterface;

/**
 * A collector for Routable objects with a common middleware stack
 *
 * @package Slim
 */
class RouteGroup extends Routable implements RouteGroupInterface
{
    /**
     * Create a new RouteGroup
     *
     * @param string                    $pattern  The pattern prefix for the group
     * @param callable                  $callable The group callable
     * @param ResponseFactoryInterface  $responseFactory
     * @param Closure|null              $deferredCallableResolver
     */
    public function __construct(
        string $pattern,
        $callable,
        ResponseFactoryInterface $responseFactory,
        Closure $deferredCallableResolver = null
    ) {
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->responseFactory = $responseFactory;
        $this->deferredCallableResolver = $deferredCallableResolver;
        $this->middlewareRunner = new MiddlewareRunner();
    }

    /**
     * Invoke the group to register any Routable objects within it.
     *
     * @param App $app The App instance to bind/pass to the group callable
     */
    public function __invoke(App $app = null)
    {
        /** @var callable $callable */
        $callable = $this->callable;

        if ($callableResolver = $this->getCallableResolver()) {
            $callable = $callableResolver->resolve($callable);
        }

        $callable($app);
    }
}
