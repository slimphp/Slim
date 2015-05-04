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

/**
 * ResolveCallable
 *
 * This is an internal class that enables resolution of 'class:method' strings
 * into a closure. This class is an implementation detail and is used only inside
 * of the Slim application.
 */
trait ResolveCallable
{
    /**
     * Resolve a string of the format 'class:method' into a closure that the
     * router can dispatch.
     *
     * @param  string $callable
     *
     * @return \Closure
     */
    protected function resolveCallable($callable)
    {
        if (is_string($callable) && strpos($callable, ':')) {
            if ($this->container instanceof ContainerInterface) {
                $container = $this->container;
            } else {
                throw new \RuntimeException('Cannot resolve callable string');
            }
            $resolver = $container['callableResolver'];
            $resolver->setToResolve($callable);
            $callable = $resolver;
        }

        return $callable;
    }
}
