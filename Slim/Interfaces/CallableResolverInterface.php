<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

interface CallableResolverInterface
{
    /**
     * Resolve $toResolve into a callable
     *
     * The callable signature is context specific, where a route callable
     * is passed to the InvocationStrategyInterface for invocation and
     * middleware are called via MiddlewareInterface::handle() method.
     *
     * @param string|callable $toResolve
     * @return callable
     */
    public function resolve($toResolve): callable;
}
