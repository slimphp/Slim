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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Interfaces\ContainerResolverInterface;

use function is_array;

/**
 * Resolves identifiers into services or callables using a PSR-11 DI container.
 *
 * Supports:
 * - Service names (strings)
 * - Slim notation: "service:method"
 * - PHP notation: "Class::method"
 * - Arrays like ["service", "method"]
 * - Callables or objects directly
 *
 * Returned results can be:
 * - A callable
 * - An object fetched from the container
 * - A callable bound to the container (closures)
 *
 * This is used internally by Slim to resolve route callables, middleware, and
 * other handler definitions.
 */
final class ContainerResolver implements ContainerResolverInterface
{
    private ContainerInterface $container;

    /**
     * Regex matching Slim-style "service:method" callables.
     */
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
        // Already a callable or object - no further resolution needed
        if (is_object($identifier) || is_callable($identifier)) {
            return $identifier;
        }

        // ClassName::methodName or Slim notation ClassName:methodName
        if (is_string($identifier) && preg_match($this->callablePattern, $identifier, $matches)) {
            $identifier = [$matches[1], $matches[2]];
        }

        // Resolve as a container entry name
        if (is_string($identifier)) {
            return $this->container->get($identifier);
        }

        // Array callable notation: ['service-id', 'method']
        // @phpstan-ignore-next-line
        if (is_string($identifier[0])) {
            // Replace the container entry name by the actual object
            $service = $this->container->get($identifier[0]);

            if (!is_object($service) && !is_string($service)) {
                throw new RuntimeException(
                    sprintf(
                        'Container entry "%s" must resolve to an object or class name.',
                        $identifier[0],
                    ),
                );
            }

            $method = (string)($identifier[1] ?? '');

            if (!method_exists($service, $method)) {
                throw new RuntimeException(sprintf('The method "%s" does not exist', $method));
            }

            return [$service, $method];
        }

        // @phpstan-ignore-next-line
        return $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRoute(callable|array|string $identifier): callable
    {
        $callable = $this->resolveCallable($identifier);

        return $this->bindToContainer($callable);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMiddleware(MiddlewareInterface|callable|string|array $middleware): MiddlewareInterface
    {
        $middleware = $this->resolve($middleware);

        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if (is_callable($middleware)) {
            return $this->createMiddlewareFromCallable($middleware);
        }

        throw new RuntimeException('A middleware must be an object or callable that implements "MiddlewareInterface".');
    }

    /**
     * Wraps a callable into a temporary MiddlewareInterface implementation.
     *
     * @param callable $middleware
     *
     * @throws RuntimeException
     */
    private function createMiddlewareFromCallable(callable $middleware): MiddlewareInterface
    {
        if ($middleware instanceof Closure) {
            /** @var Closure $middleware */
            $middleware = $middleware->bindTo($this->container) ?? throw new RuntimeException(
                'Unable to bind middleware to DI container.',
            );
        }

        return new class ($middleware) implements MiddlewareInterface {
            /**
             * @var callable
             */
            private $middleware;

            public function __construct(callable $middleware)
            {
                $this->middleware = $middleware;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return ($this->middleware)($request, $handler);
            }
        };
    }

    /**
     * Resolve the given $identifier to an object or callable.
     *
     * @param callable|array<string|object,string>|string $identifier
     *
     * @return callable A callable
     */
    private function resolveCallable(callable|array|string $identifier): callable
    {
        $callable = $this->resolve($identifier);

        if (is_callable($callable)) {
            return $callable;
        }

        // Unrecognized stuff, we let it fail
        throw new RuntimeException(
            sprintf('The definition "%s" is not a callable.', implode(':', (array)$identifier)),
        );
    }

    /**
     * Bind closures to the container to allow `$this` access.
     * @param callable $callable
     */
    private function bindToContainer(callable $callable): callable
    {
        if (is_array($callable) && $callable[0] instanceof Closure) {
            $callable = $callable[0];
        }

        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this->container) ?? throw new RuntimeException(
                'Unable to bind callable to DI container.',
            );
        }

        return $callable;
    }
}
