<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Builder\AppBuilder;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Routing\Route;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

class RoutingResultsTest extends TestCase
{
    public function testConstructAndGetters(): void
    {
        $route = new Route(['GET'], '/test', function () {
        });

        // Define test parameters
        $status = RoutingResults::FOUND;
        $method = 'GET';
        $uri = '/test';
        $routeArguments = ['arg1' => 'value1'];
        $allowedMethods = ['GET', 'POST'];

        // Create RoutingResults instance
        $routingResults = new RoutingResults(
            $status,
            $route,
            $method,
            $uri,
            $routeArguments,
            $allowedMethods
        );

        $this->assertSame($status, $routingResults->getRouteStatus());
        $this->assertSame($route, $routingResults->getRoute());
        $this->assertSame($method, $routingResults->getMethod());
        $this->assertSame($uri, $routingResults->getUri());
        $this->assertSame('value1', $routingResults->getRouteArgument('arg1'));
        $this->assertSame(null, $routingResults->getRouteArgument('nada'));
        $this->assertSame($routeArguments, $routingResults->getRouteArguments());
        $this->assertSame($allowedMethods, $routingResults->getAllowedMethods());
    }

    public function testGettersWithNullRoute(): void
    {
        // Define test parameters with null route
        $status = RoutingResults::NOT_FOUND;
        $method = 'POST';
        $uri = '/not-found';
        $routeArguments = [];
        $allowedMethods = ['GET'];

        // Create RoutingResults instance with null route
        $routingResults = new RoutingResults(
            $status,
            null,
            $method,
            $uri,
            $routeArguments,
            $allowedMethods
        );

        $this->assertSame($status, $routingResults->getRouteStatus());
        $this->assertNull($routingResults->getRoute());
        $this->assertSame($method, $routingResults->getMethod());
        $this->assertSame($uri, $routingResults->getUri());
        $this->assertSame($routeArguments, $routingResults->getRouteArguments());
        $this->assertSame($allowedMethods, $routingResults->getAllowedMethods());
    }

    public function testRoutingArgumentsFromRouteContext(): void
    {
        $app = (new AppBuilder())->build();

        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Define a route with arguments
        $app->get('/test/{id}', function (ServerRequestInterface $request, ResponseInterface $response) {
            $args = RouteContext::fromRequest($request)->getRoutingResults()->getRouteArguments();
            $response->getBody()->write('ID: ' . $args['id']);

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test/123');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ID: 123', (string)$response->getBody());
    }
}
