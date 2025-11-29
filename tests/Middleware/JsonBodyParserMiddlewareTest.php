<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Middleware\JsonBodyParserMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;

final class JsonBodyParserMiddlewareTest extends TestCase
{
    #[DataProvider('validJsonProvider')]
    public function testParsesValidJson($contentType, $body, $expected): void
    {
        $stream = (new StreamFactory())->createStream($body);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', $contentType)
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

        $this->assertSame($expected, (string) $response->getBody());
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

        $this->assertSame('{"hello":"world"}', (string) $response->getBody());
    }

    #[DataProvider('invalidJsonProvider')]
    public function testThrowsExceptionOnInvalidJson($contentType, $body): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');

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

        $this->assertSame('no-parse', (string) $response->getBody());
    }

    public static function validJsonProvider(): array
    {
        return [
            'json' => [
                'application/json',
                '{"foo":"bar"}',
                '{"foo":"bar"}',
            ],
            'json-with-charset' => [
                "application/json\t ; charset=utf8",
                '{"foo":"bar"}',
                '{"foo":"bar"}',
            ],
            'json-suffix' => [
                'application/vnd.api+json;charset=utf8',
                '{"foo":"bar"}',
                '{"foo":"bar"}',
            ],
            'valid-json-but-not-an-array' => [
                'application/json;charset=utf8',
                '"foo bar"',
                'null',
            ],
            'empty-object' => [
                'application/json',
                '{}',
                '[]',
            ],
            'unknown-contenttype' => [
                'text/foo+bar',
                '"foo bar"',
                'null',
            ],
            'empty-contenttype' => [
                '',
                '"foo bar"',
                'null',
            ],
            // null is not supported anymore
            // Header values must be RFC 7230 compatible strings.
            /* 'no-contenttype' => [
                null,
                '"foo bar"',
                'null',
            ],*/
            'json-null' => [
                'application/json',
                'null',
                'null',
            ],
            'json-false' => [
                'application/json',
                'false',
                'null',
            ],
            'invalid-contenttype' => [
                'foo',
                '"foo bar"',
                'null',
            ],
        ];
    }

    public static function invalidJsonProvider(): array
    {
        return [
            'invalid-json' => [
                'application/json',
                '{"foo": "bar"',
            ],
            'invalid-json-empty-string' => [
                'application/json',
                '',
            ],
        ];
    }
}
