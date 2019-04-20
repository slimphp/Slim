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
     * @var FastRouteDispatcher
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

        if ($cacheFile = $this->routeCollector->getCacheFile()) {
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
    public function getAllowedMethods(string $method, string $uri): array
    {
        return $this->dispatcher->getAllowedMethods($method, $uri);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(string $method, string $uri): RoutingResults
    {
        $dispatcher = $this->createDispatcher();
        return $dispatcher->dispatch($method, $uri);
    }
}
