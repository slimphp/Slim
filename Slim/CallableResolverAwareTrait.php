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
use Slim\Interfaces\CallableResolverInterface;

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
     * @param mixed $callable
     *
     * @return \Closure
     *
     * @throws RuntimeException If the string cannot be resolved as a callable
     */
    protected function resolveCallable($callable)
    {
        if (!$this->container instanceof ContainerInterface) {
            return $callable;
        }

        /** @var CallableResolverInterface $resolver */
        $resolver = $this->container->get('callableResolver');

        return $resolver->resolve($callable);
    }
}
