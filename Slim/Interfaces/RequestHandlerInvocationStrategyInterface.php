<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Defines a contract for invoking a route callable.
 */
interface RequestHandlerInvocationStrategyInterface
{
    /**
     * Invoke a route callable.
     *
     * @param callable $callable the callable to invoke using the strategy
     * @param ServerRequestInterface $request the request object
     * @param ResponseInterface $response the response object
     * @param array<string, mixed> $routeArguments The route's placeholder arguments
     *
     * @return ResponseInterface The response from the callable
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments,
    ): ResponseInterface;
}
