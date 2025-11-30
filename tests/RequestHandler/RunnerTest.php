<?php

declare(strict_types=1);

namespace Slim\Tests\RequestHandler;

use DI\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Factory\AppFactory;
use Slim\Routing\PipelineOrder;
use Slim\Routing\PipelineRunner;
use stdClass;

final class RunnerTest extends TestCase
{
    public function testHandleWithMiddlewareInterface(): void
    {
        $app = AppFactory::create();

        $request = $app
            ->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('X-Test', 'Modified');

        $response = $app
            ->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $middleware = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                $response = $handler->handle($request);

                return $response->withHeader('X-Middleware', 'Processed');
            }
        };

        $runner = $app
            ->getContainer()
            ->get(PipelineRunner::class)
            ->withPipeline([
                $middleware,
                function () use ($response) {
                    return $response->withHeader('X-Result', 'Success');
                },
            ]);

        $result = $runner->handle($request);

        $this->assertSame('Processed', $result->getHeaderLine('X-Middleware'));
        $this->assertSame('Success', $result->getHeaderLine('X-Result'));
    }

    public function testHandleWithRequestHandlerInterface(): void
    {
        $app = AppFactory::create();

        $request = $app
            ->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app
            ->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $handler = new class ($response) implements RequestHandlerInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->response->withHeader('X-Handler', 'Handled');
            }
        };

        $runner = $app
            ->getContainer()
            ->get(PipelineRunner::class)
            ->withPipeline([$handler]);

        $result = $runner->handle($request);

        $this->assertSame('Handled', $result->getHeaderLine('X-Handler'));
    }

    public function testHandleWithCallableMiddleware(): void
    {
        $app = AppFactory::create();

        $request = $app
            ->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app
            ->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $runner = $app
            ->getContainer()
            ->get(PipelineRunner::class)
            ->withPipeline([
                function (ServerRequestInterface $req, RequestHandlerInterface $handler) use ($response) {
                    return $response->withHeader('X-Callable', 'Called');
                },
            ]);

        $result = $runner->handle($request);

        $this->assertSame('Called', $result->getHeaderLine('X-Callable'));
    }

    public function testHandleWithEmptyQueueThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The middleware pipeline is empty.');

        $app = AppFactory::create();

        $request = $app
            ->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $runner = $app
            ->getContainer()
            ->get(PipelineRunner::class)
            ->withPipeline([

            ]);

        $runner->handle($request);
    }

    public function testHandleWithInvalidObjectMiddlewareThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Invalid pipeline entry of type "stdClass". Expected one of: callable, Psr\Http\Server\MiddlewareInterface, or Psr\Http\Server\RequestHandlerInterface.',
        );

        $app = AppFactory::create();

        $request = $app
            ->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $runner = $app
            ->getContainer()
            ->get(PipelineRunner::class)
            ->withPipeline([new stdClass()]);

        $runner->handle($request);
    }

    public function testHandleWithInvalidMiddlewareStringThrowsException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("No entry or class found for 'foo'");

        $app = AppFactory::create();

        $request = $app
            ->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $runner = $app
            ->getContainer()
            ->get(PipelineRunner::class)
            ->withPipeline(['foo']);

        $runner->handle($request);
    }

    public function testHandleExecutesPipelineInLifoOrder(): void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();

        $request = $container
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $responseFactory = $container->get(ResponseFactoryInterface::class);

        // This middleware will be executed LAST in LIFO mode (because it was added first).
        $first = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                $response = $handler->handle($request);
                return $response->withHeader('X-Order', 'First');
            }
        };

        // This middleware will be executed FIRST in LIFO mode.
        $second = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                $response = $handler->handle($request);
                return $response->withHeader('X-Order', 'Second');
            }
        };

        // Final handler that produces a basic response
        $finalHandler = fn() => $responseFactory->createResponse();

        $runner = $container
            ->get(PipelineRunner::class)
            ->withOrder(PipelineOrder::LIFO)
            ->withPipeline(
                [
                    $finalHandler,  // end
                    $second,        // ^ second
                    $first,         // ^ start
                ],
            );

        $response = $runner->handle($request);

        $this->assertSame('First', $response->getHeaderLine('X-Order'));
    }


}
