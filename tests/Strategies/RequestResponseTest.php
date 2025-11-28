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
use Slim\Factory\AppFactory;
use Slim\Strategies\RequestResponse;
use Slim\Tests\Traits\AppTestTrait;

final class RequestResponseTest extends TestCase
{
    use AppTestTrait;

    public function testInvokeWithArguments()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $invocationStrategy = $app->getContainer()->get(RequestResponse::class);

        $args = [
            'name' => 'John',
            'foo' => 'bar',
        ];

        $callback = function ($request, $response, $args) {
            return $response
                ->withHeader('X-Name', $args['name'])
                ->withHeader('X-Foo', $args['foo']);
        };

        $response = $invocationStrategy($callback, $request, $response, $args);

        $this->assertSame('John', $response->getHeaderLine('X-Name'));
        $this->assertSame('bar', $response->getHeaderLine('X-Foo'));
    }

    public function testInvokeWithoutArguments()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $invocationStrategy = $app->getContainer()->get(RequestResponse::class);

        $callback = function ($request, $response) {
            return $response->withHeader('X-Foo', 'Default');
        };

        $args = [];

        $response = $invocationStrategy($callback, $request, $response, $args);

        $this->assertSame('Default', $response->getHeaderLine('X-Foo'));
    }
}
