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
use Slim\Factory\AppFactory;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\RoutingArgumentsMiddleware;
use Slim\Middleware\RoutingMiddleware;

class RoutingArgumentsMiddlewareTest extends TestCase
{
    public function testProcessAddsRoutingArgumentsToRequestAttributes(): void
    {
        $app = AppFactory::create();

        $app->add(RoutingMiddleware::class);
        $app->add(RoutingArgumentsMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Define a route with arguments
        $app->get('/test/{id}', function (ServerRequestInterface $request, ResponseInterface $response) {
            // Verify that the 'id' attribute has been added to the request
            $id = $request->getAttribute('id');
            $response->getBody()->write("ID: $id");

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test/123');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ID: 123', (string)$response->getBody());
    }

    public function testProcessNoRoutingArguments(): void
    {
        $app = AppFactory::create();

        $app->add(RoutingArgumentsMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Define a route without any arguments
        $app->get('/no-args', function (ServerRequestInterface $request, ResponseInterface $response) {
            $id = $request->getAttribute('id') ?? 'No arguments';
            $response->getBody()->write("ID: $id");

            return $response;
        });

        // Create a server request without any routing arguments
        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/no-args');

        $response = $app->handle($request);

        // Assertions
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ID: No arguments', (string)$response->getBody());
    }
}
