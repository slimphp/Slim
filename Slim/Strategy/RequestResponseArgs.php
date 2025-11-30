<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Strategy;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;

use function array_values;

/**
 * Route callback strategy with route parameters as individual arguments.
 */
final class RequestResponseArgs implements RequestHandlerInvocationStrategyInterface
{
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments,
    ): ResponseInterface {
        return $callable($request, $response, ...array_values($routeArguments));
    }
}
