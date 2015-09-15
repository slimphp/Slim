<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/codeguy/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/codeguy/Slim/blob/master/LICENSE (MIT License)
 */
namespace Slim;

use RuntimeException;
use Slim\Interfaces\ServiceConfigInterface;

/**
 * ResolveCallable
 *
 * This is an internal class that enables resolution of 'class:method' strings
 * into a closure. This class is an implementation detail and is used only inside
 * of the Slim application.
 *
 * @property ServiceConfigInterface $service
 */
trait CallableResolverAwareTrait
{
    /**
     * Resolve a string of the format 'class:method' into a closure that the
     * router can dispatch.
     *
     * @param mixed $callable
     *
     * @return \Closure
     *
     * @throws RuntimeException If the string cannot be resolved as a callable
     */
    protected function resolveCallable($callable)
    {
        if (!$this->service instanceof ServiceConfigInterface) {
            return $callable;
        }

        $resolver = $this->service->newCallableResolver();

        return $resolver->resolve($callable);
    }
}
