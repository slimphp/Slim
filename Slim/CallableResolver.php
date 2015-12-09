<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim;

use RuntimeException;
use Interop\Container\ContainerInterface;
use Slim\Interfaces\CallableResolverInterface;

/**
 * This class resolves a string of the format 'class:method' into a closure
 * that can be dispatched.
 */
final class CallableResolver implements CallableResolverInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Resolve toResolve into a closure that that the router can dispatch.
     *
     * If toResolve is of the format 'class:method', then try to extract 'class'
     * from the container otherwise instantiate it and then dispatch 'method'.
     *
     * @param mixed $toResolve
     *
     * @return callable
     *
     * @throws RuntimeException if the callable does not exist
     * @throws RuntimeException if the callable is not resolvable
     */
    public function resolve($toResolve)
    {
        $resolved = $this->getResolved($toResolve);

        if (!is_callable($resolved)) {
            throw new RuntimeException(sprintf('%s is not resolvable', $toResolve));
        }

        return $resolved;
    }

    private function getResolved($toResolve)
    {
        if (is_callable($toResolve) || !is_string($toResolve)) {
            return $toResolve;
        }

        // check for slim callable as "class:method"
        $callablePattern = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';
        if (preg_match($callablePattern, $toResolve, $matches)) {
            $class = $matches[1];
            $method = $matches[2];

            if ($this->container->has($class)) {
                return [$this->container->get($class), $method];
            }

            if (!class_exists($class)) {
                throw new RuntimeException(sprintf('Callable %s does not exist', $class));
            }

            return [new $class($this->container), $method];
        }

        // check if string is something in the DIC that's callable or is a class name which
        // has an __invoke() method
        if ($this->container->has($toResolve)) {
            return $this->container->get($toResolve);
        }

        if (!class_exists($toResolve)) {
            throw new RuntimeException(sprintf('Callable %s does not exist', $toResolve));
        }

        return new $toResolve($this->container);
    }
}
