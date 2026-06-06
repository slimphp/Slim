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
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
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
        $app->addRoutingMiddleware();

        $app->put('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('X-Http-Method-Override', 'PUT');

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
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
        $app->addRoutingMiddleware();

        $app->put('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withParsedBody(['_METHOD' => 'PUT']);

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
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
        $app->addRoutingMiddleware();

        $app->delete('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('X-Http-Method-Override', 'DELETE');

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    public function testHeaderOverrideWithArbitraryValueIsIgnored(): void
    {
        $this->expectException(HttpMethodNotAllowedException::class);
        $this->expectExceptionMessage('Method not allowed.');

        $app = AppFactory::create();
        $app->add(MethodOverrideMiddleware::class);
        $app->addRoutingMiddleware();

        $app->delete('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write($request->getMethod());

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('X-Http-Method-Override', 'FAKEMETHOD');

        $app->handle($request);
    }

    public function testHeaderOverrideOnNonPostRequestIsIgnored(): void
    {
        $this->expectException(HttpMethodNotAllowedException::class);
        $this->expectExceptionMessage('Method not allowed.');

        $app = AppFactory::create();
        $app->add(MethodOverrideMiddleware::class);
        $app->addRoutingMiddleware();

        $app->delete('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write($request->getMethod());

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('X-Http-Method-Override', 'DELETE');

        $app->handle($request);
    }

    public function testHeaderOverrideWithArbitraryValueInPayload(): void
    {
        $this->expectException(HttpMethodNotAllowedException::class);
        $this->expectExceptionMessage('Method not allowed.');

        $app = AppFactory::create();
        $app->add(MethodOverrideMiddleware::class);
        $app->addRoutingMiddleware();

        $app->delete('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write($request->getMethod());

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withParsedBody(['_METHOD' => 'FAKEMETHOD']);

        $app->handle($request);
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
        $app->addRoutingMiddleware();

        $app->post('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/');

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
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
        $app->addRoutingMiddleware();

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

        $this->assertSame('Hello World', (string)$response->getBody());
    }
}
