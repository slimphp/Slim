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
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Middleware\DeferredResolutionMiddleware;

/**
 * Class Routable
 * @package Slim
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
     * @var Closure|null
     */
    protected $deferredCallableResolver;

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
            $deferredContainerResolver = $callableResolver !== null ? $callableResolver->getDeferredContainerResolver() : null;
            $middleware = new DeferredResolutionMiddleware($middleware, $deferredContainerResolver);
        } elseif (!($middleware instanceof MiddlewareInterface)) {
            $calledClass = get_called_class();
            throw new RuntimeException(
                "Parameter 1 of `{$calledClass}::add()` must be either an object or a class name ".
                "referencing an implementation of MiddlewareInterface."
            );
        }

        return $this->addMiddleware($middleware);
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
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
     * Get callable resolver
     *
     * @return CallableResolverInterface|null
     */
    public function getCallableResolver(): ?CallableResolverInterface
    {
        return $this->deferredCallableResolver ? ($this->deferredCallableResolver)() : null;
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
