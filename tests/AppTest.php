<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\App;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Middleware\BasePathMiddleware;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\ErrorExceptionMiddleware;
use Slim\Middleware\ExceptionLoggingMiddleware;
use Slim\Middleware\HeadMethodMiddleware;
use Slim\Middleware\HtmlExceptionMiddleware;
use Slim\Middleware\RoutingArgumentsMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Stream;
use Slim\Psr7\Uri;
use Slim\Routing\RouteGroup;
use Slim\Strategy\RequestResponseArgs;
use Slim\Strategy\RequestResponseNamedArgs;
use Slim\Tests\Traits\AppTestTrait;
use SplQueue;
use SplStack;
use UnexpectedValueException;

use function count;
use function strtolower;

final class AppTest extends TestCase
{
    use AppTestTrait;

    public function testAppWithExceptionAndErrorDetails(): void
    {
        $definitions =
            [
                HtmlExceptionMiddleware::class => function ($container) {
                    $responseFactory = $container->get(ResponseFactoryInterface::class);
                    $middleware = new HtmlExceptionMiddleware($responseFactory);

                    return $middleware->withErrorDetails(true);
                },
            ];

        $app = AppFactory::create($definitions);

        $app->add(HtmlExceptionMiddleware::class);
        $app->addRoutingMiddleware();

        $app->get('/', fn() => throw new UnexpectedValueException('Test exception message'));

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        $request = $request->withHeader(
            'Accept',
            'text/html, application/xhtml+xml, application/xml;q=0.9, application/json , image/webp, */*;q=0.8',
        );

        $response = $app->handle($request);

        $this->assertSame('text/html', $response->getHeaderLine('content-type'));

        $expected = 'Test exception message';
        $this->assertStringContainsString($expected, (string)$response->getBody());
    }

    public function testGetAppFromContainer(): void
    {
        $app = AppFactory::create();

        // should return the same instance
        $actual = $app->getContainer()->get(App::class);
        $this->assertSame($app, $actual);
    }

    public function testGetContainerWithDefinitions(): void
    {
        $definitions =
            [
                'test' => function () {
                    return 'test-value';
                },
            ];

        $app = AppFactory::create($definitions);

        $this->assertInstanceOf(ContainerInterface::class, $app->getContainer());
    }

    public function testAppWithMiddlewareStack(): void
    {
        $app = AppFactory::create();

        $app->add(BasePathMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(RoutingArgumentsMiddleware::class);
        $app->add(ErrorExceptionMiddleware::class);
        $app->add(ExceptionLoggingMiddleware::class);
        $app->add(HtmlExceptionMiddleware::class);
        $app->add(EndpointMiddleware::class);
        $app->add(ContentLengthMiddleware::class);

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response->withHeader('X-Test', 'action');
        });

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        $response = $app->handle($request);

