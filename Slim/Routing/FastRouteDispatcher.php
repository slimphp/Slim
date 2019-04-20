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
     * @var array
     */
    private $allowedMethods = [];

    /**
     * @param string $httpMethod
     * @param string $uri
     * @return array
     */
    public function dispatch($httpMethod, $uri): array
    {
        if (isset($this->staticRouteMap[$httpMethod][$uri])) {
            return [self::FOUND, $this->staticRouteMap[$httpMethod][$uri], []];
        }

        $varRouteData = $this->variableRouteData;
        if (isset($varRouteData[$httpMethod])) {
            $result = $this->dispatchVariableRoute($varRouteData[$httpMethod], $uri);
            $routingResults = $this->routingResultsFromVariableRouteResults($result);
            if ($routingResults[0] === self::FOUND) {
                return $routingResults;
            }
        }

        // For HEAD requests, attempt fallback to GET
        if ($httpMethod === 'HEAD') {
            if (isset($this->staticRouteMap['GET'][$uri])) {
                return [self::FOUND, $this->staticRouteMap['GET'][$uri], []];
            }
            if (isset($varRouteData['GET'])) {
                $result = $this->dispatchVariableRoute($varRouteData['GET'], $uri);
                return $this->routingResultsFromVariableRouteResults($result);
            }
        }

        // If nothing else matches, try fallback routes
        if (isset($this->staticRouteMap['*'][$uri])) {
            return [self::FOUND, $this->staticRouteMap['*'][$uri], []];
        }
        if (isset($varRouteData['*'])) {
            $result = $this->dispatchVariableRoute($varRouteData['*'], $uri);
            return $this->routingResultsFromVariableRouteResults($result);
        }

        if (count($this->getAllowedMethods($uri))) {
            return [self::METHOD_NOT_ALLOWED, null, []];
        }

        return [self::NOT_FOUND, null, []];
    }

    /**
     * @param array $result
     * @return array
     */
    protected function routingResultsFromVariableRouteResults(array $result): array
    {
        if ($result[0] === self::FOUND) {
            return [self::FOUND, $result[1], $result[2]];
        }
        return [self::NOT_FOUND, null, []];
    }

    /**
     * @param string $uri
     * @return array
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

        $varRouteData = $this->variableRouteData;
        foreach ($varRouteData as $method => $routeData) {
            $result = $this->dispatchVariableRoute($routeData, $uri);
            if ($result[0] === self::FOUND) {
                $this->allowedMethods[$uri][] = $method;
            }
        }

        return $this->allowedMethods[$uri];
    }
}
