<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Middleware\FormUrlEncodedBodyParserMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;

final class FormUrlEncodedBodyParserMiddlewareTest extends TestCase
{
    public function testParsesValidFormData(): void
    {
        $body = 'foo=bar&baz=qux';
        $stream = (new StreamFactory())->createStream($body);

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($stream);

        $middleware = new FormUrlEncodedBodyParserMiddleware();
        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(
                ServerRequestInterface $request
            ): ResponseInterface {
                $parsed = $request->getParsedBody();
                $response = new Response();
                $response->getBody()->write($parsed['foo'] . ',' . $parsed['baz']);

                return $response;
            }
        });

        $this->assertSame('bar,qux', (string)$response->getBody());
    }

    public function testSkipsParsingForNonFormContentType(): void
    {
        $stream = (new StreamFactory())->createStream('foo=bar&baz=qux');

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'text/plain')
            ->withBody($stream);

        $middleware = new FormUrlEncodedBodyParserMiddleware();
        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(
                ServerRequestInterface $request
            ): ResponseInterface {
                $parsed = $request->getParsedBody();
                $response = new Response();
                $response->getBody()->write($parsed === null ? 'no-parse' : 'parsed');

                return $response;
            }
        });

        $this->assertSame('no-parse', (string)$response->getBody());
    }

    public function testSkipsParsingForEmptyBody(): void
    {
        $stream = (new StreamFactory())->createStream('');

        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($stream);

        $middleware = new FormUrlEncodedBodyParserMiddleware();
        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(
                ServerRequestInterface $request
            ): ResponseInterface {
                $parsed = $request->getParsedBody();
                $response = new Response();
                $response->getBody()->write($parsed === null ? 'empty' : 'not-empty');

                return $response;
            }
        });

        $this->assertSame('empty', (string)$response->getBody());
    }
}
