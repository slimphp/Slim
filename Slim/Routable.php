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
use Slim\Middleware\ClosureMiddleware;
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
     * @var CallableResolverInterface
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
     * @param MiddlewareInterface|string|callable $middleware
     * @return self
     */
    protected function addRouteMiddleware($middleware): self
    {
        if (is_string($middleware)) {
            $middleware = new DeferredResolutionMiddleware($middleware, $this->callableResolver->getContainer());
        } elseif ($middleware instanceof Closure) {
            $middleware = new ClosureMiddleware($middleware);
        } elseif (!($middleware instanceof MiddlewareInterface)) {
            $calledClass = get_called_class();
            throw new RuntimeException(
                "Parameter 1 of `{$calledClass}::add()` must be a closure or an object/class name ".
                "referencing an implementation of MiddlewareInterface."
            );
        }

        $this->middlewareRunner->add($middleware);
        return $this;
    }

    /**
     * Get callable resolver
     *
     * @return CallableResolverInterface
     */
    public function getCallableResolver(): CallableResolverInterface
    {
        return $this->callableResolver;
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
     * Set the route pattern
     *
     * @param string $newPattern
     */
    public function setPattern(string $newPattern)
    {
        $this->pattern = $newPattern;
    }
}
