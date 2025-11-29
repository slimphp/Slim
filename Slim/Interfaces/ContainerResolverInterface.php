<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use Psr\Http\Server\MiddlewareInterface;

interface ContainerResolverInterface
{
    /**
     * Resolve the given $identifier to an object.
     *
     * @param callable|object|array<string|object,string>|string $identifier
     *
     * @return mixed An object or callable
     */
    public function resolve(callable|object|array|string $identifier): mixed;

    /**
     * Resolve the given $identifier to a callable that is bounded to the container.
     *
     * @param callable|string|array<string|object,string> $identifier
     *
     * @return callable A callable
     */
    public function resolveRoute(callable|array|string $identifier): callable;

    /**
     * Resolve the given $identifier to a middleware.
     *
     * @param MiddlewareInterface|callable|string|array<mixed,mixed> $middleware
     *
     * @return MiddlewareInterface
     */
    public function resolveMiddleware(MiddlewareInterface|callable|string|array $middleware): MiddlewareInterface;
}
