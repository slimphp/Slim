<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Middleware\BasePathMiddleware;
use Slim\Middleware\EndpointMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Tests\Traits\AppTestTrait;

final class BasePathMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function testEmptyScriptName(): void
    {
        $definitions =
            [
                BasePathMiddleware::class => function (ContainerInterface $container) {
                    $app = $container->get(App::class);

                    return new BasePathMiddleware($app, 'apache2handler');
                },
            ];
        $app = $this->createApp($definitions);

        $app->add(BasePathMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get('/', function ($request, ResponseInterface $response) {
            $basePath = $this->get(App::class)->getBasePath();
            $response->getBody()->write('basePath: ' . $basePath);

            return $response;
        });

        $serverParams = [
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => '',
        ];

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/', $serverParams);

        $response = $app->handle($request);

        $this->assertSame('', $app->getBasePath());
        $this->assertSame('basePath: ', (string)$response->getBody());
    }

    public function testScriptNameWithIndexPhp(): void
    {
        $definitions =
            [
                BasePathMiddleware::class => function (ContainerInterface $container) {
                    $app = $container->get(App::class);

                    return new BasePathMiddleware($app, 'apache2handler');
                },
            ];
        $app = $this->createApp($definitions);

        $app->add(BasePathMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get('/', function ($request, ResponseInterface $response) {
            $basePath = $this->get(App::class)->getBasePath();
            $response->getBody()->write('basePath: ' . $basePath);

            return $response;
        });

        $serverParams = [
            'REQUEST_URI' => '/',
            // PHP internal server
            'SCRIPT_NAME' => '/index.php',
        ];

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/', $serverParams);

        $response = $app->handle($request);

        $this->assertSame('', $app->getBasePath());
        $this->assertSame('basePath: ', (string)$response->getBody());
    }

    public function testScriptNameWithPublicIndexPhp(): void
    {
        $definitions =
            [
                BasePathMiddleware::class => function (ContainerInterface $container) {
                    $app = $container->get(App::class);

                    return new BasePathMiddleware($app, 'apache2handler');
                },
            ];
        $app = $this->createApp($definitions);

        $app->add(BasePathMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            $basePath = $this->get(App::class)->getBasePath();
            $response->getBody()->write('basePath: ' . $basePath);

            return $response;
        });

        $serverParams = [
            'REQUEST_URI' => '/',
            // PHP internal server
            'SCRIPT_NAME' => '/public/index.php',
        ];

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/', $serverParams);

        $response = $app->handle($request);

        $this->assertSame('', $app->getBasePath());
        $this->assertSame('basePath: ', (string)$response->getBody());
    }

    public function testSubDirectoryWithSlash(): void
    {
        $definitions =
            [
                BasePathMiddleware::class => function (ContainerInterface $container) {
                    $app = $container->get(App::class);

                    return new BasePathMiddleware($app, 'apache2handler');
                },
            ];
        $app = $this->createApp($definitions);

        $app->add(BasePathMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get('/', function ($request, ResponseInterface $response) {
            $basePath = $this->get(App::class)->getBasePath();
            $response->getBody()->write('basePath: ' . $basePath);

            return $response;
        });

        $serverParams = [
            'REQUEST_URI' => '/slim-hello-world/',
            'SCRIPT_NAME' => '/slim-hello-world/public/index.php',
        ];
        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/slim-hello-world/?key=value', $serverParams);

        $response = $app->handle($request);

        $this->assertSame('/slim-hello-world', $app->getBasePath());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('basePath: /slim-hello-world', (string)$response->getBody());
    }

    public function testSubDirectoryWithoutSlash(): void
    {
        $definitions =
            [
                BasePathMiddleware::class => function (ContainerInterface $container) {
                    $app = $container->get(App::class);

                    return new BasePathMiddleware($app, 'apache2handler');
                },
            ];
        $app = $this->createApp($definitions);

        $app->add(BasePathMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get('/foo', function ($request, ResponseInterface $response) {
            $basePath = $this->get(App::class)->getBasePath();
            $response->getBody()->write('basePath: ' . $basePath);

            return $response;
        });

        $serverParams = [
            'REQUEST_URI' => '/slim-hello-world/foo',
            'SCRIPT_NAME' => '/slim-hello-world/public/index.php',
        ];

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/slim-hello-world/foo?key=value', $serverParams);

        $response = $app->handle($request);

        $this->assertSame('/slim-hello-world', $app->getBasePath());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('basePath: /slim-hello-world', (string)$response->getBody());
    }

    public function testSubDirectoryWithFooPath(): void
    {
        $definitions =
            [
                BasePathMiddleware::class => function (ContainerInterface $container) {
                    $app = $container->get(App::class);

                    return new BasePathMiddleware($app, 'apache2handler');
                },
            ];
        $app = $this->createApp($definitions);

        $app->add(BasePathMiddleware::class);
        $app->add(RoutingMiddleware::class);
        $app->add(EndpointMiddleware::class);

        $app->get('/foo', function ($request, ResponseInterface $response) {
            $basePath = $this->get(App::class)->getBasePath();
            $response->getBody()->write('basePath: ' . $basePath);

            return $response;
        });

        $serverParams = [
            'REQUEST_URI' => '/slim-hello-world/foo',
            'SCRIPT_NAME' => '/slim-hello-world/public/index.php',
        ];
        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/slim-hello-world/foo/?key=value', $serverParams);

        $response = $app->handle($request);

        $this->assertSame('/slim-hello-world', $app->getBasePath());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('basePath: /slim-hello-world', (string)$response->getBody());
    }
}
