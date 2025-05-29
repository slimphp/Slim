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
use Slim\Middleware\JsonBodyParserMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;

final class JsonBodyParserMiddlewareTest extends TestCase
{
    public function testParsesValidJson(): void
    {
        $stream = (new StreamFactory())->createStream('{"foo":"bar"}');

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);

        $middleware = new JsonBodyParserMiddleware();

        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $data = $request->getParsedBody();
                $response = new Response();
                $response->getBody()->write(json_encode($data));

                return $response;
            }
        });

        $this->assertSame('{"foo":"bar"}', (string)$response->getBody());
    }

    public function testParsesStructuredJsonType(): void
    {
        $stream = (new StreamFactory())->createStream('{"hello":"world"}');

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/vnd.api+json')
            ->withBody($stream);

        $middleware = new JsonBodyParserMiddleware();

        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $data = $request->getParsedBody();
                $response = new Response();
                $response->getBody()->write(json_encode($data));

                return $response;
            }
        });

        $this->assertSame('{"hello":"world"}', (string)$response->getBody());
    }

    public function testThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON body');

        $stream = (new StreamFactory())->createStream('{"foo": "bar"');

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);

        $middleware = new JsonBodyParserMiddleware();

        $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        });
    }

    public function testSkipsParsingForNonJsonContentType(): void
    {
        $stream = (new StreamFactory())->createStream('{"foo":"bar"}');

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'text/plain')
            ->withBody($stream);

        $middleware = new JsonBodyParserMiddleware();

        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = new Response();
                $parsedBody = $request->getParsedBody();
                $response->getBody()->write($parsedBody === null ? 'no-parse' : 'parsed');

                return $response;
            }
        });

        $this->assertSame('no-parse', (string)$response->getBody());
    }
}
