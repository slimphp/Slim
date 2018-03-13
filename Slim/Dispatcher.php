<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
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
     * @param string $httpMethod
     * @param string $uri
     * @return DispatcherResults
     */
    public function dispatch($httpMethod, $uri)
    {
        $this->httpMethod = $httpMethod;
        $this->uri = $uri;

        if (isset($this->staticRouteMap[$httpMethod][$uri])) {
            return $this->dispatcherResults(self::FOUND, $this->staticRouteMap[$httpMethod][$uri]);
        }

        $varRouteData = $this->variableRouteData;
        if (isset($varRouteData[$httpMethod])) {
            $result = $this->dispatchVariableRoute($varRouteData[$httpMethod], $uri);
            $dispatcherResults = $this->dispatcherResultsFromVariableRouteResults($result);
            if ($dispatcherResults->getRouteStatus() === Dispatcher::FOUND) {
                return $dispatcherResults;
            }
        }

        // For HEAD requests, attempt fallback to GET
        if ($httpMethod === 'HEAD') {
            if (isset($this->staticRouteMap['GET'][$uri])) {
                return $this->dispatcherResults(self::FOUND, $this->staticRouteMap['GET'][$uri]);
            }
            if (isset($varRouteData['GET'])) {
                $result = $this->dispatchVariableRoute($varRouteData['GET'], $uri);
                return $this->dispatcherResultsFromVariableRouteResults($result);
            }
        }

        // If nothing else matches, try fallback routes
        if (isset($this->staticRouteMap['*'][$uri])) {
            return $this->dispatcherResults(self::FOUND, $this->staticRouteMap['*'][$uri]);
        }
        if (isset($varRouteData['*'])) {
            $result = $this->dispatchVariableRoute($varRouteData['*'], $uri);
            return $this->dispatcherResultsFromVariableRouteResults($result);
        }

        if (count($this->getAllowedMethods($httpMethod, $uri))) {
            return $this->dispatcherResults(self::METHOD_NOT_ALLOWED);
        }

        return $this->dispatcherResults(self::NOT_FOUND);
    }

    /**
     * @param $status
     * @param null $handler
     * @param array $arguments
     * @return DispatcherResults
     */
    protected function dispatcherResults($status, $handler = null, $arguments = [])
    {
        return new DispatcherResults($this, $this->httpMethod, $this->uri, $status, $handler, $arguments);
    }

    /**
     * @param array $result
     * @return DispatcherResults
     */
    protected function dispatcherResultsFromVariableRouteResults($result)
    {
        if ($result[0] === self::FOUND) {
            return $this->dispatcherResults(self::FOUND, $result[1], $result[2]);
        }
        return $this->dispatcherResults(self::NOT_FOUND);
    }

    /**
     * @param string $httpMethod
     * @param string $uri
     * @param bool $includeRequestedMethod
     * @return array
     */
    public function getAllowedMethods($httpMethod, $uri, $includeRequestedMethod = true)
    {
        $httpMethod = $includeRequestedMethod ? 'ArtificialMethod' : $httpMethod;

        $allowedMethods = [];
        foreach ($this->staticRouteMap as $method => $uriMap) {
            if ($method !== $httpMethod && isset($uriMap[$uri])) {
                $allowedMethods[] = $method;
            }
        }

        $varRouteData = $this->variableRouteData;
        foreach ($varRouteData as $method => $routeData) {
            if ($method === $httpMethod) {
                continue;
            }
            $result = $this->dispatchVariableRoute($routeData, $uri);
            if ($result[0] === self::FOUND) {
                $allowedMethods[] = $method;
            }
        }

        return $allowedMethods;
    }
}
