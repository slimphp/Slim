<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim;

use Closure;
use Interop\Container\ContainerInterface;

class DeferredCallable
{
    use CallableResolverAwareTrait;

    /** @var callable|string */
    private $callable;

    /** @var  ContainerInterface */
    private $container;

    /**
     * DeferredMiddleware constructor.
     *
     * @param callable|string $callable
     * @param ContainerInterface $container
     */
    public function __construct($callable, ContainerInterface $container = null)
    {
        $this->callable = $callable;
        $this->container = $container;
    }

    public function __invoke()
    {
        return call_user_func_array($this->getResolvedCallable(), func_get_args());
    }

    public function getResolvedCallable()
    {
        $callable = $this->resolveCallable($this->callable);
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this->container);
        }
        return $callable;
    }
}
