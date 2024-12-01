<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Container;

use Closure;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Slim\Interfaces\ContainerResolverInterface;

use function is_array;

/**
 *  This class is responsible for resolving dependencies or services from a PSR-11 compatible DI container.
 *  It can handle resolving strings, arrays, callables, and objects. If the provided identifier is a string,
 *  it can also interpret Slim's notation (e.g., "service:method") or the standard "::" notation for static
 *  method calls.
 *
 *  The primary use case for this class is to provide a way to retrieve or resolve services and callables from
 *  a container by processing the given identifier.
 */
final class ContainerResolver implements ContainerResolverInterface
{
    private ContainerInterface $container;

    private string $callablePattern = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(callable|object|array|string $identifier): mixed
    {
        if (is_object($identifier) || is_callable($identifier)) {
            return $identifier;
        }

        // The callable is a container entry name
        if (is_string($identifier)) {
            $identifier = $this->processStringNotation($identifier);
        }

        if (is_string($identifier)) {
            return $this->container->get($identifier);
        }

        // The callable is an array whose first item is a container entry name
        // e.g. ['some-container-entry', 'methodToCall']
        if (is_string($identifier[0] ?? null)) {
            // Replace the container entry name by the actual object
            $identifier[0] = $this->container->get($identifier[0]);

            if (!method_exists($identifier[0], (string)$identifier[1])) {
                throw new RuntimeException(sprintf('The method "%s" does not exists', $identifier[1]));
            }
        }

        return $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveCallable(callable|array|string $identifier): callable
    {
        $callable = $this->resolve($identifier);

        if (is_callable($callable)) {
            return $callable;
        }

        // Unrecognized stuff, we let it fail
        throw new RuntimeException(
            sprintf('The definition "%s" is not a callable.', implode(':', (array)$identifier))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRoute(callable|array|string $identifier): callable
    {
        $callable = $this->resolveCallable($identifier);

        return $this->bindToContainer($callable);
    }

    private function processStringNotation(string $toResolve): string|array
    {
        // Resolve Slim notation
        $matches = null;
        if (preg_match($this->callablePattern, $toResolve, $matches)) {
            return $matches ? [$matches[1], $matches[2]] : [$toResolve, null];
        }

        return $toResolve;
    }

    private function bindToContainer(callable $callable): callable
    {
        if (is_array($callable) && $callable[0] instanceof Closure) {
            $callable = $callable[0];
        }

        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this->container) ?? throw new RuntimeException(
                'Unable to bind callable to DI container.'
            );
        }

        return $callable;
    }
}
