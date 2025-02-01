<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Builder\AppBuilder;
use Slim\Container\GuzzleDefinitions;
use Slim\Container\HttpSoftDefinitions;
use Slim\Container\LaminasDiactorosDefinitions;
use Slim\Container\NyholmDefinitions;
use Slim\Container\SlimHttpDefinitions;
use Slim\Container\SlimPsr7Definitions;
use Slim\Media\MediaTypeDetector;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\ResponseFactoryMiddleware;
use Slim\RequestHandler\Runner;
use Slim\Tests\Traits\AppTestTrait;

use function simplexml_load_string;

final class BodyParsingMiddlewareTest extends TestCase
{
    use AppTestTrait;

    #[DataProvider('parsingProvider')]
    public function testParsingWithNyholm($contentType, $body, $expected)
    {
        $builder = new AppBuilder();

        $builder->addDefinitions(
            [
                BodyParsingMiddleware::class => function (ContainerInterface $container) {
                    $mediaTypeDetector = $container->get(MediaTypeDetector::class);
                    $middleware = new BodyParsingMiddleware($mediaTypeDetector);

                    return $middleware
                        ->withDefaultMediaType('text/html')
                        ->withDefaultBodyParsers();
                },
            ]
        );

        $builder->addDefinitionsClass(NyholmDefinitions::class);
        $app = $builder->build();

        $responseFactory = $app->getContainer()->get(ResponseFactoryMiddleware::class);

        $test = $this;
        $middlewares = [
            $app->getContainer()->get(BodyParsingMiddleware::class),
            $this->createCallbackMiddleware(function (ServerRequestInterface $request) use ($expected, $test) {
                $test->assertEquals($expected, $request->getParsedBody());
            }),
            $responseFactory,
        ];

        $stream = $app->getContainer()
            ->get(StreamFactoryInterface::class)
            ->createStream($body);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('Accept', $contentType)
            ->withHeader('Content-Type', $contentType)
            ->withBody($stream);

        (new Runner($middlewares))->handle($request);
    }

