<?php

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Middleware\XmlExceptionMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class XmlExceptionMiddlewareTest extends TestCase
{
    public function testProcessHandlesRequestSuccessfully(): void
    {
        $middleware = new XmlExceptionMiddleware(new ResponseFactory());
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

    public function testProcessCatchesExceptionWithXmlAccept(): void
    {
        $middleware = (new XmlExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('application/xml');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/xml');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Something went wrong');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/xml', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('<message>Application Error</message>', (string)$response->getBody());
        $this->assertStringContainsString('<error>', (string)$response->getBody());
    }

    public function testWithErrorDetailsTrueIncludesExceptionDetails(): void
    {
        $middleware = (new XmlExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('application/xml')
            ->withErrorDetails(true);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/xml');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Detailed error');
            }
        };

        $response = $middleware->process($request, $handler);
        $body = (string)$response->getBody();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('<exception>', $body);
        $this->assertStringContainsString('<message>Detailed error</message>', $body);
        $this->assertStringContainsString('<type>RuntimeException</type>', $body);
        $this->assertStringContainsString('<code>0</code>', $body);
    }

    public function testWithErrorDetailsFalseOmitsExceptionDetails(): void
    {
        $middleware = (new XmlExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('application/xml')
            ->withErrorDetails(false);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/xml');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('Should not appear');
            }
        };

        $response = $middleware->process($request, $handler);
        $body = (string)$response->getBody();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('<message>Application Error</message>', $body);
        $this->assertStringNotContainsString('<exception>', $body);
    }

    public function testThrowsOriginalExceptionIfMediaTypeNotAccepted(): void
    {
        $middleware = new XmlExceptionMiddleware(new ResponseFactory());

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

    public function testWithMimeTypeSupportsAdditionalXmlTypes(): void
    {
        $middleware = (new XmlExceptionMiddleware(new ResponseFactory()))
            ->withMimeType('application/xml')
            ->withMimeType('application/xhtml+xml');

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/xhtml+xml');

        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new RuntimeException('XHTML error');
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('application/xhtml+xml', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('<message>Application Error</message>', (string)$response->getBody());
        $this->assertStringContainsString('<error>', (string)$response->getBody());
    }
}
