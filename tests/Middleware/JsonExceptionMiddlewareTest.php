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
use Slim\Middleware\JsonExceptionMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class JsonExceptionMiddlewareTest extends TestCase
{
    public function testProcessHandlesRequestSuccessfully(): void
    {
        $middleware = new JsonExceptionMiddleware(new ResponseFactory());
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

    public function testProcessCatchesExceptionWithJsonAccept(): void
    {
        $middleware = (new JsonExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('application/json')
            ->withErrorDetails(true);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Something went wrong');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertJson((string) $response->getBody());
        $this->assertStringContainsString('Something went wrong', (string) $response->getBody());
    }

    public function testProcessWithHttpMethodNotAllowedIncludesAllowHeader(): void
    {
        $middleware = (new JsonExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('application/json');

        $request = (new ServerRequestFactory())->createServerRequest('POST', '/')
            ->withHeader('Accept', 'application/json');

        $handler = new class ($request) implements RequestHandlerInterface {
            private $request;

            public function __construct($request)
            {
                $this->request = $request;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $exception = new HttpMethodNotAllowedException($this->request);
                $exception->setAllowedMethods(['GET', 'PATCH']);
                throw $exception;
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('GET, PATCH', $response->getHeaderLine('Allow'));
    }

    public function testWithErrorDetails(): void
    {
        $middleware = (new JsonExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('application/json')
            ->withErrorDetails(true);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Detailed error');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(500, $response->getStatusCode());

        $body = (string) $response->getBody();
        $this->assertJson($body);
        $this->assertStringContainsString('exception', $body);
        $this->assertStringContainsString('Application Error', $body);
        $this->assertStringContainsString('Detailed error', $body);

        $data = json_decode($body, true);

        $this->assertSame('Application Error', $data['message']);
        $this->assertArrayHasKey('exception', $data);
        $this->assertSame(1, count($data['exception']));
        $this->assertSame('Detailed error', $data['exception'][0]['message']);
        $this->assertSame(['type', 'code', 'message', 'file', 'line'], array_keys($data['exception'][0]));
    }

    public function testWithoutErrorDetails(): void
    {
        $middleware = (new JsonExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('application/json')
            ->withErrorDetails(false);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Hidden error');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertJson((string) $response->getBody());
        $this->assertStringNotContainsString('exception', (string) $response->getBody());
        $this->assertStringContainsString('Application Error', (string) $response->getBody());
        $this->assertStringNotContainsString('Hidden error', (string) $response->getBody());
    }

    public function testRethrowsExceptionWhenNoAcceptableContentTypeDetected(): void
    {
        $middleware = (new JsonExceptionMiddleware(new ResponseFactory()));

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/unsupported-type');

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

    public function testWithMimeTypeSupportsAdditionalJsonTypes(): void
    {
        $middleware = (new JsonExceptionMiddleware(new ResponseFactory()))
            ->withErrorDetails(true)
            ->withMimeType('application/vnd.api+json')
            ->withMimeType('application/ld+json');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/vnd.api+json');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Test message');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/vnd.api+json', $response->getHeaderLine('Content-Type'));
        $this->assertJson((string) $response->getBody());
        $this->assertStringContainsString('Test message', (string) $response->getBody());
    }

    public function testWithJsonOptionsChangesEncoding(): void
    {
        $middleware = (new JsonExceptionMiddleware(new ResponseFactory()))
            ->withErrorDetails(true)
            ->withMimeType('application/json')
            ->withJsonOptions(JSON_HEX_TAG);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('<script>');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertJson((string) $response->getBody());
        $this->assertStringContainsString('\u003Cscript\u003E', (string) $response->getBody());
    }
}
