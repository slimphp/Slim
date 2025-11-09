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
use RuntimeException;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Tests\TestCase;

use function is_string;
use function simplexml_load_string;

class BodyParsingMiddlewareTest extends TestCase
{
    /**
     * Create a request handler that simply assigns the $request that it receives to a public property
     * of the returned response, so that we can then inspect that request.
     */
    protected function createRequestHandler(): RequestHandlerInterface
    {
        $response = $this->createResponse();
        return new class ($response) implements RequestHandlerInterface {
            private $response;
            public $request;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;
                return $this->response;
            }
        };
    }

    /**
     * @param string $contentType
     * @param string $body
     * @return ServerRequestInterface
     */
    protected function createRequestWithBody($contentType, $body)
    {
        $request = $this->createServerRequest('/', 'POST');
        if (is_string($contentType)) {
            $request = $request->withHeader('Content-Type', $contentType);
        }
        if (is_string($body)) {
            $request = $request->withBody($this->createStream($body));
        }
        return $request;
    }


    public static function parsingProvider()
    {
        return [
            'form' => [
                'application/x-www-form-urlencoded;charset=utf8',
                'foo=bar',
                ['foo' => 'bar'],
            ],
            'json' => [
                "application/json",
                '{"foo":"bar"}',
                ['foo' => 'bar'],
            ],
            'json-with-charset' => [
                "application/json\t ; charset=utf8",
                '{"foo":"bar"}',
                ['foo' => 'bar'],
            ],
            'json-suffix' => [
                'application/vnd.api+json;charset=utf8',
                '{"foo":"bar"}',
                ['foo' => 'bar'],
            ],
            'xml' => [
                'application/xml',
                '<person><name>John</name></person>',
                simplexml_load_string('<person><name>John</name></person>'),
            ],
            'xml-suffix' => [
                'application/hal+xml;charset=utf8',
                '<person><name>John</name></person>',
                simplexml_load_string('<person><name>John</name></person>'),
            ],
            'text-xml' => [
                'text/xml',
                '<person><name>John</name></person>',
                simplexml_load_string('<person><name>John</name></person>'),
            ],
            'invalid-json' => [
                'application/json;charset=utf8',
                '{"foo"}/bar',
                null,
            ],
            'valid-json-but-not-an-array' => [
                'application/json;charset=utf8',
                '"foo bar"',
                null,
            ],
            'unknown-contenttype' => [
                'text/foo+bar',
                '"foo bar"',
                null,
            ],
            'empty-contenttype' => [
                '',
                '"foo bar"',
                null,
            ],
            'no-contenttype' => [
                null,
                '"foo bar"',
                null,
            ],
            'invalid-contenttype' => [
                'foo',
                '"foo bar"',
                null,
            ],
            'invalid-xml' => [
                'application/xml',
                '<person><name>John</name></invalid>',
                null,
            ],
            'invalid-textxml' => [
                'text/xml',
                '<person><name>John</name></invalid>',
                null,
            ],
        ];
    }

    /**
     * @dataProvider parsingProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('parsingProvider')]
    public function testParsing($contentType, $body, $expected)
    {
        $request = $this->createRequestWithBody($contentType, $body);

        $middleware = new BodyParsingMiddleware();
        $requestHandler = $this->createRequestHandler();
        $middleware->process($request, $requestHandler);

        $this->assertEquals($expected, $requestHandler->request->getParsedBody());
    }

    public function testParsingWithARegisteredParser()
    {
        $request = $this->createRequestWithBody('application/vnd.api+json', '{"foo":"bar"}');

        $parsers = [
            'application/vnd.api+json' => function ($input) {
                return ['data' => $input];
            },
        ];
        $middleware = new BodyParsingMiddleware($parsers);
        $requestHandler = $this->createRequestHandler();
        $middleware->process($request, $requestHandler);

        $this->assertSame(['data' => '{"foo":"bar"}'], $requestHandler->request->getParsedBody());
    }

    public function testParsingFailsWhenAnInvalidTypeIsReturned()
    {
        $request = $this->createRequestWithBody('application/json;charset=utf8', '{"foo":"bar"}');

        $parsers = [
            'application/json' => function ($input) {
                return 10; // invalid - should return null, array or object
            },
        ];
        $middleware = new BodyParsingMiddleware($parsers);

        $this->expectException(RuntimeException::class);
        $middleware->process($request, $this->createRequestHandler());
    }

    public function testSettingAndGettingAParser()
    {
        $middleware = new BodyParsingMiddleware();
        $parser = function ($input) {
            return ['data' => $input];
        };

        $this->assertFalse($middleware->hasBodyParser('text/foo'));

        $middleware->registerBodyParser('text/foo', $parser);
        $this->assertTrue($middleware->hasBodyParser('text/foo'));

        $this->assertSame($parser, $middleware->getBodyParser('text/foo'));
    }

    public function testGettingUnknownParser()
    {
        $middleware = new BodyParsingMiddleware();

        $this->expectException(RuntimeException::class);
        $middleware->getBodyParser('text/foo');
    }
}
