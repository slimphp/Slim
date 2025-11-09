<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;
use Slim\Tests\TestCase;

class RouteContextTest extends TestCase
{
    public function testCanCreateInstanceFromServerRequest(): void
    {
        $route = $this->createMock(RouteInterface::class);
        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        $serverRequest = $this->createServerRequest('/')
                              ->withAttribute(RouteContext::BASE_PATH, '')
                              ->withAttribute(RouteContext::ROUTE, $route)
                              ->withAttribute(RouteContext::ROUTE_PARSER, $routeParser)
                              ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        $routeContext = RouteContext::fromRequest($serverRequest);

        $this->assertSame($route, $routeContext->getRoute());
        $this->assertSame($routeParser, $routeContext->getRouteParser());
        $this->assertSame($routingResults, $routeContext->getRoutingResults());
        $this->assertSame('', $routeContext->getBasePath());
    }

    public function testCanCreateInstanceWithoutRoute(): void
    {
        $serverRequest = $this->createServerRequestWithRouteAttributes();

        // Route attribute is not required
        $serverRequest = $serverRequest->withoutAttribute(RouteContext::ROUTE);

        $routeContext = RouteContext::fromRequest($serverRequest);
        $this->assertNull($routeContext->getRoute());
        $this->assertNotNull($routeContext->getRouteParser());
        $this->assertNotNull($routeContext->getRoutingResults());
        $this->assertNotNull($routeContext->getBasePath());
    }

    public function testCanCreateInstanceWithoutBasePathAndThrowExceptionIfGetBasePathIsCalled(): void
    {
        $serverRequest = $this->createServerRequestWithRouteAttributes();

        // Route attribute is not required
        $serverRequest = $serverRequest->withoutAttribute(RouteContext::BASE_PATH);

        $routeContext = RouteContext::fromRequest($serverRequest);
        $this->assertNotNull($routeContext->getRoute());
        $this->assertNotNull($routeContext->getRouteParser());
        $this->assertNotNull($routeContext->getRoutingResults());

        $this->expectException(RuntimeException::class);
        $routeContext->getBasePath();
    }

    public static function requiredRouteContextRequestAttributes(): array
    {
        return [
            [RouteContext::ROUTE_PARSER],
            [RouteContext::ROUTING_RESULTS],
        ];
    }

    /**
     * @dataProvider requiredRouteContextRequestAttributes
     * @param string $attribute
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('requiredRouteContextRequestAttributes')]
    public function testCannotCreateInstanceIfRequestIsMissingAttributes(string $attribute): void
    {
        $this->expectException(RuntimeException::class);

        $serverRequest = $this->createServerRequestWithRouteAttributes()->withoutAttribute($attribute);

        RouteContext::fromRequest($serverRequest);
    }

    private function createServerRequestWithRouteAttributes(): ServerRequestInterface
    {
        $route = $this->createMock(RouteInterface::class);
        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        return $this->createServerRequest('/')
                    ->withAttribute(RouteContext::BASE_PATH, '')
                    ->withAttribute(RouteContext::ROUTE, $route)
                    ->withAttribute(RouteContext::ROUTE_PARSER, $routeParser)
                    ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);
    }
}
