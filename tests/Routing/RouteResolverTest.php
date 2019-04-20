<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteResolver;
use Slim\Routing\RoutingResults;
use Slim\Tests\TestCase;

class RouteResolverTest extends TestCase
{
    public function testComputeRoutingResults()
    {
        $method = 'GET';
        $uri = '/';

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routingResultsProphecy = $this->prophesize(RoutingResults::class);

        $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
        $dispatcherProphecy
            ->dispatch($method, $uri)
            ->willReturn($routingResultsProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeResolver = new RouteResolver(
            $routeCollectorProphecy->reveal(),
            $dispatcherProphecy->reveal()
        );

        $routeResolver->computeRoutingResults($uri, $method);
    }

    public function testResolveRoute()
    {
        $identifier = 'test';

        $routeProphecy = $this->prophesize(RouteInterface::class);
        $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routeCollectorProphecy
            ->lookupRoute($identifier)
            ->willReturn($routeProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeResolver = new RouteResolver(
            $routeCollectorProphecy->reveal(),
            $dispatcherProphecy->reveal()
        );

        $routeResolver->resolveRoute($identifier);
    }
}
