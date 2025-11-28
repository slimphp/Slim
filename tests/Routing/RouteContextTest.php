<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use RuntimeException;
use Slim\Factory\AppFactory;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

class RouteContextTest extends TestCase
{
    /**
     * Tests that a RouteContext instance is correctly created with all required attributes.
     * Verifies that URL generator, routing results, and base path are properly set.
     */
    public function testFromRequestCreatesInstanceWithValidAttributes(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $routingResults = new RoutingResults(200, null, 'GET', '/test', []);
        $basePath = '/base-path';

        $request = $request
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults)
            ->withAttribute(RouteContext::BASE_PATH, $basePath);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertSame($routingResults, $routeContext->getRoutingResults());
        $this->assertSame($basePath, $routeContext->getBasePath());
    }

    /**
     * Tests that an exception is thrown when attempting to create a RouteContext
     * without routing results attribute set in the request.
     */
    public function testFromRequestThrowsExceptionIfRoutingResultsAreMissing(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $request = $request
            ->withAttribute(RouteContext::BASE_PATH, '/base-path');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot create RouteContext before routing has been completed. Add RoutingMiddleware to fix this.'
        );

        RouteContext::fromRequest($request);
    }

    /**
     * Tests that the RoutingResults instance returned by getRoutingResults matches
     * the one originally provided in the request attributes.
     */
    public function testGetRoutingResultsReturnsCorrectInstance(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $routingResults = new RoutingResults(200, null, 'GET', '/test', []);

        $request = $request
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertSame($routingResults, $routeContext->getRoutingResults());
    }

    /**
     * Tests that the base path value returned by getBasePath matches
     * the one originally provided in the request attributes.
     */
    public function testGetBasePathReturnsCorrectValue(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $routingResults = new RoutingResults(200, null, 'GET', '/test', []);
        $basePath = '/base-path';

        $request = $request
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults)
            ->withAttribute(RouteContext::BASE_PATH, $basePath);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertSame($basePath, $routeContext->getBasePath());
    }

    /**
     * Tests that getBasePath returns null when no base path attribute
     * was set in the request.
     */
    public function testGetBasePathReturnsNullIfNotSet(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $routingResults = new RoutingResults(200, null, 'GET', '/test', []);

        $request = $request
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertNull($routeContext->getBasePath());
    }

    /**
     * Tests that getRoute() returns the correct Route instance when a route is matched
     */
    public function testGetRouteReturnsCorrectInstance(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        // Create a route for testing
        $route = $app->get('/test', function () {
        })->setName('test-route');
        $routingResults = new RoutingResults(200, $route, 'GET', '/test', []);

        $request = $request
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertInstanceOf(Route::class, $routeContext->getRoute());
        $this->assertSame($route, $routeContext->getRoute());
        $this->assertSame('test-route', $routeContext->getRoute()->getName());
    }

    /**
     * Tests that getRoute() returns null when no route is matched
     */
    public function testGetRouteReturnsNullWhenNoRouteMatched(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $routingResults = new RoutingResults(404, null, 'GET', '/not-found', []);

        $request = $request
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertNull($routeContext->getRoute());
    }

    /**
     * Tests that getArguments() returns all route arguments correctly
     */
    public function testGetArgumentsReturnsCorrectValues(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $arguments = ['id' => '123', 'name' => 'test'];
        $routingResults = new RoutingResults(200, null, 'GET', '/test', $arguments);

        $request = $request
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertSame($arguments, $routeContext->getArguments());
    }

    /**
     * Tests that getArgument() returns the correct value for a specific argument key
     */
    public function testGetArgumentReturnsCorrectValue(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $arguments = ['id' => '123', 'name' => 'test'];
        $routingResults = new RoutingResults(200, null, 'GET', '/test', $arguments);

        $request = $request
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertSame('123', $routeContext->getArgument('id'));
        $this->assertSame('test', $routeContext->getArgument('name'));
    }

    /**
     * Tests that getArgument() returns null when the requested key doesn't exist
     */
    public function testGetArgumentReturnsNullForNonExistentKey(): void
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $arguments = ['id' => '123'];
        $routingResults = new RoutingResults(200, null, 'GET', '/test', $arguments);

        $request = $request
            ->withAttribute(RouteContext::ROUTING_RESULTS, $routingResults);

        $routeContext = RouteContext::fromRequest($request);

        $this->assertNull($routeContext->getArgument('non-existent'));
    }
}
