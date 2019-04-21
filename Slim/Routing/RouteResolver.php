<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Routing;

use RuntimeException;
use Slim\Interfaces\DispatcherInterface;
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
     * @var DispatcherInterface
     */
    private $dispatcher;

    /**
     * @param RouteCollectorInterface  $routeCollector
     * @param DispatcherInterface|null $dispatcher
     */
    public function __construct(RouteCollectorInterface $routeCollector, ?DispatcherInterface $dispatcher = null)
    {
        $this->routeCollector = $routeCollector;
        $this->dispatcher = $dispatcher ?? new Dispatcher($routeCollector);
    }

    /**
     * @param string $uri Should be $request->getUri()->getPath()
     * @param string $method
     * @return RoutingResults
     */
    public function computeRoutingResults(string $uri, string $method): RoutingResults
    {
        $uri = '/' . ltrim(rawurldecode($uri), '/');
        return $this->dispatcher->dispatch($method, $uri);
    }

    /**
     * @param string $identifier
     * @return RouteInterface
     * @throws RuntimeException
     */
    public function resolveRoute(string $identifier): RouteInterface
    {
        return $this->routeCollector->lookupRoute($identifier);
    }
}
