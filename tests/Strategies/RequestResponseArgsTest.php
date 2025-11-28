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
use Slim\Strategies\RequestResponseArgs;
use Slim\Tests\Traits\AppTestTrait;

final class RequestResponseArgsTest extends TestCase
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

        $invocationStrategy = $app->getContainer()->get(RequestResponseArgs::class);

        $args = [
            'name' => 'John',
            'age' => '30',
        ];

        $callback = function ($request, $response, $name, $age) {
            return $response->withHeader('X-Name', $name)
                ->withHeader('X-Age', $age);
        };

        $response = $invocationStrategy($callback, $request, $response, $args);

        $this->assertSame('John', $response->getHeaderLine('X-Name'));
        $this->assertSame('30', $response->getHeaderLine('X-Age'));
    }

    public function testInvokeWithSingleArgument()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $invocationStrategy = $app->getContainer()->get(RequestResponseArgs::class);

        $args = [
            'name' => 'John',
        ];

        $callback = function ($request, $response, $name) {
            return $response->withHeader('X-Name', $name);
        };

        $response = $invocationStrategy($callback, $request, $response, $args);

        $this->assertSame('John', $response->getHeaderLine('X-Name'));
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

        $invocationStrategy = $app->getContainer()->get(RequestResponseArgs::class);

        $args = [];

        $callback = function ($request, $response) {
            return $response->withHeader('X-Status', 'NoArgs');
        };

        $response = $invocationStrategy($callback, $request, $response, $args);

        $this->assertSame('NoArgs', $response->getHeaderLine('X-Status'));
    }
}