    public static function parsingProvider(): array
    {
        return [
            'form' => [
                'application/x-www-form-urlencoded;charset=utf8',
                'foo=bar',
                ['foo' => 'bar'],
            ],
            'json' => [
                'application/json',
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
            'text-xml' => [
                'text/xml',
                '<person><name>John</name></person>',
                simplexml_load_string('<person><name>John</name></person>'),
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
            // null is not supported anymore
            // 'no-contenttype' => [
            //    null,
            //    '"foo bar"',
            //    null,
            // ],
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

    #[DataProvider('parsingInvalidJsonProvider')]
    public function testParsingInvalidJsonWithSlimPsr7($contentType, $body)
    {
        $builder = new AppBuilder();
        $builder->addDefinitions(
            [
                BodyParsingMiddleware::class => function (ContainerInterface $container) {
                    $mediaTypeDetector = $container->get(MediaTypeDetector::class);
                    $middleware = new BodyParsingMiddleware($mediaTypeDetector);

                    return $middleware
                        ->withDefaultMediaType('text/html')
                        ->withDefaultBodyParsers();
                },
            ]
        );

        $builder->addDefinitionsClass(SlimPsr7Definitions::class);
        $app = $builder->build();
        $container = $app->getContainer();

        $middlewares = [
            $container->get(BodyParsingMiddleware::class),
            $container->get(ResponseFactoryMiddleware::class),
        ];

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('Accept', $contentType)
            ->withHeader('Content-Type', $contentType);

        $request->getBody()->write($body);

        $response = (new Runner($middlewares))->handle($request);

        $this->assertSame('', (string)$response->getBody());
    }

    public static function parsingInvalidJsonProvider(): array
    {
        return [
            'invalid-json' => [
                'application/json;charset=utf8',
                '{"foo"}/bar',
            ],
            'invalid-json-2' => [
                'application/json',
                '{',
            ],
        ];
    }

    public function testParsingWithARegisteredParserAndSlimHttp()
    {
        $builder = new AppBuilder();

        // Replace or change the PSR-17 factory because slim/http has its own parser
        $builder->addDefinitionsClass(SlimHttpDefinitions::class);
        $builder->addDefinitions(
            [
                BodyParsingMiddleware::class => function (ContainerInterface $container) {
                    $mediaTypeDetector = $container->get(MediaTypeDetector::class);
                    $middleware = new BodyParsingMiddleware($mediaTypeDetector);

                    return $middleware->withBodyParser('application/vnd.api+json', function ($input) {
                        return ['data' => json_decode($input, true)];
                    });
                },
            ]
        );
        $app = $builder->build();

        $input = '{"foo":"bar"}';
        $stream = $app->getContainer()
            ->get(StreamFactoryInterface::class)
            ->createStream($input);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('Accept', 'application/vnd.api+json;charset=utf8')
            ->withBody($stream);

        $middlewares = [];
        $middlewares[] = $app->getContainer()->get(BodyParsingMiddleware::class);
        $middlewares[] = $this->createParsedBodyMiddleware();
        $middlewares[] = $app->getContainer()->get(ResponseFactoryMiddleware::class);

        $response = (new Runner($middlewares))->handle($request);

        $this->assertJsonResponse(['data' => ['foo' => 'bar']], $response);
        $this->assertSame(['data' => ['foo' => 'bar']], json_decode((string)$response->getBody(), true));
    }

    #[DataProvider('httpDefinitionsProvider')]
    public function testParsingFailsWhenAnInvalidTypeIsReturned(string $definitions)
    {
        // The slim/http package has its own body parser, so this middleware will not be used.
        // The SlimHttpDefinitions::class will not fail here, because the body parser will not be executed.
        if ($definitions === SlimHttpDefinitions::class) {
            $this->assertTrue(true);

            return;
        }

        $this->expectException(RuntimeException::class);

        $builder = new AppBuilder();
        $builder->addDefinitionsClass($definitions);

        $builder->addDefinitions(
            [
                BodyParsingMiddleware::class => function (ContainerInterface $container) {
                    $mediaTypeDetector = $container->get(MediaTypeDetector::class);
                    $middleware = new BodyParsingMiddleware($mediaTypeDetector);

                    $middleware = $middleware->withBodyParser('application/json', function () {
                        // invalid - should return null, array or object
                        return 10;
                    });

                    return $middleware;
                },
            ]
        );
        $app = $builder->build();

        $stream = $app->getContainer()
            ->get(StreamFactoryInterface::class)
            ->createStream('{"foo":"bar"}');

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/')
            ->withHeader('Accept', 'application/json;charset=utf8')
            ->withHeader('Content-Type', 'application/json;charset=utf8')
            ->withBody($stream);

        $middlewares = [];
        $middlewares[] = $app->getContainer()->get(BodyParsingMiddleware::class);
        $middlewares[] = $this->createParsedBodyMiddleware();
        $middlewares[] = $app->getContainer()->get(ResponseFactoryMiddleware::class);

        (new Runner($middlewares))->handle($request);
    }

    public static function httpDefinitionsProvider(): array
    {
        return [
            'GuzzleDefinitions' => [GuzzleDefinitions::class],
            'HttpSoftDefinitions' => [HttpSoftDefinitions::class],
            'LaminasDiactorosDefinitions' => [LaminasDiactorosDefinitions::class],
            'NyholmDefinitions' => [NyholmDefinitions::class],
            'SlimHttpDefinitions' => [SlimHttpDefinitions::class],
            'SlimPsr7Definitions' => [SlimPsr7Definitions::class],
        ];
    }

    private function createParsedBodyMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                $response = $handler->handle($request);

                // Return the parsed body
                $response->getBody()->write(json_encode($request->getParsedBody()));

                return $response;
            }
        };
    }

    private function createCallbackMiddleware(callable $callback): MiddlewareInterface
    {
        return new class ($callback) implements MiddlewareInterface {
            /**
             * @var callable
             */
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                $response = $handler->handle($request);

                call_user_func($this->callback, $request, $handler);

                return $response;
            }
        };
    }
}
