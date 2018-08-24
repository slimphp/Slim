<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim;

use FastRoute\Dispatcher\GroupCountBased;

class Dispatcher extends GroupCountBased
{
    /**
     * @var string
     */
    private $httpMethod;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var array
     */
    private $allowedMethods = [];

    /**
     * @param string $httpMethod
     * @param string $uri
     * @return RoutingResults
     */
    public function dispatch($httpMethod, $uri): RoutingResults
    {
        $this->httpMethod = $httpMethod;
        $this->uri = $uri;

        if (isset($this->staticRouteMap[$httpMethod][$uri])) {
            return $this->routingResults(self::FOUND, $this->staticRouteMap[$httpMethod][$uri]);
        }

        $varRouteData = $this->variableRouteData;
        if (isset($varRouteData[$httpMethod])) {
            $result = $this->dispatchVariableRoute($varRouteData[$httpMethod], $uri);
            $routingResults = $this->routingResultsFromVariableRouteResults($result);
            if ($routingResults->getRouteStatus() === Dispatcher::FOUND) {
                return $routingResults;
            }
        }

        // For HEAD requests, attempt fallback to GET
        if ($httpMethod === 'HEAD') {
            if (isset($this->staticRouteMap['GET'][$uri])) {
                return $this->routingResults(self::FOUND, $this->staticRouteMap['GET'][$uri]);
            }
            if (isset($varRouteData['GET'])) {
                $result = $this->dispatchVariableRoute($varRouteData['GET'], $uri);
                return $this->routingResultsFromVariableRouteResults($result);
            }
        }

        // If nothing else matches, try fallback routes
        if (isset($this->staticRouteMap['*'][$uri])) {
            return $this->routingResults(self::FOUND, $this->staticRouteMap['*'][$uri]);
        }
        if (isset($varRouteData['*'])) {
            $result = $this->dispatchVariableRoute($varRouteData['*'], $uri);
            return $this->routingResultsFromVariableRouteResults($result);
        }

        if (count($this->getAllowedMethods($httpMethod, $uri))) {
            return $this->routingResults(self::METHOD_NOT_ALLOWED);
        }

        return $this->routingResults(self::NOT_FOUND);
    }

    /**
     * @param int $status
     * @param string|null $handler
     * @param array $arguments
     * @return RoutingResults
     */
    protected function routingResults(int $status, string $handler = null, array $arguments = []): RoutingResults
    {
        return new RoutingResults($this, $this->httpMethod, $this->uri, $status, $handler, $arguments);
    }

    /**
     * @param array $result
     * @return RoutingResults
     */
    protected function routingResultsFromVariableRouteResults(array $result): RoutingResults
    {
        if ($result[0] === self::FOUND) {
            return $this->routingResults(self::FOUND, $result[1], $result[2]);
        }
        return $this->routingResults(self::NOT_FOUND);
    }

    /**
     * @param string $httpMethod
     * @param string $uri
     * @return array
     */
    public function getAllowedMethods(string $httpMethod, string $uri): array
    {
        if (isset($this->allowedMethods[$uri])) {
            return $this->allowedMethods[$uri];
        }

        $this->allowedMethods[$uri] = [];
        foreach ($this->staticRouteMap as $method => $uriMap) {
            if ($method !== $httpMethod && isset($uriMap[$uri])) {
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
