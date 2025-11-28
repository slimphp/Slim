<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\RoutingMiddleware;

class EndpointMiddlewareTest extends TestCase
{
    public function testProcessRouteFound(): void
    {
        $app = AppFactory::create();

        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Set up a route that will be found
        $app->get('/test', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Route found');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Route found', (string)$response->getBody());
    }

    public function testProcessRouteNotFound(): void
    {
        $this->expectException(HttpNotFoundException::class);

        $app = AppFactory::create();

        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/non-existent-route');

        $app->handle($request);
    }

    public function testProcessMethodNotAllowed(): void
    {
        $this->expectException(HttpMethodNotAllowedException::class);

        $app = AppFactory::create();

        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Set up a route with POST method only
        $app->post('/test', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test');

        $app->handle($request);
    }
}
