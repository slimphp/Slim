<?php

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Slim\Builder\AppBuilder;
use Slim\Middleware\CorsMiddleware;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\RoutingMiddleware;

class CorsMiddlewareTest extends TestCase
{
    public function testDefaultConfiguration(): void
    {
        $app = (new AppBuilder())->build();

        // Add CORS middleware with default config
        $app->add(CorsMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Add test route
        $app->get('/test', function ($request, $response) {
            $response->getBody()->write('Test response');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertSame(
            'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            $response->getHeaderLine('Access-Control-Allow-Methods')
        );
        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'));
    }

    public function testDefaultConfigurationWithOrigin(): void
    {
        $app = (new AppBuilder())->build();

        // Add CORS middleware with default config
        $app->add(CorsMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Add test route
        $app->get('/test', function ($request, $response) {
            $response->getBody()->write('Test response');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test')
            ->withHeader('Origin', 'https://example.com');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertSame(
            'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            $response->getHeaderLine('Access-Control-Allow-Methods')
        );
        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'));
    }

    public function testPreflightRequest(): void
    {
        $app = (new AppBuilder())->build();

        // Configure CORS middleware
        $cors = $app->getContainer()
            ->get(CorsMiddleware::class)
            ->withAllowedOrigins(['https://example.com'])
            ->withAllowCredentials(true)
            ->withMaxAge(3600);

        $app->add($cors);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Add test routes
        $app->get('/test', function ($request, $response) {
            $response->getBody()->write('Test response');

            return $response;
        });

        $app->options('/test', function ($request, $response) {
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('OPTIONS', '/test')
            ->withHeader('Origin', 'https://example.com')
            ->withHeader('Access-Control-Request-Method', 'POST');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
        $this->assertSame('3600', $response->getHeaderLine('Access-Control-Max-Age'));
    }

    public function testDisallowedOrigin(): void
    {
        $app = (new AppBuilder())->build();

        // Configure CORS middleware
        $cors = $app->getContainer()
            ->get(CorsMiddleware::class)
           ->withAllowedOrigins(['https://example.com'])
                ->withAllowCredentials(true);

        $app->add($cors);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Add test route
        $app->get('/test', function ($request, $response) {
            $response->getBody()->write('Test response');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test')
            ->withHeader('Origin', 'https://bad-domain.tld');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'));
    }

    public function testCustomHeadersAndMethods(): void
    {
        $app = (new AppBuilder())->build();

        // Configure CORS middleware
        $cors = $app->getContainer()
            ->get(CorsMiddleware::class)
            ->withAllowedOrigins(['https://example.com'])
            ->withAllowedHeaders(['Content-Type', 'X-Custom-Header'])
            ->withExposedHeaders(['X-Custom-Response'])
            ->withAllowedMethods(['GET', 'POST'])
            ->withMaxAge(3600)
            ->withCache(false);

        $app->add($cors);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Add test routes
        $app->get('/test', function ($request, $response) {
            $response->getBody()->write('Test response');

            return $response;
        });

        $app->options('/test', function ($request, $response) {
            return $response;
        });

        // Test preflight request
        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('OPTIONS', '/test')
            ->withHeader('Origin', 'https://example.com')
            ->withHeader('Access-Control-Request-Method', 'POST')
            ->withHeader('Access-Control-Request-Headers', 'X-Custom-Header');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('Content-Type, X-Custom-Header', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertSame('GET, POST', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertSame('X-Custom-Response', $response->getHeaderLine('Access-Control-Expose-Headers'));
        $this->assertSame('3600', $response->getHeaderLine('Access-Control-Max-Age'));
        $this->assertFalse($response->hasHeader('Cache-Control'));
    }

    public function testWildcardOriginWithCredentials(): void
    {
        $app = (new AppBuilder())->build();

        // Configure CORS middleware
        $cors = $app->getContainer()
            ->get(CorsMiddleware::class)
            ->withAllowedOrigins(null)  // Wildcard
            ->withAllowCredentials(true);

        $app->add($cors);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        // Add test route
        $app->get('/test', function ($request, $response) {
            $response->getBody()->write('Test response');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/test')
            ->withHeader('Origin', 'https://example.com');

        $response = $app->handle($request);

        // Should use specific origin instead of wildcard when credentials are allowed
        $this->assertSame('https://example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
    }
}
