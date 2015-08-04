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
use Interop\Container\ContainerInterface;

/**
 * ResolveCallable
 *
 * This is an internal class that enables resolution of 'class:method' strings
 * into a closure. This class is an implementation detail and is used only inside
 * of the Slim application.
 *
 * @property ContainerInterface $container
 */
trait CallableResolverAwareTrait
{
    /**
     * Resolve a string of the format 'class:method' into a closure that the
     * router can dispatch.
     *
     * @param string $callable
     *
     * @return \Closure
     *
     * @throws RuntimeException If the string cannot be resolved as a callable
     */
    protected function resolveCallable($callable)
    {
        if (is_string($callable) && !is_callable($callable)) {
            if ($this->container instanceof ContainerInterface) {
                $container = $this->container;
            } else {
                throw new RuntimeException('Cannot resolve callable string');
            }
            /** @var CallableResolver $resolver */
            $resolver = clone($container->get('callableResolver')); // we need a new one each time
            $resolver->setToResolve($callable);
            $callable = $resolver;
        }

        return $callable;
    }
}
