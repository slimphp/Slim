<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Middleware;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Factory\AppFactory;
use Slim\Middleware\OutputBufferingMiddleware;
use Slim\Tests\Traits\AppTestTrait;

use function ob_get_contents;

final class OutputBufferingMiddlewareTest extends TestCase
{
    use AppTestTrait;

    public function testStyleCustomValid()
    {
        $this->expectNotToPerformAssertions();

        $app = AppFactory::create();
        $streamFactory = $app->getContainer()->get(StreamFactoryInterface::class);

        new OutputBufferingMiddleware($streamFactory, OutputBufferingMiddleware::APPEND);
        new OutputBufferingMiddleware($streamFactory, OutputBufferingMiddleware::PREPEND);
    }

    public function testStyleCustomInvalid()
    {
        $this->expectException(InvalidArgumentException::class);

        $app = AppFactory::create();
        $streamFactory = $app->getContainer()->get(StreamFactoryInterface::class);

        new OutputBufferingMiddleware($streamFactory, 'foo');
    }

    public function testAppend()
    {
        $app = AppFactory::create();

        $responseFactory = $app->getContainer()->get(ResponseFactoryInterface::class);
        $streamFactory = $app->getContainer()->get(StreamFactoryInterface::class);

        $outputBufferingMiddleware = new OutputBufferingMiddleware($streamFactory, OutputBufferingMiddleware::APPEND);
        $app->add($outputBufferingMiddleware);

        $middleware = function () use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        };
        $app->add($middleware);

        $app->addRoutingMiddleware();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->handle($request);

        $this->assertSame('BodyTest', (string)$response->getBody());
    }

    public function testPrepend()
    {
        $app = AppFactory::create();

        $responseFactory = $app->getContainer()->get(ResponseFactoryInterface::class);
        $streamFactory = $app->getContainer()->get(StreamFactoryInterface::class);

        $middleware = function ($request, $handler) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('Body');
            echo 'Test';

            return $response;
        };

        $outputBufferingMiddleware = new OutputBufferingMiddleware($streamFactory, OutputBufferingMiddleware::PREPEND);

        $app->add($outputBufferingMiddleware);
        $app->add($middleware);
        $app->addRoutingMiddleware();

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->handle($request);

        $this->assertSame('TestBody', (string)$response->getBody());
    }

    public function testOutputBufferIsCleanedWhenThrowableIsCaught()
    {
        $app = AppFactory::create();

        $streamFactory = $app->getContainer()->get(StreamFactoryInterface::class);

        $test = $this;
        $middleware = (function ($request, $handler) use ($test) {
            echo 'Test';
            $test->assertSame('Test', ob_get_contents());
            throw new Exception('Oops...');
        });

        $outputBufferingMiddleware = new OutputBufferingMiddleware($streamFactory, OutputBufferingMiddleware::PREPEND);

        $app->add($outputBufferingMiddleware);
        $app->add($middleware);
        $app->addRoutingMiddleware();

        $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        try {
            $app->handle($request);
        } catch (Exception $e) {
            $this->assertSame('', ob_get_contents());
        }
    }
}
