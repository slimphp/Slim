<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Strategies;

use Invoker\Exception\NotEnoughParametersException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Slim\Factory\AppFactory;
use Slim\Strategies\RequestResponseTypedArgs;
use Slim\Tests\Traits\AppTestTrait;

final class RequestResponseTypedArgsTest extends TestCase
{
    use AppTestTrait;

    public function testCallingWithEmptyArguments()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $invocationStrategy = $app->getContainer()->get(RequestResponseTypedArgs::class);

        $args = [
            'name' => 'John',
        ];

        $callback = function ($request, $response, $name) {
            return $response->withHeader('X-Foo', $name);
        };

        $response = $invocationStrategy($callback, $request, $response, $args);

        $this->assertSame('John', $response->getHeaderLine('X-Foo'));
    }

    // https://github.com/slimphp/Slim/issues/3198
    public function testCallingWithKnownArguments()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $invocationStrategy = $app->getContainer()->get(RequestResponseTypedArgs::class);

        $args = [
            'name' => 'John',
            'id' => '123',
        ];

        $callback = function ($request, $response, string $name, int $id) {
            $this->assertSame('John', $name);
            $this->assertSame(123, $id);

            return $response->withHeader('X-Foo', $name);
        };

        $response = $invocationStrategy($callback, $request, $response, $args);

        $this->assertSame('John', $response->getHeaderLine('X-Foo'));
    }

    public function testCallingWithOptionalArguments()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $invocationStrategy = $app->getContainer()->get(RequestResponseTypedArgs::class);

        $args = [
            'name' => 'world',
        ];

        $callback = function ($request, $response, string $greeting = 'Hello', string $name = 'Rob') {
            $this->assertSame('Hello', $greeting);
            $this->assertSame('world', $name);

            return $response->withHeader('X-Foo', $name);
        };

        $response = $invocationStrategy($callback, $request, $response, $args);

        $this->assertSame('world', $response->getHeaderLine('X-Foo'));
    }

    public function testCallingWithNotEnoughParameters()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $invocationStrategy = $app->getContainer()->get(RequestResponseTypedArgs::class);

        $this->expectException(NotEnoughParametersException::class);
        $args = [
            'greeting' => 'hello',
        ];

        $callback = function ($request, $response, $arguments) use ($args) {
            $this->assertSame($args, $arguments);

            return $response->withHeader('X-Foo', $args['greeting']);
        };

        $response = $invocationStrategy($callback, $request, $response, $args);

        $this->assertSame('hello', $response->getHeaderLine('X-Foo'));
    }
}
