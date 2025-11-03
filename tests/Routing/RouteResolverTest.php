<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use Error;
use Prophecy\Argument;
use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteResolver;
use Slim\Routing\RoutingResults;
use Slim\Tests\TestCase;

use function sprintf;

class RouteResolverTest extends TestCase
{
    public static function computeRoutingResultsDataProvider(): array
    {
        return [
            ['GET', '', '/'],
            ['GET', '/', '/'],
            ['GET', '//foo', '//foo'],
            ['GET', 'hello%20world', '/hello world'],
        ];
    }

    /**
     * @dataProvider computeRoutingResultsDataProvider
     *
     * @param string $method      The request method
     * @param string $uri         The request uri
     * @param string $expectedUri The expected uri after transformation in the computeRoutingResults()
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('computeRoutingResultsDataProvider')]
    public function testComputeRoutingResults(string $method, string $uri, string $expectedUri)
    {
        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);
        $routingResultsProphecy = $this->prophesize(RoutingResults::class);

        $dispatcherProphecy = $this->prophesize(DispatcherInterface::class);
        $dispatcherProphecy
            ->dispatch(Argument::type('string'), Argument::type('string'))
            ->will(function ($args) use ($routingResultsProphecy, $expectedUri) {
                if ($args[1] !== $expectedUri) {
                    throw new Error(sprintf(
                        "URI transformation failed.\n  Received: '%s'\n  Expected: '%s'",
                        $args[1],
                        $expectedUri
                    ));
                }
                return $routingResultsProphecy->reveal();
            })
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
