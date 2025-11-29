<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Strategies;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Strategy\RequestHandler;
use Slim\Tests\Traits\AppTestTrait;

final class RequestHandlerTest extends TestCase
{
    use AppTestTrait;

    public function testInvokeReturnsResponse()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $invocationStrategy = $app->getContainer()->get(RequestHandler::class);

        $callback = function (ServerRequestInterface $request) use ($response) {
            return $response->withHeader('X-Result', 'Success');
        };

        $resultResponse = $invocationStrategy($callback, $request, $response, []);

        $this->assertSame('Success', $resultResponse->getHeaderLine('X-Result'));
    }

    public function testInvokeWithModifiedRequest()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/')
            ->withHeader('X-Test', 'Modified');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $invocationStrategy = $app->getContainer()->get(RequestHandler::class);

        $callback = function (ServerRequestInterface $request) use ($response) {
            $headerValue = $request->getHeaderLine('X-Test');

            return $response->withHeader('X-Test-Result', $headerValue);
        };

        $resultResponse = $invocationStrategy($callback, $request, $response, []);

        $this->assertSame('Modified', $resultResponse->getHeaderLine('X-Test-Result'));
    }
}
