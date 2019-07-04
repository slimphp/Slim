<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
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
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @param ContainerInterface|null $container
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Resolve toResolve into a callable that the router can dispatch.
     *
     * If toResolve is of the format 'class:method', then try to extract 'class'
     * from the container otherwise instantiate it and then dispatch 'method'.
     *
     * @param mixed $toResolve
     * @return callable
     *
     * @throws RuntimeException if the callable does not exist
     * @throws RuntimeException if the callable is not resolvable
     */
    public function resolve($toResolve): callable
    {
        $resolved = $toResolve;

        if (!is_callable($toResolve) && is_string($toResolve)) {
            $class = $toResolve;
            $instance = null;
            $method = null;

            // check for slim callable as "class:method"
            $callablePattern = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';
            if (preg_match($callablePattern, $toResolve, $matches)) {
                $class = $matches[1];
                $method = $matches[2];
            }

            if ($this->container instanceof ContainerInterface && $this->container->has($class)) {
                $instance = $this->container->get($class);
            } else {
                if (!class_exists($class)) {
                    throw new RuntimeException(sprintf('Callable %s does not exist', $class));
                }
                $instance = new $class($this->container);
            }

            // For a class that implements RequestHandlerInterface, we will call handle()
            // if no method has been specified explicitly
            if ($instance instanceof RequestHandlerInterface && $method === null) {
                $method = 'handle';
            }

            $resolved = [$instance, $method ?? '__invoke'];
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

        if ($this->container instanceof ContainerInterface && $resolved instanceof Closure) {
            $resolved = $resolved->bindTo($this->container);
        }

        return $resolved;
    }
}
