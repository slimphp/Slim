<?php

declare(strict_types=1);

namespace Slim\Tests\RequestHandler;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Factory\AppFactory;
use Slim\Routing\PipelineRunner;
use stdClass;

final class RunnerTest extends TestCase
{
    public function testHandleWithMiddlewareInterface()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('X-Test', 'Modified');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $middleware = new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = $handler->handle($request);

                return $response->withHeader('X-Middleware', 'Processed');
            }
        };

        $runner = new PipelineRunner(
            [
                $middleware,
                function () use ($response) {
                    return $response->withHeader('X-Result', 'Success');
                },
            ],
        );

        $result = $runner->handle($request);

        $this->assertSame('Processed', $result->getHeaderLine('X-Middleware'));
        $this->assertSame('Success', $result->getHeaderLine('X-Result'));
    }

    public function testHandleWithRequestHandlerInterface()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
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

        $runner = new PipelineRunner([$handler]);

        $result = $runner->handle($request);

        $this->assertSame('Handled', $result->getHeaderLine('X-Handler'));
    }

    public function testHandleWithCallableMiddleware()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $runner = new PipelineRunner([
            function (ServerRequestInterface $req, RequestHandlerInterface $handler) use ($response) {
                return $response->withHeader('X-Callable', 'Called');
            },
        ]);

        $result = $runner->handle($request);

        $this->assertSame('Called', $result->getHeaderLine('X-Callable'));
    }

    public function testHandleWithEmptyQueueThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No middleware found. Add a response factory middleware.');

        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $runner = new PipelineRunner([]);
        $runner->handle($request);
    }

    public function testHandleWithInvalidObjectMiddlewareThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid middleware queue entry "object"');

        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $runner = new PipelineRunner([new stdClass()]);
        $runner->handle($request);
    }

    public function testHandleWithInvalidMiddlewareStringThrowsException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid middleware queue entry "foo"');

        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $runner = new PipelineRunner(['foo']);
        $runner->handle($request);
    }
}
