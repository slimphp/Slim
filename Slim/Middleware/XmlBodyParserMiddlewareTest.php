<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Middleware\XmlBodyParserMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;

final class XmlBodyParserMiddlewareTest extends TestCase
{
    public function testParsesValidApplicationXml(): void
    {
        $xml = '<root><foo>bar</foo></root>';
        $stream = (new StreamFactory())->createStream($xml);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/xml')
            ->withBody($stream);

        $middleware = new XmlBodyParserMiddleware();

        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $parsed = $request->getParsedBody();
                $response = new Response();
                $response->getBody()->write((string)$parsed->foo);

                return $response;
            }
        });

        $this->assertSame('bar', (string)$response->getBody());
    }

    public function testParsesValidTextXml(): void
    {
        $xml = '<root><name>Test</name></root>';
        $stream = (new StreamFactory())->createStream($xml);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'text/xml')
            ->withBody($stream);

        $middleware = new XmlBodyParserMiddleware();

        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $parsed = $request->getParsedBody();
                $response = new Response();
                $response->getBody()->write((string)$parsed->name);

                return $response;
            }
        });

        $this->assertSame('Test', (string)$response->getBody());
    }

    public function testSkipsParsingForNonXmlContentType(): void
    {
        $stream = (new StreamFactory())->createStream('<root><x>y</x></root>');

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);

        $middleware = new XmlBodyParserMiddleware();

        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $parsed = $request->getParsedBody();
                $response = new Response();
                $response->getBody()->write($parsed === null ? 'no-parse' : 'parsed');

                return $response;
            }
        });

        $this->assertSame('no-parse', (string)$response->getBody());
    }

    public function testThrowsExceptionOnInvalidXml(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid XML body');

        $stream = (new StreamFactory())->createStream('<root><unclosed></root>');

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/xml')
            ->withBody($stream);

        $middleware = new XmlBodyParserMiddleware();

        $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        });
    }
}
