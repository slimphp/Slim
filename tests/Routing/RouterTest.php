<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Routing;

use FastRoute\RouteCollector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Builder\AppBuilder;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Routing\Route;
use Slim\Routing\RouteGroup;
use Slim\Routing\Router;

class RouterTest extends TestCase
{
    #[DataProvider('httpMethodProvider')]
    public function testHttpMethods(string $methodName, string $path, callable $handler, array $expectedMethods): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);

        // Define a route using the HTTP method from the data provider
        $route = $router->{$methodName}($path, $handler);

        // Verify the route is mapped correctly
        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals($handler, $route->getHandler());
        $this->assertSame($path, $route->getPattern());

        // Verify that all expected methods are present in the route's methods
        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContains(
                $expectedMethod,
                $route->getMethods(),
                "Method $expectedMethod not found in route methods"
            );
        }
    }

    public static function httpMethodProvider(): array
    {
        return [
            [
                'any',
                '/any',
                function () {
                    return 'any_handler';
                },
                ['*'],
            ],
            [
                'delete',
                '/delete',
                function () {
                    return 'delete_handler';
                },
                ['DELETE'],
            ],
            [
                'get',
                '/get',
                function () {
                    return 'get_handler';
                },
                ['GET'],
            ],
            [
                'options',
                '/options',
                function () {
                    return 'options_handler';
                },
                ['OPTIONS'],
            ],
            [
                'patch',
                '/patch',
                function () {
                    return 'patch_handler';
                },
                ['PATCH'],
            ],
            [
                'post',
                '/post',
                function () {
                    return 'post_handler';
                },
                ['POST'],
            ],
            [
                'put',
                '/put',
                function () {
                    return 'put_handler';
                },
                ['PUT'],
            ],
        ];
    }

    public function testMapCreatesRoute(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);

        $methods = ['GET'];
        $path = '/test-route';
        $handler = function () {
            return 'Test Handler';
        };

        $route = $router->map($methods, $path, $handler);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame($methods, $route->getMethods());
        $this->assertSame($router->getBasePath() . $path, $route->getPattern());
        $this->assertSame($handler, $route->getHandler());
    }

    public function testGroupCreatesRouteGroup(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);

        $pattern = '/group';
        $handler = function (RouteGroup $group) {
            $group->map(['GET'], '/foo', 'foo_handler');
        };

        $routeGroup = $router->group($pattern, $handler);

        $this->assertInstanceOf(RouteGroup::class, $routeGroup);
        $this->assertSame($router->getBasePath() . $pattern, $routeGroup->getPrefix());
    }

    public function testGetRouteCollectorReturnsCollector(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);

        $collector = $router->getRouteCollector();
        $this->assertInstanceOf(RouteCollector::class, $collector);
    }

    public function testSetAndGetBasePath(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);

        $basePath = '/base-path';
        $router->setBasePath($basePath);

        $this->assertSame($basePath, $router->getBasePath());
    }

    public function testMapWithBasePath(): void
    {
        $app = (new AppBuilder())->build();
        $router = $app->getContainer()->get(Router::class);

        $basePath = '/base-path';
        $router->setBasePath($basePath);

        $methods = ['GET'];
        $path = '/test-route';
        $handler = function () {
            return 'Test Handler';
        };

        $route = $router->map($methods, $path, $handler);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame($methods, $route->getMethods());
        $this->assertSame($path, $route->getPattern());
        $this->assertSame($handler, $route->getHandler());
    }

    public function testOptionsAnyCorsRoute(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $app->add(new ContentLengthMiddleware());
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->options('/{routes:.+}', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Body');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('OPTIONS', '/test');

        $response = $app->handle($request);
        $this->assertSame('Body', (string)$response->getBody());
    }

    public function testOptionsAnyRoute(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $app->add(new ContentLengthMiddleware());
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->options('/{any:.*}', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Body');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('OPTIONS', '/test');

        $response = $app->handle($request);
        $this->assertSame('Body', (string)$response->getBody());

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('OPTIONS', '/');

        $response = $app->handle($request);
        $this->assertSame('Body', (string)$response->getBody());
    }

    public function testRouteWithParameters(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $app->add(new ContentLengthMiddleware());
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get('/books/{id}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
            $response->getBody()->write(json_encode($args));

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/books/123');

        $response = $app->handle($request);
        $this->assertSame('{"id":"123"}', (string)$response->getBody());
    }

    public function testCustomRoute(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $app->add(new ContentLengthMiddleware());
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->map(['GET', 'POST'], '/books', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('OK');

            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/books');

        $response = $app->handle($request);
        $this->assertSame('OK', (string)$response->getBody());

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('POST', '/books');

        $response = $app->handle($request);
        $this->assertSame('OK', (string)$response->getBody());
    }

    public function testRegexRoute(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $app->add(new ContentLengthMiddleware());
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get(
            '/users/{id:[0-9]+}',
            function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
                $response->getBody()->write($args['id']);

                return $response;
            }
        );

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/users/123');

        $response = $app->handle($request);
        $this->assertSame('123', (string)$response->getBody());
    }

    public function testMultipleOptionalParameters(): void
    {
        $builder = new AppBuilder();
        $app = $builder->build();

        $app->add(new ContentLengthMiddleware());
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get(
            '/news[/{year}[/{month}]]',
            function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
                $response->getBody()->write(json_encode($args));

                return $response;
            }
        );

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/news');

        $response = $app->handle($request);
        $this->assertSame('[]', (string)$response->getBody());

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/news/2038');

        $response = $app->handle($request);
        $this->assertSame('{"year":"2038"}', (string)$response->getBody());

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/news/2038/01');

        $response = $app->handle($request);
        $this->assertSame('{"year":"2038","month":"01"}', (string)$response->getBody());
    }
}
