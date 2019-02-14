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
use RuntimeException;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Middleware\DeferredResolutionMiddleware;

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
     * @param MiddlewareInterface|string $middleware
     * @return self
     */
    public function add($middleware)
    {
        if (is_string($middleware)) {
            $callableResolver = $this->getCallableResolver();
            $container = $callableResolver !== null ? $callableResolver->getContainer() : null;
            $middleware = new DeferredResolutionMiddleware($middleware, $container);
        } elseif (!($middleware instanceof MiddlewareInterface)) {
            $calledClass = get_called_class();
            throw new RuntimeException(
                "Parameter 1 of `{$calledClass}::add()` must be either an object or a class name ".
                "referencing an implementation of MiddlewareInterface."
            );
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
