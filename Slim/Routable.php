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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Middleware\DeferredResolutionMiddlewareWrapper;
use Slim\Middleware\Psr7MiddlewareWrapper;

/**
 * A routable, middleware-aware object
 *
 * @package Slim
 * @since   3.0.0
 */
abstract class Routable
{
    /**
     * Route callable
     *
     * @var callable|string
     */
    protected $callable;

    /**
     * @var CallableResolverInterface|null
     */
    protected $callableResolver;

    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var MiddlewareRunner
     */
    protected $middlewareRunner;

    /**
     * Route pattern
     *
     * @var string
     */
    protected $pattern;

    /**
     * @param MiddlewareInterface|callable|string $middleware
     * @return self
     */
    public function add($middleware)
    {
        if (is_string($middleware) && is_subclass_of($middleware, MiddlewareInterface::class)) {
            $callableResolver = $this->getCallableResolver();
            $container = $callableResolver !== null ? $callableResolver->getContainer() : null;
            $middleware = new DeferredResolutionMiddlewareWrapper($middleware, $container);
        } elseif (!($middleware instanceof MiddlewareInterface)) {
            $deferredCallable = new DeferredCallable($middleware, $this->getCallableResolver());
            $middleware = new Psr7MiddlewareWrapper($deferredCallable, $this->responseFactory);
        }

        $this->middlewareRunner->add($middleware);
        return $this;
    }

    /**
     * Get the middleware registered for the group
     *
     * @return MiddlewareInterface[]
     */
    public function getMiddleware(): array
    {
        return $this->middlewareRunner->getMiddleware();
    }

    /**
     * Get the route pattern
     *
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Set callable resolver
     *
     * @param CallableResolverInterface $resolver
     */
    public function setCallableResolver(CallableResolverInterface $resolver)
    {
        $this->callableResolver = $resolver;
    }

    /**
     * Get callable resolver
     *
     * @return CallableResolverInterface|null
     */
    public function getCallableResolver()
    {
        return $this->callableResolver;
    }

    /**
     * Set the route pattern
     *
     * @param string $newPattern
     */
    public function setPattern(string $newPattern)
    {
        $this->pattern = $newPattern;
    }
}
