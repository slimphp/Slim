<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use FastRoute\Dispatcher\GroupCountBased;

class FastRouteDispatcher extends GroupCountBased
{
    /**
     * @var string[][]
     */
    private $allowedMethods = [];

    /**
     * @param string $httpMethod
     * @param string $uri
     *
     * @return array<mixed>
     */
    public function dispatch($httpMethod, $uri): array
    {
        $routingResults = $this->routingResults($httpMethod, $uri);
        if ($routingResults[0] === self::FOUND) {
            return $routingResults;
        }

        // For HEAD requests, attempt fallback to GET
        if ($httpMethod === 'HEAD') {
            $routingResults = $this->routingResults('GET', $uri);
            if ($routingResults[0] === self::FOUND) {
                return $routingResults;
            }
        }

        // If nothing else matches, try fallback routes
        $routingResults = $this->routingResults('*', $uri);
        if ($routingResults[0] === self::FOUND) {
            return $routingResults;
        }

        if (!empty($this->getAllowedMethods($uri))) {
            return [self::METHOD_NOT_ALLOWED, null, []];
        }

        return [self::NOT_FOUND, null, []];
    }

    /**
     * @param string $httpMethod
     * @param string $uri
     *
     * @return array<mixed>
     */
    private function routingResults(string $httpMethod, string $uri): array
    {
        if (isset($this->staticRouteMap[$httpMethod][$uri])) {
            return [self::FOUND, $this->staticRouteMap[$httpMethod][$uri], []];
        }

        if (isset($this->variableRouteData[$httpMethod])) {
            $result = $this->dispatchVariableRoute($this->variableRouteData[$httpMethod], $uri);
            if ($result[0] === self::FOUND) {
                return [self::FOUND, $result[1], $result[2]];
            }
        }

        return [self::NOT_FOUND, null, []];
    }

    /**
     * @param string $uri
     *
     * @return string[]
     */
    public function getAllowedMethods(string $uri): array
    {
        if (isset($this->allowedMethods[$uri])) {
            return $this->allowedMethods[$uri];
        }

        $this->allowedMethods[$uri] = [];
        foreach ($this->staticRouteMap as $method => $uriMap) {
            if (isset($uriMap[$uri])) {
                $this->allowedMethods[$uri][] = $method;
            }
        }

        foreach ($this->variableRouteData as $method => $routeData) {
            $result = $this->dispatchVariableRoute($routeData, $uri);
            if ($result[0] === self::FOUND) {
                $this->allowedMethods[$uri][] = $method;
            }
        }

        return $this->allowedMethods[$uri];
    }
}
