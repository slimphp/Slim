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
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Interfaces\CallableResolverInterface;

/**
 * This class resolves a string of the format 'class:method' into a closure
 * that can be dispatched.
 */
final class CallableResolver implements CallableResolverInterface
{
    /**
     * @var Closure|null
     */
    private $deferredContainerResolver;

    /**
     * @param Closure|null $deferredContainerResolver
     */
    public function __construct(Closure $deferredContainerResolver = null)
    {
        $this->deferredContainerResolver = $deferredContainerResolver;
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
    public function resolve($toResolve): callable
    {
        $resolved = $toResolve;

        $container = null;
        if ($deferredContainerResolver = $this->getDeferredContainerResolver()) {
            $container = ($deferredContainerResolver)();
        }

        if (!is_callable($toResolve) && is_string($toResolve)) {
            $class = $toResolve;
            $method = '__invoke';

            // check for slim callable as "class:method"
            $callablePattern = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';
            if (preg_match($callablePattern, $toResolve, $matches)) {
                $class = $matches[1];
                $method = $matches[2];
            }

            if ($container instanceof ContainerInterface && $container->has($class)) {
                $resolved = [$container->get($class), $method];
            } else {
                if (!class_exists($class)) {
                    throw new RuntimeException(sprintf('Callable %s does not exist', $class));
                }
                $resolved = [new $class($container), $method];
            }

            // For a class that implements RequestHandlerInterface, we will call handle()
            if ($resolved[0] instanceof RequestHandlerInterface) {
                $resolved[1] = 'handle';
            }
        }

        if ($resolved instanceof RequestHandlerInterface) {
            $resolved = [$resolved, 'handle'];
        }

        if (!is_callable($resolved)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_array($toResolve) || is_object($toResolve) ? json_encode($toResolve) : $toResolve
            ));
        }

        if ($container instanceof ContainerInterface && $resolved instanceof \Closure) {
            $resolved = $resolved->bindTo($container);
        }

        return $resolved;
    }

    /**
     * Returned Closure must return a ContainerInterface or null when called
     *
     * @return Closure|null
     */
    public function getDeferredContainerResolver(): ?Closure
    {
        return $this->deferredContainerResolver;
    }
}
