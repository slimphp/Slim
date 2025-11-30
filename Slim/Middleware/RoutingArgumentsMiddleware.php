<?php

/**
 * Slim Framework (https://slimframework.com).
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

/**
 * Add routing arguments to the request attributes.
 */
final class RoutingArgumentsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /* @var RoutingResults|null $routingResults */
        $routingResults = $request->getAttribute(RouteContext::ROUTING_RESULTS);

        if ($routingResults instanceof RoutingResults) {
            foreach ($routingResults->getRouteArguments() as $key => $value) {
                $request = $request->withAttribute($key, $value);
            }
        }

        return $handler->handle($request);
    }
}
