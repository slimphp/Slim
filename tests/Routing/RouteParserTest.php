<?php

declare(strict_types=1);

namespace Slim\Tests\Routing;

use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteParser;
use Slim\Tests\TestCase;

class RouteParserTest extends TestCase
{
    public static function urlForCases()
    {
        return [
            'with base path' => [
                true,
                '/{first}/{second}',
                ['first' => 'hello', 'second' => 'world'],
                [],
                '/app/hello/world',
            ],
            'without base path' => [
                false,
                '/{first}/{second}',
                ['first' => 'hello', 'second' => 'world'],
                [],
                '/hello/world',
            ],
            'with query parameters' => [
                false,
                '/{first}/{second}',
                ['first' => 'hello', 'second' => 'world'],
                ['a' => 'b', 'c' => 'd'],
                '/hello/world?a=b&c=d',
            ],
            'with query parameters containing array with string keys' => [
                false,
                '/{first}/{second}',
                ['first' => 'hello', 'second' => 'world'],
                ['a' => ['k' => '1', 'f' => 'x'], 'b', 'c' => 'd'],
                '/hello/world?a%5Bk%5D=1&a%5Bf%5D=x&0=b&c=d',
            ],
            'with query parameters containing array with numeric keys' => [
                false,
                '/{first}/{second}',
                ['first' => 'hello', 'second' => 'world'],
                ['a' => ['b', 'x', 'y'], 'c' => 'd'],
                '/hello/world?a%5B0%5D=b&a%5B1%5D=x&a%5B2%5D=y&c=d',
            ],
            'with argument without optional parameter' => [
                false,
                '/archive/{year}[/{month:[\d:{2}]}[/d/{day}]]',
                ['year' => '2015'],
                [],
                '/archive/2015',
            ],
            'with argument and optional parameter' => [
                false,
                '/archive/{year}[/{month:[\d:{2}]}[/d/{day}]]',
                ['year' => '2015', 'month' => '07'],
                [],
                '/archive/2015/07',
            ],
            'with argument and optional parameters' => [
                false,
                '/archive/{year}[/{month:[\d:{2}]}[/d/{day}]]',
                ['year' => '2015', 'month' => '07', 'day' => '19'],
                [],
                '/archive/2015/07/d/19',
            ],
        ];
    }

    public function testRelativePathForWithNoBasePath()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());

        $route = $routeCollector->map(['GET'], '/{first}/{second}', function () {
        });
        $route->setName('test');

        $routeParser = $routeCollector->getRouteParser();
        $results = $routeParser->relativeUrlFor('test', ['first' => 'hello', 'second' => 'world']);

        $this->assertSame('/hello/world', $results);
    }

    public function testBasePathIsIgnoreInRelativePathFor()
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeCollector->setBasePath('/app');

        $route = $routeCollector->map(['GET'], '/{first}/{second}', function () {
        });
        $route->setName('test');

        $routeParser = $routeCollector->getRouteParser();
        $results = $routeParser->relativeUrlFor('test', ['first' => 'hello', 'second' => 'world']);

        $this->assertSame('/hello/world', $results);
    }

    /**
     * @dataProvider urlForCases
     * @param $withBasePath
     * @param $pattern
     * @param $arguments
     * @param $queryParams
     * @param $expectedResult
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('urlForCases')]
    public function testUrlForWithBasePath($withBasePath, $pattern, $arguments, $queryParams, $expectedResult)
    {
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());

        if ($withBasePath) {
            $routeCollector->setBasePath('/app');
        }

        $route = $routeCollector->map(['GET'], $pattern, function () {
        });
        $route->setName('test');

        $routeParser = $routeCollector->getRouteParser();
        $results = $routeParser->urlFor('test', $arguments, $queryParams);

        $this->assertSame($expectedResult, $results);
    }

    public function testUrlForWithMissingSegmentData()
    {
        $this->expectException(InvalidArgumentException::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $route = $routeCollector->map(['GET'], '/{first}/{last}', function () {
        });
        $route->setName('test');

        $routeParser = $routeCollector->getRouteParser();
        $routeParser->urlFor('test', ['last' => 'world']);
    }

    public function testUrlForRouteThatDoesNotExist()
    {
        $this->expectException(RuntimeException::class);

        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $callableResolverProphecy = $this->prophesize(CallableResolverInterface::class);

        $routeCollector = new RouteCollector($responseFactoryProphecy->reveal(), $callableResolverProphecy->reveal());
        $routeParser = $routeCollector->getRouteParser();

        $routeParser->urlFor('test');
    }

    public function testFullUrlFor()
    {
        $uriProphecy = $this->prophesize(UriInterface::class);
        $uriProphecy
            ->getScheme()
            ->willReturn('http')
            ->shouldBeCalledOnce();

        $uriProphecy
            ->getAuthority()
            ->willReturn('example.com:8080')
            ->shouldBeCalledOnce();

        $routeProphecy = $this->prophesize(RouteInterface::class);
        $routeProphecy
            ->getPattern()
            ->willReturn('/{token}')
            ->shouldBeCalledOnce();

        $routeCollectorProphecy = $this->prophesize(RouteCollectorInterface::class);

        $routeCollectorProphecy
            ->getBasePath()
            ->willReturn('/app')
            ->shouldBeCalledOnce();

        $routeCollectorProphecy
            ->getNamedRoute('test')
            ->willReturn($routeProphecy->reveal())
            ->shouldBeCalledOnce();

        $routeParser = new RouteParser($routeCollectorProphecy->reveal());
        $result = $routeParser->fullUrlFor($uriProphecy->reveal(), 'test', ['token' => '123']);

        $expectedResult = 'http://example.com:8080/app/123';
        $this->assertSame($expectedResult, $result);
    }
}
