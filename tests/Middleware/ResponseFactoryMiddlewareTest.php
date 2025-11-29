<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Slim\Factory\AppFactory;
use Slim\Middleware\ResponseFactoryMiddleware;

class ResponseFactoryMiddlewareTest extends TestCase
{
    public function testWithoutEndpointMiddleware(): void
    {
        $app = AppFactory::create();

        $app->add(function ($request, $handler) {
            $response = $handler->handle($request);
            $response->getBody()->write('Expected Response');

            return $response;
        });
        $app->add(ResponseFactoryMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Expected Response', (string)$response->getBody());
    }

    public function testProcessReturnsResponseFromFactory(): void
    {
        $app = AppFactory::create();

        $responseFactory = $app->getContainer()->get(ResponseFactoryInterface::class);

        // Create a response with a specific content
        $expectedResponse = $responseFactory->createResponse();
        $expectedResponse->getBody()->write('Expected Response');

        // Mock the ResponseFactoryInterface to always return the expected response
        $responseFactory = new class ($expectedResponse) implements ResponseFactoryInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function createResponse(int $status = 200, string $reasonPhrase = ''): ResponseInterface
            {
                return $this->response;
            }
        };

        $app->add(new ResponseFactoryMiddleware($responseFactory));

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Expected Response', (string)$response->getBody());
    }
}
