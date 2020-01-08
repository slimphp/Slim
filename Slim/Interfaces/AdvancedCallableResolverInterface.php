<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

interface AdvancedCallableResolverInterface extends CallableResolverInterface
{
    /**
     * Resolve $toResolve into a callable
     *
     * This callable will be invoked by the InvocationStrategyInterface.
     *
     * @param string|callable $toResolve
     *
     * @return callable
     */
    public function resolveRoute($toResolve): callable;

    /**
     * Resolve $toResolve into a callable
     *
     * This callable will use the same signature as MiddlewareInterface::handle()
     * but does not need to implement the interface.
     *
     * @param string|callable $toResolve
     *
     * @return callable
     */
    public function resolveMiddleware($toResolve): callable;
}
