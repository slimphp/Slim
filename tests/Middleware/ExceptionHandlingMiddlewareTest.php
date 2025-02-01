<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\Error\Renderers\HtmlExceptionRenderer;
use Slim\Error\Renderers\JsonExceptionRenderer;
use Slim\Error\Renderers\XmlExceptionRenderer;
use Slim\Media\MediaType;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\ExceptionHandlingMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Traits\AppTestTrait;

final class ExceptionHandlingMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function testDefaultHandlerWithoutDetails(): void
    {
        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                ExceptionHandlingMiddleware::class => function ($container) {
                    $middleware = ExceptionHandlingMiddleware::createFromContainer($container);

                    return $middleware
                        ->withDisplayErrorDetails(false)
                        ->withDefaultHandler(HtmlExceptionRenderer::class);
                },
            ]
        );
        $app = $builder->build();

        $app->add(ExceptionHandlingMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function () {
            throw new RuntimeException('Test error');
        });

        $response = $app->handle($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertStringNotContainsString('Test Error message', (string)$response->getBody());
        $this->assertStringContainsString('<h1>Application Error</h1>', (string)$response->getBody());
    }

    public function testDefaultHandlerWithDetails(): void
    {
        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                ExceptionHandlingMiddleware::class => function ($container) {
                    $middleware = ExceptionHandlingMiddleware::createFromContainer($container);

                    return $middleware
                        ->withDisplayErrorDetails(true)
                        ->withDefaultHandler(HtmlExceptionRenderer::class);
                },
            ]
        );
        $app = $builder->build();

        $app->add(ExceptionHandlingMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function () {
            throw new RuntimeException('Test error', 123);
        });

        $response = $app->handle($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/html', (string)$response->getHeaderLine('Content-Type'));
        $this->assertStringNotContainsString('Test Error message', (string)$response->getBody());
        $this->assertStringContainsString('<h1>Application Error</h1>', (string)$response->getBody());
    }

    public function testDefaultHtmlMediaTypeWithDetails(): void
    {
        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                ExceptionHandlingMiddleware::class => function ($container) {
                    $middleware = ExceptionHandlingMiddleware::createFromContainer($container);

                    return $middleware
                        ->withDisplayErrorDetails(true)
                        ->withDefaultMediaType(MediaType::TEXT_HTML)
                        ->withHandler(MediaType::TEXT_HTML, HtmlExceptionRenderer::class);
                },
            ]
        );
        $app = $builder->build();

        $app->add(ExceptionHandlingMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');

        $app->get('/', function () {
            throw new RuntimeException('Test error', 123);
        });

        $response = $app->handle($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/html', (string)$response->getHeaderLine('Content-Type'));
        $this->assertStringNotContainsString('Test Error message', (string)$response->getBody());
        $this->assertStringContainsString('<h1>Application Error</h1>', (string)$response->getBody());
    }

    public function testJsonMediaTypeDisplayErrorDetails(): void
    {
        $builder = new AppBuilder();

        $builder->addDefinitions(
            [
                ExceptionHandlingMiddleware::class => function ($container) {
                    $middleware = ExceptionHandlingMiddleware::createFromContainer($container);

                    return $middleware
                        ->withDisplayErrorDetails(true)
                        ->withHandler(MediaType::APPLICATION_JSON, JsonExceptionRenderer::class);
                },
            ]
        );

        $app = $builder->build();

        $app->add(ExceptionHandlingMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');

        $app->get('/', function () {
            throw new RuntimeException('Test error', 123);
        });

        $response = $app->handle($request);

        $actual = json_decode((string)$response->getBody(), true);
        $this->assertSame('Application Error', $actual['message']);
        $this->assertSame(1, count($actual['exception']));
        $this->assertSame('RuntimeException', $actual['exception'][0]['type']);
        $this->assertSame(123, $actual['exception'][0]['code']);
        $this->assertSame('Test error', $actual['exception'][0]['message']);
    }

    public function testWithoutHandler(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Exception handler for "text/html" not found');

        $builder = new AppBuilder();
        $app = $builder->build();

        $app->add(ExceptionHandlingMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->get('/', function () {
            throw new RuntimeException('Test error', 123);
        });

        $app->handle($request);
    }

    #[DataProvider('textHmlHeaderProvider')]
    public function testWithTextHtml(string $header, string $headerValue): void
    {
        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                ExceptionHandlingMiddleware::class => function ($container) {
                    $middleware = ExceptionHandlingMiddleware::createFromContainer($container);

                    return $middleware
                        ->withDisplayErrorDetails(true)
                        ->withHandler(MediaType::TEXT_HTML, HtmlExceptionRenderer::class);
                },
            ]
        );
        $app = $builder->build();

        $app->add(ExceptionHandlingMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get('/', function () {
            throw new RuntimeException('Test Error message');
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader($header, $headerValue);

        $response = $app->handle($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/html', (string)$response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Test Error message', (string)$response->getBody());
    }

    public static function textHmlHeaderProvider(): array
    {
        return [
            ['Accept', 'text/html'],
            ['Accept', 'text/html, application/xhtml+xml, application/xml;q=0.9, image/webp, */*;q=0.8'],
            ['Content-Type', 'text/html'],
            ['Content-Type', 'text/html; charset=utf-8'],
        ];
    }

    // todo: Add test for other media types

    public function testWithAcceptJson(): void
    {
        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                ExceptionHandlingMiddleware::class => function ($container) {
                    $middleware = ExceptionHandlingMiddleware::createFromContainer($container);

                    return $middleware
                        ->withDisplayErrorDetails(false)
                        ->withHandler(MediaType::APPLICATION_JSON, JsonExceptionRenderer::class);
                },
            ]
        );
        $app = $builder->build();

        $app->add(ExceptionHandlingMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('Accept', 'application/json');

        $app->get('/', function () {
            throw new RuntimeException('Test exception');
        });

        $response = $app->handle($request);

        $this->assertSame(500, $response->getStatusCode());
        $expected = [
            'message' => 'Application Error',
        ];
        $this->assertJsonResponse($expected, $response);
    }

    public static function xmlHeaderProvider(): array
    {
        return [
            ['Accept', 'application/xml'],
            ['Accept', 'application/xml, application/json'],
            ['Content-Type', 'application/xml'],
            ['Content-Type', 'application/xml; charset=utf-8'],
        ];
    }

    #[DataProvider('xmlHeaderProvider')]
    public function testWithAcceptXml(string $header, string $headerValue): void
    {
        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                ExceptionHandlingMiddleware::class => function ($container) {
                    $middleware = ExceptionHandlingMiddleware::createFromContainer($container);

                    return $middleware->withDisplayErrorDetails(false)
                        ->withoutHandlers()
                        ->withHandler('application/json', JsonExceptionRenderer::class)
                        ->withHandler('application/xml', XmlExceptionRenderer::class);
                },
            ]
        );
        $app = $builder->build();

        $app->add(ExceptionHandlingMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader($header, $headerValue);

        $app->get('/', function () {
            throw new RuntimeException('Test exception');
        });

        $response = $app->handle($request);

        $this->assertSame(500, $response->getStatusCode());
        $expected = '<?xml version="1.0" encoding="UTF-8"?>
                    <error>
                      <message>Application Error</message>
                    </error>';

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($expected);
        $expected = $dom->saveXML();

        $dom2 = new DOMDocument();
        $dom2->preserveWhiteSpace = false;
        $dom2->formatOutput = true;
        $dom2->loadXML((string)$response->getBody());
        $actual = $dom2->saveXML();

        $this->assertSame($expected, $actual);
    }
}
