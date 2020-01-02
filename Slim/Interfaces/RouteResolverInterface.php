<?php

declare(strict_types=1);

namespace Slim\Interfaces;

use Slim\Routing\RoutingResults;

interface RouteResolverInterface
{
    /**
     * @param string $uri Should be ServerRequestInterface::getUri()->getPath()
     * @param string $method
     * @return RoutingResults
     */
    public function computeRoutingResults(string $uri, string $method): RoutingResults;

    /**
     * @param string $identifier
     * @return RouteInterface
     */
    public function resolveRoute(string $identifier): RouteInterface;
}
