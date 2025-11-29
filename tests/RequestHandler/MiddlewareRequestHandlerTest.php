<?php

declare(strict_types=1);

namespace Slim\Tests\RequestHandler;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Enums\MiddlewareOrder;
use Slim\Factory\AppFactory;
use Slim\Middleware\ResponseFactoryMiddleware;
use Slim\Routing\RouterDispatcher;

final class MiddlewareRequestHandlerTest extends TestCase
{
    public function testHandleWithFunctionMiddlewareStack()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->add(
            function ($req, $handler) {
                $response = $handler->handle($req);

                return $response->withHeader('X-Middleware-1', 'Processed-1');
            },
        );

        $app->add(function ($req, $handler) {
            $response = $handler->handle($req);

            return $response->withHeader('X-Middleware-2', 'Processed-2');
        });

        $app->add(ResponseFactoryMiddleware::class);

        $handler = $app->getContainer()
            ->get(RouterDispatcher::class);

        $response = $handler->handle($request);

        $this->assertSame('Processed-1', $response->getHeaderLine('X-Middleware-1'));
        $this->assertSame('Processed-2', $response->getHeaderLine('X-Middleware-2'));
    }

    public function testHandleWithoutMiddlewareStack()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No middleware found. Add a response factory middleware.');

        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $handler = $app->getContainer()
            ->get(RouterDispatcher::class);

        $response = $handler->handle($request);

        $this->assertSame('Final', $response->getHeaderLine('X-Result'));
    }

    public function testHandleWithClassMiddlewareStack()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->add(
            new class implements MiddlewareInterface {
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler,
                ): ResponseInterface {
                    $response = $handler->handle($request);

                    return $response->withHeader('X-Middleware-1', 'Processed-1');
                }
            },
        );

        $app->add(ResponseFactoryMiddleware::class);

        $handler = $app->getContainer()
            ->get(RouterDispatcher::class);

        $response = $handler->handle($request);

        $this->assertSame('Processed-1', $response->getHeaderLine('X-Middleware-1'));
    }

    public function testHandleWithFifoMiddlewareStack()
    {
        $app = AppFactory::create();
        // $builder->setMiddlewareOrder(MiddlewareOrder::FIFO);

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $app->add(
            new class implements MiddlewareInterface {
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler,
                ): ResponseInterface {
                    $response = $handler->handle($request);
                    $response->getBody()->write('2');

                    return $response;
                }
            },
        );

        $app->add(
            new class implements MiddlewareInterface {
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler,
                ): ResponseInterface {
                    $response = $handler->handle($request);
                    $response->getBody()->write('1');

                    return $response;
                }
            },
        );

        $app->add(ResponseFactoryMiddleware::class);

        $handler = $app->getContainer()
            ->get(RouterDispatcher::class);

        $response = $handler->handle($request);

        $this->assertSame('12', (string)$response->getBody());
    }
}
