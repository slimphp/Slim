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
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Traits\AppTestTrait;

final class MethodOverrideMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function testHeader()
    {
        $app = AppFactory::create();

        $test = $this;
        $middleware = (function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($test) {
            $test->assertSame('PUT', $request->getMethod());

            return $handler->handle($request);
        });
        $methodOverrideMiddleware = new MethodOverrideMiddleware();

        $app->add($methodOverrideMiddleware);
        $app->add($middleware);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->put('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('X-Http-Method-Override', 'PUT');

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testBodyParam()
    {
        $app = AppFactory::create();

        $test = $this;
        $middleware = (function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($test) {
            $test->assertSame('PUT', $request->getMethod());

            return $handler->handle($request);
        });
        $methodOverrideMiddleware = new MethodOverrideMiddleware();

        $app->add($methodOverrideMiddleware);
        $app->add($middleware);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->put('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withParsedBody(['_METHOD' => 'PUT']);

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testHeaderPreferred()
    {
        $app = AppFactory::create();

        $test = $this;
        $middleware = (function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($test) {
            $test->assertSame('DELETE', $request->getMethod());

            return $handler->handle($request);
        });
        $methodOverrideMiddleware = new MethodOverrideMiddleware();

        $app->add($methodOverrideMiddleware);
        $app->add($middleware);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->delete('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('X-Http-Method-Override', 'DELETE')
            ->withParsedBody((object) ['_METHOD' => 'PUT']);

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testNoOverride()
    {
        $app = AppFactory::create();

        $test = $this;
        $middleware = (function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($test) {
            $test->assertSame('POST', $request->getMethod());

            return $handler->handle($request);
        });
        $methodOverrideMiddleware = new MethodOverrideMiddleware();

        $app->add($methodOverrideMiddleware);
        $app->add($middleware);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->post('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/');

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function testNoOverrideRewindEofBodyStream()
    {
        $app = AppFactory::create();

        $test = $this;
        $middleware = (function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($test) {
            $test->assertSame('POST', $request->getMethod());

            return $handler->handle($request);
        });
        $methodOverrideMiddleware = new MethodOverrideMiddleware();

        $app->add($methodOverrideMiddleware);
        $app->add($middleware);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->post('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        /** @var ServerRequestInterface $request */
        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/');

        $body = $this->createMock(StreamInterface::class);

        // Configuring the mock to return true for eof() and ensure rewind() is called
        $body->expects($this->once())
            ->method('eof')
            ->willReturn(true);

        $body->expects($this->once())
            ->method('rewind');

        $request = $request->withBody($body);

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string) $response->getBody());
    }
}
