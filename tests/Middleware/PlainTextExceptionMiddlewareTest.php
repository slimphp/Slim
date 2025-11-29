<?php

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Middleware\PlainTextExceptionMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class PlainTextExceptionMiddlewareTest extends TestCase
{
    public function testProcessHandlesRequestSuccessfully(): void
    {
        $middleware = new PlainTextExceptionMiddleware(new ResponseFactory());
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

    public function testProcessCatchesExceptionWithTextPlainAccept(): void
    {
        $middleware = (new PlainTextExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('text/plain');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'text/plain');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Something went wrong');
            }
        };

        $response = $middleware->process($request, $handler);
        $body = (string) $response->getBody();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Application Error', $body);
        $this->assertStringNotContainsString('Something went wrong', $body);
    }

    public function testWithErrorDetailsTrueIncludesExceptionDetails(): void
    {
        $middleware = (new PlainTextExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('text/plain')
            ->withErrorDetails(true);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'text/plain');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Detailed error');
            }
        };

        $response = $middleware->process($request, $handler);
        $body = (string) $response->getBody();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Application Error', $body);
        $this->assertStringContainsString('Detailed error', $body);
        $this->assertStringContainsString('Type: RuntimeException', $body);
        $this->assertStringContainsString('Trace:', $body);
    }

    public function testWithErrorDetailsFalseOmitsExceptionDetails(): void
    {
        $middleware = (new PlainTextExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('text/plain')
            ->withErrorDetails(false);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'text/plain');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Hidden error');
            }
        };

        $response = $middleware->process($request, $handler);
        $body = (string) $response->getBody();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Application Error', $body);
        $this->assertStringNotContainsString('Hidden error', $body);
        $this->assertStringNotContainsString('Type:', $body);
    }

    public function testThrowsOriginalExceptionIfMediaTypeNotAccepted(): void
    {
        $middleware = new PlainTextExceptionMiddleware(new ResponseFactory());

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('This should be rethrown');
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This should be rethrown');

        $middleware->process($request, $handler);
    }

    public function testWithMimeTypeSupportsAdditionalTextTypes(): void
    {
        $middleware = (new PlainTextExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('text/plain')
            ->withMimeType('text/custom');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'text/custom');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Test error message');
            }
        };

        $response = $middleware->process($request, $handler);
        $body = (string) $response->getBody();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/custom', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Application Error', $body);
    }

    public function testIncludesPreviousExceptionDetails(): void
    {
        $previous = new RuntimeException('Inner exception');
        $exception = new RuntimeException('Outer exception', 0, $previous);

        $middleware = (new PlainTextExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('text/plain')
            ->withErrorDetails(true);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'text/plain');

        $handler = new class ($exception) implements RequestHandlerInterface {
            private $exception;

            public function __construct(RuntimeException $exception)
            {
                $this->exception = $exception;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw $this->exception;
            }
        };

        $response = $middleware->process($request, $handler);
        $body = (string) $response->getBody();

        $this->assertStringContainsString('Outer exception', $body);
        $this->assertStringContainsString('Previous Exception:', $body);
        $this->assertStringContainsString('Inner exception', $body);
    }
}
