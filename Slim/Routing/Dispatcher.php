<?php
declare(strict_types=1);

namespace Slim\Routing;

use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std;
use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;

/**
 * Class Dispatcher
 *
 * @package Slim\Routing
 */
class Dispatcher implements DispatcherInterface
{
    /**
     * @var RouteCollectorInterface
     */
    private $routeCollector;

    /**
     * @var RouteParser
     */
    private $routeParser;

    /**
     * @var FastRouteDispatcher|null
     */
    private $dispatcher;

    /**
     * Dispatcher constructor.
     *
     * @param RouteCollectorInterface   $routeCollector
     * @param RouteParser|null          $routeParser
     */
    public function __construct(RouteCollectorInterface $routeCollector, RouteParser $routeParser = null)
    {
        $this->routeCollector = $routeCollector;
        $this->routeParser = $routeParser ?? new Std();
    }

    /**
     * @return FastRouteDispatcher
     */
    protected function createDispatcher(): FastRouteDispatcher
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }

        $routeDefinitionCallback = function (RouteCollector $r) {
            foreach ($this->routeCollector->getRoutes() as $route) {
                $r->addRoute($route->getMethods(), $route->getPattern(), $route->getIdentifier());
            }
        };

        $cacheFile = $this->routeCollector->getCacheFile();
        if ($cacheFile) {
            /** @var FastRouteDispatcher $dispatcher */
            $dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback, [
                'dispatcher' => FastRouteDispatcher::class,
                'routeParser' => $this->routeParser,
                'cacheFile' => $cacheFile,
            ]);
        } else {
            /** @var FastRouteDispatcher $dispatcher */
            $dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback, [
                'dispatcher' => FastRouteDispatcher::class,
                'routeParser' => $this->routeParser,
            ]);
        }

        $this->dispatcher = $dispatcher;
        return $this->dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedMethods(string $uri): array
    {
        $dispatcher = $this->createDispatcher();
        return $dispatcher->getAllowedMethods($uri);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(string $method, string $uri): RoutingResults
    {
        $dispatcher = $this->createDispatcher();
        $results = $dispatcher->dispatch($method, $uri);
        return new RoutingResults($this, $method, $uri, $results[0], $results[1], $results[2]);
    }
}
