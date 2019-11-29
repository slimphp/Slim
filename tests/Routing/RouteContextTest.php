<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
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
                              ->withAttribute(RouteContext::BASE_PATH_ATTRIBUTE_NAME, '')
                              ->withAttribute(RouteContext::ROUTE_ATTRIBUTE_NAME, $route)
                              ->withAttribute(RouteContext::ROUTE_PARSER_ATTRIBUTE_NAME, $routeParser)
                              ->withAttribute(RouteContext::ROUTING_RESULTS_ATTRIBUTE_NAME, $routingResults);

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
        $serverRequest = $serverRequest->withoutAttribute(RouteContext::ROUTE_ATTRIBUTE_NAME);

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
        $serverRequest = $serverRequest->withoutAttribute(RouteContext::BASE_PATH_ATTRIBUTE_NAME);

        $routeContext = RouteContext::fromRequest($serverRequest);
        $this->assertNotNull($routeContext->getRoute());
        $this->assertNotNull($routeContext->getRouteParser());
        $this->assertNotNull($routeContext->getRoutingResults());

        $this->expectException(RuntimeException::class);
        $routeContext->getBasePath();
    }

    public function requiredRouteContextRequestAttributes(): array
    {
        return [
            [RouteContext::ROUTE_PARSER_ATTRIBUTE_NAME],
            [RouteContext::ROUTING_RESULTS_ATTRIBUTE_NAME],
        ];
    }

    /**
     * @dataProvider requiredRouteContextRequestAttributes
     * @expectedException RuntimeException
     * @param string $attribute
     */
    public function testCannotCreateInstanceIfRequestIsMissingAttributes(string $attribute): void
    {
        $serverRequest = $this->createServerRequestWithRouteAttributes()->withoutAttribute($attribute);

        RouteContext::fromRequest($serverRequest);
    }

    private function createServerRequestWithRouteAttributes(): ServerRequestInterface
    {
        $route = $this->createMock(RouteInterface::class);
        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        return $this->createServerRequest('/')
                    ->withAttribute(RouteContext::BASE_PATH_ATTRIBUTE_NAME, '')
                    ->withAttribute(RouteContext::ROUTE_ATTRIBUTE_NAME, $route)
                    ->withAttribute(RouteContext::ROUTE_PARSER_ATTRIBUTE_NAME, $routeParser)
                    ->withAttribute(RouteContext::ROUTING_RESULTS_ATTRIBUTE_NAME, $routingResults);
    }
}
