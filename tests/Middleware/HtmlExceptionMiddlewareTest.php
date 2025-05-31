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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Middleware\HtmlExceptionMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class HtmlExceptionMiddlewareTest extends TestCase
{
    public function testProcessHandlesRequestSuccessfully(): void
    {
        $middleware = new HtmlExceptionMiddleware(new ResponseFactory());
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new ResponseFactory())->createResponse();
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessCatchesGenericExceptionWithHtmlAccept(): void
    {
        $middleware = (new HtmlExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('text/html');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'text/html');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Generic failure');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Application Error', (string)$response->getBody());
    }

    public function testProcessWithHttpMethodNotAllowedAddsAllowHeader(): void
    {
        $middleware = (new HtmlExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('text/html');

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/')
            ->withHeader('Accept', 'text/html');

        $handler = new class ($request) implements RequestHandlerInterface {
            private $request;

            public function __construct($request)
            {
                $this->request = $request;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $exception = new HttpMethodNotAllowedException($this->request);
                $exception->setAllowedMethods(['GET', 'PUT']);

                throw $exception;
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('GET, PUT', $response->getHeaderLine('Allow'));
    }

    public function testProcessThrowsOriginalExceptionIfMediaTypeNotAccepted(): void
    {
        $middleware = new HtmlExceptionMiddleware(new ResponseFactory());

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/xml');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Unsupported type');
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported type');

        $middleware->process($request, $handler);
    }
}