        $this->assertSame('action', $response->getHeaderLine('X-Test'));
    }

    public function testGetWithInvokableClass(): void
    {
        $app = AppFactory::create();

        $app->addRoutingMiddleware();

        $action = new class {
            public function __invoke($request, $response, $args)
            {
                return $response->withHeader('X-Test', 'action');
            }
        };

        $app->get('/', $action::class);

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        $response = $app->handle($request);

        $this->assertSame('action', $response->getHeaderLine('X-Test'));
    }

    public static function lowerCaseRequestMethodsProvider(): array
    {
        return [
            ['get'],
            ['post'],
            ['put'],
            ['patch'],
            ['delete'],
            ['options'],
        ];
    }

    #[DataProvider('upperCaseRequestMethodsProvider')]
    public function testGetPostPutPatchDeleteOptionsMethods(string $method): void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest($method, '/');

        $methodName = strtolower($method);
        $app->$methodName('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });
        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    public function testAnyRoute(): void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('FOO', '/');

        $app->any('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });
        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    public static function upperCaseRequestMethodsProvider(): array
    {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
            ['OPTIONS'],
        ];
    }

    #[DataProvider('lowerCaseRequestMethodsProvider')]
    #[DataProvider('upperCaseRequestMethodsProvider')]
    public function testMapRoute(string $method): void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest($method, '/');

        $app->map([$method], '/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    public function testRouteWithInternationalCharacters(): void
    {
        $path = '/новости';

        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', $path);

        $app->get($path, function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    /********************************************************************************
     * Route Patterns
     *******************************************************************************/

    public static function routePatternsProvider(): array
    {
        return [
            // Route pattern -> http uri
            // Single slash route
            ['/', '/'],
            // Route That Does Not End In A Slash
            ['/foo', '/foo'],
            // Route That Ends In A Slash
            ['/foo/', '/foo/'],
            // Route That Ends In A double Slash
            ['/foo//', '/foo//'],
            // Route That contains In A double Slash
            ['/foo//bar', '/foo//bar'],
        ];
    }

    #[DataProvider('routePatternsProvider')]
    public function testRoutePatterns(string $pattern, string $uri): void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', $uri);

        $app->get($pattern, function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    /********************************************************************************
     * Route Groups
     *******************************************************************************/

    public function testGroupClosureIsBoundToThisClass(): void
    {
        $app = AppFactory::create();
        $testCase = $this;
        $app->group('/foo', function () use ($testCase) {
            $testCase->assertSame($testCase, $this);
        });
    }

    /**
     * Middleware
     */
    public function testAddMiddlewareUsingDeferredResolution(): void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    public function testAddMiddlewareUsingClosure(): void
    {
        $app = AppFactory::create();

        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $response = $handler->handle($request);

            return $response->withHeader('X-Foo', 'Foo');
        };

        $app->add($middleware);
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
        $this->assertSame('Foo', $response->getHeaderLine('X-Foo'));
    }

    public function testAddMiddlewareOnRoute(): void
    {
        $app = AppFactory::create();

        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $response = $handler->handle($request);
            $response->getBody()->write('_MW1_');

            return $response;
        };

        $middleware2 = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $response = $handler->handle($request);
            $response->getBody()->write('_MW2_');

            return $response;
        };

        $app->addRoutingMiddleware();

        // This middleware should not be invoked
        $middleware3 = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $response = $handler->handle($request);
            $response->getBody()->write('_MW3_');

            return $response;
        };
        $app->add($middleware3);

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        // Add two middlewares
        $app
            ->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
                $response->getBody()->write('_ROUTE1_');

                return $response;
            })
            ->add($middleware)
            ->add($middleware2);

        $response = $app->handle($request);

        $this->assertSame('_ROUTE1__MW2__MW1_', (string)$response->getBody());
    }

    public function testAddMiddlewareOnRouteGroup(): void
    {
        $app = AppFactory::create();

        $trace = new SplStack();

        $authMiddleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($trace) {
            $trace->push('_AUTH_');

            return $handler->handle($request);
        };

        $outgoingMiddleware = function (
            ServerRequestInterface $request,
            RequestHandlerInterface $handler,
        ) use ($trace) {
            $response = $handler->handle($request);
            $response->getBody()->write('_OUTGOING_');
            $trace->push('_OUTGOING_');

            return $response;
        };

        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/api/users');

        // Add middleware to group
        $app->group('/api', function (RouteGroup $group) use ($trace) {
            $group->get('/users', function (
                ServerRequestInterface $request,
                ResponseInterface $response,
            ) use ($trace) {
                $trace->push('_ROUTE1_');
                $response->getBody()->write('_ROUTE1_');

                return $response;
            });
        })->add($authMiddleware)->add($outgoingMiddleware);

        $response = $app->handle($request);

        $this->assertSame('_ROUTE1__OUTGOING_', (string)$response->getBody());
        $this->assertSame(
            [
                2 => '_OUTGOING_',
                1 => '_ROUTE1_',
                0 => '_AUTH_',
            ],
            iterator_to_array($trace),
        );
    }

    public function testAddMiddlewareOnTwoRouteGroup(): void
    {
        $app = AppFactory::create();

        $trace = new SplQueue();

        $app->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($trace) {
            $trace[] = '_GLOBAL_';

            return $handler->handle($request);
        });

        $authMiddleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($trace) {
            $trace[] = '_AUTH_';
            $response = $handler->handle($request);

            return $response;
        };

        $usersMiddleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($trace) {
            $trace[] = '_USERS_';
            $response = $handler->handle($request);
            $response->getBody()->write('_USERS_');

            return $response;
        };

        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/api/users/123');

        // Add middleware to groups
        $app->group('/api', function (RouteGroup $group) use ($usersMiddleware, $trace) {
            $group->group('/users', function (RouteGroup $group) use ($trace) {
                $group->get(
                    '/{id}',
                    function (ServerRequestInterface $request, ResponseInterface $response) use ($trace) {
                        $trace[] = '_ROUTE1_';
                        $response->getBody()->write('_ROUTE1_');

                        return $response;
                    },
                );
            })->add($usersMiddleware);
        })->add($authMiddleware);

        $response = $app->handle($request);

        $this->assertSame('_ROUTE1__USERS_', (string)$response->getBody());

        $this->assertSame(
            [
                '_GLOBAL_',
                '_AUTH_',
                '_USERS_',
                '_ROUTE1_',
            ],
            iterator_to_array($trace),
        );
    }

    public function testInvokeReturnMethodNotAllowed(): void
    {
        $this->expectException(HttpMethodNotAllowedException::class);

        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('POST', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });

        $app->handle($request);
    }

    public function testInvokeWithMatchingRoute(): void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterRequestResponseStrategy(): void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/hello/john');

        $app->get(
            '/hello/{name}',
            function (ServerRequestInterface $request, ResponseInterface $response, $args) {
                $this->get(App::class);
                $response->getBody()->write("Hello {$args['name']}");

                return $response;
            },
        );

        $response = $app->handle($request);
        $this->assertSame('Hello john', (string)$response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterRequestResponseArgStrategy(): void
    {
        $definitions = [
            RequestHandlerInvocationStrategyInterface::class => fn() => new RequestResponseArgs(),
        ];
        $app = AppFactory::create($definitions);

        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/hello/john');

        $app->get(
            '/hello/{name}',
            function (ServerRequestInterface $request, ResponseInterface $response, $name) {
                $response->getBody()->write("Hello {$name}");

                return $response;
            },
        );

        $response = $app->handle($request);

        $this->assertSame('Hello john', (string)$response->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterRequestResponseNamedArgsStrategy(): void
    {
        $definitions =
            [
                RequestHandlerInvocationStrategyInterface::class => fn() => new RequestResponseNamedArgs(),
            ];

        $app = AppFactory::create($definitions);

        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/hello/john');

        $app->get(
            '/hello/{name}',
            function (ServerRequestInterface $request, ResponseInterface $response, $name) {
                $response->getBody()->write("Hello {$name}");

                return $response;
            },
        );

        $response = $app->handle($request);

        $this->assertSame('Hello john', (string)$response->getBody());
    }

    public function testInvokeWithoutMatchingRoute(): void
    {
        $this->expectException(HttpNotFoundException::class);

        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/nada');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });

        $app->handle($request);
    }

    public function testInvokeWithCallableRegisteredInContainer(): void
    {
        $handler = new class {
            public function foo(ServerRequestInterface $request, ResponseInterface $response)
            {
                $response->getBody()->write('Hello handler:foo');

                return $response;
            }
        };

        $definitions = [
            'handler' => $handler,
        ];

        $app = AppFactory::create($definitions);

        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        $app->get('/', 'handler:foo');

        $response = $app->handle($request);

        $this->assertSame('Hello handler:foo', (string)$response->getBody());
    }

    public function testInvokeWithCallableRegisteredInContainerAsFunction(): void
    {
        $handler = new class {
            public function foo(ServerRequestInterface $request, ResponseInterface $response)
            {
                $response->getBody()->write('Hello handler:foo');

                return $response;
            }
        };

        $definitions = [
            'handler' => function () use ($handler) {
                return $handler;
            },
        ];

        $app = AppFactory::create($definitions);

        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        $app->get('/', 'handler:foo');

        $response = $app->handle($request);

        $this->assertSame('Hello handler:foo', (string)$response->getBody());
    }

    public function testInvokeWithNonExistentMethodOnCallableRegisteredInContainer(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The method "method_does_not_exist" does not exist');

        $definitions = [
            'handler' => new class {
                public function foo()
                {
                }
            },
        ];

        $app = AppFactory::create($definitions);

        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        $app->get('/', 'handler:method_does_not_exist');
        $app->handle($request);
    }

    public function testInvokeFunctionName(): void
    {
        $app = AppFactory::create();

        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        // @codingStandardsIgnoreStart
        function handle($request, ResponseInterface $response)
        {
            $response->getBody()->write('Hello World');

            return $response;
        }

        // @codingStandardsIgnoreEnd

        $app->get('/', __NAMESPACE__ . '\handle');

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    public function testAddMiddleware(): void
    {
        $app = AppFactory::create();

        $container = $app->getContainer();
        $routing = $container->get(RoutingMiddleware::class);
        $endpoint = $container->get(EndpointMiddleware::class);

        $app->addMiddleware($routing);
        $app->add($endpoint);

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $app->run($request);
        $this->expectOutputString('Hello World');
    }

    public function testGetBasePath(): void
    {
        $app = AppFactory::create();

        $app->addRoutingMiddleware();

        $this->assertSame('', $app->getBasePath());
        $app->setBasePath('/sub');
        $this->assertSame('/sub', $app->getBasePath());
    }

    public function testRun(): void
    {
        $app = AppFactory::create();

        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $app->run($request);
        $this->expectOutputString('Hello World');
    }

    public function testRunWithoutPassingInServerRequest(): void
    {
        $definitions
            = [
                ServerRequestCreatorInterface::class => function () {
                    return new class implements ServerRequestCreatorInterface {
                        public function createServerRequestFromGlobals(): ServerRequestInterface
                        {
                            return new Request(
                                'GET',
                                new Uri('http', 'localhost', 80, '/'),
                                new Headers(),
                                [],
                                [],
                                new Stream(fopen('php://memory', 'w+')),
                            );
                        }
                    };
                },
            ];

        $app = AppFactory::create($definitions);

        // $app = $builder->build();
        $app->addRoutingMiddleware();

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $app->run();

        $this->expectOutputString('Hello World');
    }

    public function testHandleReturnsEmptyResponseBodyWithHeadRequestMethod(): void
    {
        $app = AppFactory::create();
        $app->add(HeadMethodMiddleware::class);
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('HEAD', '/');

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $response = $app->handle($request);
        $this->assertEmpty((string)$response->getBody());
    }

    public function testPathWithOptionalArgs(): void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/hello/friend');

        $app->get('/hello[/{name}]', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $response->getBody()->write((string)count($args));

            return $response;
        });

        $response = $app->handle($request);
        $this->assertSame('1', (string)$response->getBody());

        // 2. test without value
        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/hello');

        $response = $app->handle($request);
        $this->assertSame('0', (string)$response->getBody());
    }

    public function testAddErrorMiddleware(): void
    {
        $app = AppFactory::create()
            ->addErrorMiddleware()
            ->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('HEAD', '/')
            ->withHeader('Accept', 'text/html');

        $app->get('/', function () {
            throw new RuntimeException('test message');
        });

        $response = $app->handle($request);
        $this->assertStringContainsString('<title>Application Error</title>', (string)$response->getBody());
    }
}
