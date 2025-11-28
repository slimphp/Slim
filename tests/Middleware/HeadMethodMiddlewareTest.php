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
use Slim\Middleware\HeadMethodMiddleware;
use Slim\Middleware\RoutingMiddleware;

class HeadMethodMiddlewareTest extends TestCase
{
    public function testHeadRequestResponseBodyIsEmpty(): void
    {
        $app = AppFactory::create();

        $app->add(HeadMethodMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Set up a route that returns a non-empty body
        $app->get('/test', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('This is the body content');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('HEAD', '/test');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', (string)$response->getBody());
    }

    public function testGetRequestResponseBodyIsUnchanged(): void
    {
        $app = AppFactory::create();

        $app->add(HeadMethodMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Set up a route that returns a non-empty body
        $app->get('/test', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('This is the body content');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('This is the body content', (string)$response->getBody());
    }
}
