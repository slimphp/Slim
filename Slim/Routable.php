<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use Interop\Container\ContainerInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;

/**
 * A routable, middleware-aware object
 *
 * @package Slim
 * @since   3.0.0
 */
abstract class Routable
{
    use CallableResolverAwareTrait;

    /**
     * Route callable
     *
     * @var callable
     */
    protected $callable;

    /**
     * Container
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var InvocationStrategyInterface
     */
    protected $foundHandler;

    /**
     * @var CallableResolverInterface
     */
    protected $callableResolver;

    /**
     * Route middleware
     *
     * @var callable[]
     */
    protected $middleware = [];

    /**
     * Route pattern
     *
     * @var string
     */
    protected $pattern;

    /**
     * Get the middleware registered for the group
     *
     * @return callable[]
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Get the route pattern
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Set container for use with resolveCallable
     *
     * @param ContainerInterface $container
     *
     * @return self
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the foundHandler for use
     *
     * @param InvocationStrategyInterface $foundHandler
     *
     * @return self
     */
    public function setFoundHandler(InvocationStrategyInterface $foundHandler)
    {
        $this->foundHandler = $foundHandler;
        return $this;
    }

    /**
     * @param CallableResolverInterface $callableResolver
     * @return $this
     */
    public function setCallableResolver(CallableResolverInterface $callableResolver) {
        $this->callableResolver = $callableResolver;
        return $this;
    }

    protected function getCallableResolver() {
        return $this->callableResolver;
    }
}
