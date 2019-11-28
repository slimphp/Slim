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
    public function testStaticSetAttributeNames()
    {
        $routeContextReflection = new ReflectionClass(RouteContext::class);
        $originals = $routeContextReflection->getStaticProperties();

        RouteContext::setRouteAttributeName('##route##');
        RouteContext::setRouteParserAttributeName('##routeParser##');
        RouteContext::setRoutingResultsAttributeName('##routingResults##');
        RouteContext::setBasePathAttributeName('##basePath##');

        $properties = $routeContextReflection->getStaticProperties();

        // Set the static properties back to their original values
        RouteContext::setRouteAttributeName($originals['routeAttributeName']);
        RouteContext::setRouteParserAttributeName($originals['routeParserAttributeName']);
        RouteContext::setRoutingResultsAttributeName($originals['routingResultsAttributeName']);
        RouteContext::setBasePathAttributeName($originals['basePathAttributeName']);

        $this->assertEquals('##route##', $properties['routeAttributeName']);
        $this->assertEquals('##routeParser##', $properties['routeParserAttributeName']);
        $this->assertEquals('##routingResults##', $properties['routingResultsAttributeName']);
        $this->assertEquals('##basePath##', $properties['basePathAttributeName']);
    }

    public function testCanCreateInstanceFromServerRequest(): void
    {
        $route = $this->createMock(RouteInterface::class);
        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);

        $serverRequest = $this->createServerRequest('/')
                              ->withAttribute('__basePath__', '')
                              ->withAttribute('__route__', $route)
                              ->withAttribute('__routeParser__', $routeParser)
                              ->withAttribute('__routingResults__', $routingResults);

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
        $serverRequest = $serverRequest->withoutAttribute('__route__');

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
        $serverRequest = $serverRequest->withoutAttribute('__basePath__');

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
            ['__routeParser__'],
            ['__routingResults__'],
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
                    ->withAttribute('__basePath__', '')
                    ->withAttribute('__route__', $route)
                    ->withAttribute('__routeParser__', $routeParser)
                    ->withAttribute('__routingResults__', $routingResults);
    }
}
