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
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\UrlGeneratorInterface;
use Slim\Middleware\BasePathMiddleware;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\JsonBodyParserMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Routing\RouteMatch;
use Slim\Tests\Traits\AppTestTrait;

final class RoutingMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function testRouteIsStoredOnSuccessfulMatch()
    {
        $app = AppFactory::create();

        $test = $this;
        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($test) {
            // route is available
            /** @var RouteMatch $routeMatch */
            $routeMatch = $request->getAttribute(RouteMatch::class);
            $test->assertInstanceOf(RouteMatch::class, $routeMatch);

            return $handler->handle($request);
        };

        $app->add(RoutingMiddleware::class);
        $app->add($middleware);
        $app->add(EndpointMiddleware::class);

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', 'https://example.com:443/hello/foo');

        $app->get('/hello/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    public function testRouteWithMiddlewareAsString()
    {
        $app = AppFactory::create();

        $app->addRoutingMiddleware();

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', 'https://example.com:443/hello/foo');

        $app->get('/hello/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        })->add(JsonBodyParserMiddleware::class);

        $response = $app->handle($request);

        $this->assertSame('Hello World', (string)$response->getBody());
    }

    public function testRouteIsNotStoredOnMethodNotAllowed()
    {
        $this->expectException(HttpMethodNotAllowedException::class);

        $app = AppFactory::create();

        $test = $this;
        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($test) {
            try {
                return $handler->handle($request);
            } catch (HttpMethodNotAllowedException $exception) {
                $request = $exception->getRequest();

                // RouteMatch is available
                /** @var RouteMatch $routeMatch */
                $routeMatch = $request->getAttribute(RouteMatch::class);
                $test->assertSame(DispatcherInterface::METHOD_NOT_ALLOWED, $routeMatch->getStatus());

                // route is not available
                $test->assertNull($routeMatch->getRoute());

                // Re-throw to keep the behavior consistent
                throw $exception;
            }
        };

        $app->add(RoutingMiddleware::class);
        $app->add($middleware);
        $app->add(EndpointMiddleware::class);

        $app->post('/hello/foo', function (ServerRequestInterface $request, ResponseInterface $response) {
            $response->getBody()->write('Hello World');

            return $response;
        });

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/hello/foo');

        $app->handle($request);
    }

    public function testRouteIsNotStoredOnNotFound(): void
    {
        $this->expectException(HttpNotFoundException::class);

        $app = AppFactory::create();

        $test = $this;
        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($test) {
            try {
                return $handler->handle($request);
            } catch (HttpNotFoundException $exception) {
                $request = $exception->getRequest();

                // RouteMatch is available
                $routeMatch = $request->getAttribute(RouteMatch::class);
                $test->assertSame(DispatcherInterface::NOT_FOUND, $routeMatch->getStatus());

                // route is not available
                $test->assertNull($routeMatch->getRoute());

                // Re-throw to keep the behavior consistent
                throw $exception;
            }
        };

        $app->add(RoutingMiddleware::class);
        $app->add($middleware);
        $app->add(EndpointMiddleware::class);

        // No route is defined for '/hello/foo'

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/hello/foo');

        $app->handle($request);
    }

    public function testRoutingWithBasePath(): void
    {
        $app = AppFactory::create();
        $app->setBasePath('/api');

        $app->add(BasePathMiddleware::class);
        $app->addRoutingMiddleware();

        // Define a route with arguments
        $app->get('/users/{id}', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            $urlGenerator = $this->get(UrlGeneratorInterface::class);

            $url = $urlGenerator->relativeUrlFor('user.show', ['id' => $args['id']], ['page' => 2]);
            $response = $response->withHeader('X-relativeUrlFor', $url);

            $url2 = $urlGenerator->fullUrlFor($request->getUri(), 'user.show', ['id' => $args['id']], ['page' => 2]);
            $response = $response->withHeader('X-fullUrlFor', $url2);

            return $response;
        })->setName('user.show');

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/api/users/123');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('/api/users/123?page=2', $response->getHeaderLine('X-relativeUrlFor'));
        $this->assertSame('/api/users/123?page=2', $response->getHeaderLine('X-fullUrlFor'));
    }

    public function testRoutePreservesEncodedReservedCharactersWhenPathDecodingDisabled(): void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware(false);

        $app->get('/something/{magic}/{foo}', function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            array $args
        ) {
            $response->getBody()->write($args['magic'] . '|' . $args['foo']);

            return $response;
        });

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/something/magic/foo%2Fbar');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('magic|foo%2Fbar', (string)$response->getBody());
    }

    public function testRoutingWithUriDoesNotStartWithBasePath(): void
    {
        $this->expectException(HttpNotFoundException::class);

        $app = AppFactory::create();
        $app->setBasePath('/api');

        $app->add(BasePathMiddleware::class);
        $app->addRoutingMiddleware();

        // Define a route with arguments
        $app->get('/users', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/users');

        $app->handle($request);
    }

    public function testHttpMethodNotAllowedException(): void
    {
        $this->expectException(HttpMethodNotAllowedException::class);

        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        // Define a route with arguments
        $app->post('/hello/foo', function (ServerRequestInterface $request, ResponseInterface $response, $args) {
            return $response;
        });

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/hello/foo');

        $app->handle($request);
    }

    public function testPercentEncodedPath(): void
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();

        // Define a route with arguments
        $app->get('/article/{articles}', function ($request, $response) {
            return $response;
        });

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/article/1%2C2');

        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }
}
