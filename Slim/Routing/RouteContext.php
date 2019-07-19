<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteParserInterface;

final class RouteContext
{
    /**
     * @param ServerRequestInterface $serverRequest
     * @return RouteContext
     */
    public static function fromRequest(ServerRequestInterface $serverRequest): self
    {
        $route = $serverRequest->getAttribute('route');
        $routeParser = $serverRequest->getAttribute('routeParser');
        $routingResults = $serverRequest->getAttribute('routingResults');

        if ($routeParser === null || $routingResults === null) {
            throw new RuntimeException('Cannot create RouteContext before routing has been completed');
        }

        return new self($route, $routeParser, $routingResults);
    }

    /**
     * @var RouteInterface|null
     */
    private $route;

    /**
     * @var RouteParserInterface
     */
    private $routeParser;

    /**
     * @var RoutingResults
     */
    private $routingResults;

    /**
     * @param RouteInterface|null  $route
     * @param RouteParserInterface $routeParser
     * @param RoutingResults       $routingResults
     */
    private function __construct(
        ?RouteInterface $route,
        RouteParserInterface $routeParser,
        RoutingResults $routingResults
    ) {
        $this->route = $route;
        $this->routeParser = $routeParser;
        $this->routingResults = $routingResults;
    }

    /**
     * @return RouteInterface|null
     */
    public function getRoute(): ?RouteInterface
    {
        return $this->route;
    }

    /**
     * @return RouteParserInterface
     */
    public function getRouteParser(): RouteParserInterface
    {
        return $this->routeParser;
    }

    /**
     * @return RoutingResults
     */
    public function getRoutingResults(): RoutingResults
    {
        return $this->routingResults;
    }
}
