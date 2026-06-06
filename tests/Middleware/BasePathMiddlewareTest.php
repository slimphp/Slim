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
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Middleware\BasePathMiddleware;
use Slim\Tests\Traits\AppTestTrait;

final class BasePathMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function testEmptyScriptName(): void
    {
        $app = AppFactory::create();

        $app->add(BasePathMiddleware::class);
        $app->addRoutingMiddleware();

        $app->get('/', function ($request, ResponseInterface $response) use ($app) {
            $response->getBody()->write('basePath: ' . $app->getBasePath());

            return $response;
        });

        $serverParams = [
            'SCRIPT_NAME' => '',
        ];

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/', $serverParams);

        $response = $app->handle($request);

        $this->assertSame('', $app->getBasePath());
        $this->assertSame('basePath: ', (string)$response->getBody());
    }

    public function testScriptNameWithIndexPhp(): void
    {
        $app = AppFactory::create();

        $app->add(BasePathMiddleware::class);
        $app->addRoutingMiddleware();

        $app->get('/', function ($request, ResponseInterface $response) use ($app) {
            $response->getBody()->write('basePath: ' . $app->getBasePath());

            return $response;
        });

        $serverParams = [
            // PHP internal server
            'SCRIPT_NAME' => '/index.php',
        ];

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/', $serverParams);

        $response = $app->handle($request);

        $this->assertSame('', $app->getBasePath());
        $this->assertSame('basePath: ', (string)$response->getBody());
    }

    public function testScriptNameWithPublicIndexPhp(): void
    {
        $app = AppFactory::create();

        $app->add(BasePathMiddleware::class);
        $app->addRoutingMiddleware();

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) use ($app) {
            $response->getBody()->write('basePath: ' . $app->getBasePath());

            return $response;
        });

        $serverParams = [
            // PHP internal server
            'SCRIPT_NAME' => '/public/index.php',
        ];

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/', $serverParams);

        $response = $app->handle($request);

        $this->assertSame('', $app->getBasePath());
        $this->assertSame('basePath: ', (string)$response->getBody());
    }

    public function testSubDirectoryWithSlash(): void
    {
        $app = AppFactory::create();

        $app->add(BasePathMiddleware::class);
        $app->addRoutingMiddleware();

        $app->get('/', function ($request, ResponseInterface $response) use ($app) {
            $response->getBody()->write('basePath: ' . $app->getBasePath());

            return $response;
        });

        $serverParams = [
            'SCRIPT_NAME' => '/slim-hello-world/public/index.php',
        ];
        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/slim-hello-world/?key=value', $serverParams);

        $response = $app->handle($request);

        $this->assertSame('/slim-hello-world', $app->getBasePath());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('basePath: /slim-hello-world', (string)$response->getBody());
    }

    public function testSubDirectoryWithoutSlash(): void
    {
        $app = AppFactory::create();

        $app->add(BasePathMiddleware::class);
        $app->addRoutingMiddleware();

        $app->get('/foo', function ($request, ResponseInterface $response) use ($app) {
            $response->getBody()->write('basePath: ' . $app->getBasePath());

            return $response;
        });

        $serverParams = [
            'SCRIPT_NAME' => '/slim-hello-world/public/index.php',
        ];

        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/slim-hello-world/foo?key=value', $serverParams);

        $response = $app->handle($request);

        $this->assertSame('/slim-hello-world', $app->getBasePath());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('basePath: /slim-hello-world', (string)$response->getBody());
    }

    public function testStrictSubDirectoryWithFooPath(): void
    {
        $this->expectException(HttpNotFoundException::class);

        $app = AppFactory::create();

        $app->add(BasePathMiddleware::class);
        $app->addRoutingMiddleware();

        $app->get('/foo', function ($request, ResponseInterface $response) use ($app) {
            $response->getBody()->write('basePath: ' . $app->getBasePath());

            return $response;
        });

        $serverParams = [
            'SCRIPT_NAME' => '/slim-hello-world/public/index.php',
        ];
        $request = $this
            ->getServerRequestFactory($app)
            ->createServerRequest('GET', '/slim-hello-world/foo/?key=value', $serverParams);

        $app->handle($request);
    }
}
