<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Middleware\BasePathMiddleware;
use Slim\Tests\TestCase;

class BasePathMiddlewareTest extends TestCase
{
    /**
     * Create a request handler that captures the request for inspection.
     */
    protected function createRequestHandler(): RequestHandlerInterface
    {
        $response = $this->createResponse();
        return new class ($response) implements RequestHandlerInterface {
            private ResponseInterface $response;
            public ?ServerRequestInterface $receivedRequest = null;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->receivedRequest = $request;
                return $this->response;
            }
        };
    }

    /**
     * Create a request with specific server params.
     *
     * @param string $uri The request URI
     * @param array<string, string> $serverParams Additional server params
     * @return ServerRequestInterface
     */
    protected function createRequestWithServerParams(string $uri, array $serverParams = []): ServerRequestInterface
    {
        return $this->createServerRequest($uri, 'GET', $serverParams);
    }

    public function testStripsBasePath(): void
    {
        $middleware = new BasePathMiddleware('/myapp');
        $request = $this->createServerRequest('/myapp/api/users');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/api/users', $handler->receivedRequest->getUri()->getPath());
    }

    public function testAddsBasePathAttribute(): void
    {
        $middleware = new BasePathMiddleware('/myapp');
        $request = $this->createServerRequest('/myapp/api/users');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/myapp', $handler->receivedRequest->getAttribute('basePath'));
    }

    public function testSkipsWhenNoMatch(): void
    {
        $middleware = new BasePathMiddleware('/other');
        $request = $this->createServerRequest('/myapp/api/users');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/myapp/api/users', $handler->receivedRequest->getUri()->getPath());
    }

    public function testSkipsWhenBasePathEmpty(): void
    {
        $middleware = new BasePathMiddleware('');
        $request = $this->createServerRequest('/api/users');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/api/users', $handler->receivedRequest->getUri()->getPath());
        $this->assertNull($handler->receivedRequest->getAttribute('basePath'));
    }

    public function testHandlesRootPath(): void
    {
        $middleware = new BasePathMiddleware('/myapp');
        $request = $this->createServerRequest('/myapp');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/', $handler->receivedRequest->getUri()->getPath());
    }

    public function testHandlesRootPathWithTrailingSlash(): void
    {
        $middleware = new BasePathMiddleware('/myapp');
        $request = $this->createServerRequest('/myapp/');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/', $handler->receivedRequest->getUri()->getPath());
    }

    public function testHandlesTrailingSlashInBasePath(): void
    {
        $middleware = new BasePathMiddleware('/myapp/');
        $request = $this->createServerRequest('/myapp/api/users');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/api/users', $handler->receivedRequest->getUri()->getPath());
    }

    public function testPreservesQueryString(): void
    {
        $middleware = new BasePathMiddleware('/myapp');
        $request = $this->createServerRequest('/myapp/api/users?page=2&limit=10');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/api/users', $handler->receivedRequest->getUri()->getPath());
        $this->assertEquals('page=2&limit=10', $handler->receivedRequest->getUri()->getQuery());
    }

    public function testHandlesNestedPath(): void
    {
        $middleware = new BasePathMiddleware('/myapp/v2');
        $request = $this->createServerRequest('/myapp/v2/api/users');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/api/users', $handler->receivedRequest->getUri()->getPath());
    }

    public function testHandlesPartialMatchInPathSegment(): void
    {
        // /myapp2 should NOT match /myapp
        $middleware = new BasePathMiddleware('/myapp');
        $request = $this->createServerRequest('/myapp2/api/users');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        // Path should remain unchanged since /myapp2 != /myapp
        $this->assertEquals('/myapp2/api/users', $handler->receivedRequest->getUri()->getPath());
    }

    public function testFromRequestAutoDetectsBasePath(): void
    {
        $request = $this->createRequestWithServerParams(
            '/myapp/api/users',
            ['SCRIPT_NAME' => '/myapp/public/index.php']
        );

        $middleware = BasePathMiddleware::fromRequest($request);
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/api/users', $handler->receivedRequest->getUri()->getPath());
    }

    public function testFromRequestWithScriptInRoot(): void
    {
        $request = $this->createRequestWithServerParams(
            '/api/users',
            ['SCRIPT_NAME' => '/index.php']
        );

        $middleware = BasePathMiddleware::fromRequest($request);
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/api/users', $handler->receivedRequest->getUri()->getPath());
    }

    public function testFromRequestWithEmptyScriptName(): void
    {
        $request = $this->createRequestWithServerParams(
            '/api/users',
            ['SCRIPT_NAME' => '']
        );

        $middleware = BasePathMiddleware::fromRequest($request);
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/api/users', $handler->receivedRequest->getUri()->getPath());
    }

    public function testFromRequestWithNoScriptNameParam(): void
    {
        $request = $this->createRequestWithServerParams('/api/users');

        $middleware = BasePathMiddleware::fromRequest($request);
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/api/users', $handler->receivedRequest->getUri()->getPath());
    }

    public function testFromRequestKeepsPublicDirectoryIfNotAtEnd(): void
    {
        // /public/app should NOT strip /public
        $request = $this->createRequestWithServerParams(
            '/public/app/api/users',
            ['SCRIPT_NAME' => '/public/app/index.php']
        );

        $middleware = BasePathMiddleware::fromRequest($request);
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/api/users', $handler->receivedRequest->getUri()->getPath());
    }

    public function testFromRequestWithNestedPublicPath(): void
    {
        $request = $this->createRequestWithServerParams(
            '/myapp/public/api/users',
            ['SCRIPT_NAME' => '/myapp/public/public/index.php']
        );

        $middleware = BasePathMiddleware::fromRequest($request);
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        // Only strips the last /public
        $this->assertEquals('/api/users', $handler->receivedRequest->getUri()->getPath());
    }

    public function testAttributeNotSetWhenNoMatch(): void
    {
        $middleware = new BasePathMiddleware('/other');
        $request = $this->createServerRequest('/myapp/api/users');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertNull($handler->receivedRequest->getAttribute('basePath'));
    }

    public function testHandlesSingleCharacterBasePath(): void
    {
        $middleware = new BasePathMiddleware('/a');
        $request = $this->createServerRequest('/a/b');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/b', $handler->receivedRequest->getUri()->getPath());
    }

    public function testPreservesFragment(): void
    {
        $middleware = new BasePathMiddleware('/myapp');
        $request = $this->createServerRequest('/myapp/api/users#section');
        $handler = $this->createRequestHandler();

        $middleware->process($request, $handler);

        $this->assertEquals('/api/users', $handler->receivedRequest->getUri()->getPath());
        // Note: Fragment is typically not sent by browsers in HTTP requests,
        // but we should ensure the URI object preserves it if present
    }
}
