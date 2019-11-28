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
    private static $routeAttributeName = '__route__';

    private static $routeParserAttributeName = '__routeParser__';

    private static $routingResultsAttributeName = '__routingResults__';

    private static $basePathAttributeName = '__basePath__';

    public static function setRouteAttributeName(string $routeAttributeName): void
    {
        self::$routeAttributeName = $routeAttributeName;
    }

    public static function setRouteParserAttributeName(string $routeParserAttributeName): void
    {
        self::$routeParserAttributeName = $routeParserAttributeName;
    }

    public static function setRoutingResultsAttributeName(string $routingResultsAttributeName): void
    {
        self::$routingResultsAttributeName = $routingResultsAttributeName;
    }

    public static function setBasePathAttributeName(string $basePathAttributeName): void
    {
        self::$basePathAttributeName = $basePathAttributeName;
    }

    public static function attachRoute(ServerRequestInterface $request, RouteInterface $route): ServerRequestInterface
    {
        return $request->withAttribute(self::$routeAttributeName, $route);
    }

    public static function grabRoute(ServerRequestInterface $request): ?RouteInterface
    {
        return $request->getAttribute(self::$routeAttributeName);
    }

    public static function attachRouteParser(
        ServerRequestInterface $request,
        RouteParserInterface $routeParser
    ): ServerRequestInterface {
        return $request->withAttribute(self::$routeParserAttributeName, $routeParser);
    }

    public static function attachRoutingResults(
        ServerRequestInterface $request,
        RoutingResults $routingResults
    ): ServerRequestInterface {
        return $request->withAttribute(self::$routingResultsAttributeName, $routingResults);
    }

    public static function routingResultsAttached(ServerRequestInterface $request): bool
    {
        return $request->getAttribute(self::$routingResultsAttributeName) !== null;
    }

    public static function attachBasePath(ServerRequestInterface $request, string $basePath): ServerRequestInterface
    {
        return $request->withAttribute(self::$basePathAttributeName, $basePath);
    }

    /**
     * @param ServerRequestInterface $serverRequest
     * @return RouteContext
     */
    public static function fromRequest(ServerRequestInterface $serverRequest): self
    {
        $route = $serverRequest->getAttribute(self::$routeAttributeName);
        $routeParser = $serverRequest->getAttribute(self::$routeParserAttributeName);
        $routingResults = $serverRequest->getAttribute(self::$routingResultsAttributeName);
        $basePath = $serverRequest->getAttribute(self::$basePathAttributeName);

        if ($routeParser === null || $routingResults === null) {
            throw new RuntimeException('Cannot create RouteContext before routing has been completed');
        }

        return new self($route, $routeParser, $routingResults, $basePath);
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
     * @var string|null
     */
    private $basePath;

    /**
     * @param RouteInterface|null  $route
     * @param RouteParserInterface $routeParser
     * @param RoutingResults       $routingResults
     * @param string|null          $basePath
     */
    private function __construct(
        ?RouteInterface $route,
        RouteParserInterface $routeParser,
        RoutingResults $routingResults,
        ?string $basePath = null
    ) {
        $this->route = $route;
        $this->routeParser = $routeParser;
        $this->routingResults = $routingResults;
        $this->basePath = $basePath;
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

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        if ($this->basePath === null) {
            throw new RuntimeException('No base path defined.');
        }
        return $this->basePath;
    }
}
