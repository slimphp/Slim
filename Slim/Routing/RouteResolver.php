<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdParser;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteResolverInterface;

/**
 * RouteResolver instantiates the FastRoute dispatcher
 * and computes the routing results of a given URI and request method
 */
class RouteResolver implements RouteResolverInterface
{
    /**
     * @var RouteCollectorInterface
     */
    protected $routeCollector;

    /**
     * Parser
     *
     * @var RouteParser
     */
    protected $routeParser;

    /**
     * @var Dispatcher|null
     */
    private $dispatcher;

    /**
     * RouteResolver constructor.
     * @param RouteCollectorInterface $routeCollector
     * @param RouteParser $routeParser
     */
    public function __construct(RouteCollectorInterface $routeCollector, RouteParser $routeParser = null)
    {
        $this->routeCollector = $routeCollector;
        $this->routeParser = $routeParser ?? new StdParser();
    }

    /**
     * @param string $uri Should be $request->getUri()->getPath()
     * @param string $method
     * @return RoutingResults
     */
    public function computeRoutingResults(string $uri, string $method): RoutingResults
    {
        $uri = '/' . ltrim(rawurldecode($uri), '/');
        return $this->createDispatcher()->dispatch($method, $uri);
    }

    /**
     * @param string $identifier
     * @return RouteInterface
     */
    public function resolveRoute(string $identifier): RouteInterface
    {
        return $this->routeCollector->lookupRoute($identifier);
    }

    /**
     * @return Dispatcher
     */
    protected function createDispatcher(): Dispatcher
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
            /** @var Dispatcher $dispatcher */
            $dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback, [
                'dispatcher' => Dispatcher::class,
                'routeParser' => $this->routeParser,
                'cacheFile' => $cacheFile,
            ]);
        } else {
            /** @var Dispatcher $dispatcher */
            $dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback, [
                'dispatcher' => Dispatcher::class,
                'routeParser' => $this->routeParser,
            ]);
        }

        $this->dispatcher = $dispatcher;
        return $this->dispatcher;
    }

    /**
     * @param Dispatcher $dispatcher
     */
    public function setDispatcher(Dispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }
}
