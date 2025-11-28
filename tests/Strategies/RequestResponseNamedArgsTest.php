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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Strategies\RequestResponseNamedArgs;
use Slim\Tests\Traits\AppTestTrait;

final class RequestResponseNamedArgsTest extends TestCase
{
    use AppTestTrait;

    private ServerRequestInterface $request;
    private ResponseInterface $response;

    public function testCallingWithEmptyArguments()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $args = [];
        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response) {
            return $response;
        };

        $this->assertSame($response, $invocationStrategy($callback, $request, $response, $args));
    }

    public function testCallingWithKnownArguments()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $args = [
            'name' => 'world',
            'greeting' => 'hello',
        ];

        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response, $greeting, string $name) use ($args) {
            $this->assertSame($greeting, $args['greeting']);
            $this->assertSame($name, $args['name']);

            return $response;
        };

        $this->assertSame($response, $invocationStrategy($callback, $request, $response, $args));
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

        $args = [
            'name' => 'world',
        ];

        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response, $greeting = 'Hello', $name = 'Rob') use ($args) {
            $this->assertSame('Hello', $greeting);
            $this->assertSame($args['name'], $name);

            return $response;
        };

        $this->assertSame($response, $invocationStrategy($callback, $request, $response, $args));
    }

    public function testCallingWithUnknownAndVariadic()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $args = [
            'name' => 'world',
            'greeting' => 'hello',
        ];

        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response, ...$arguments) use ($args) {
            $this->assertSame($args, $arguments);

            return $response;
        };

        $this->assertSame($response, $invocationStrategy($callback, $request, $response, $args));
    }

    public function testCallingWithMixedKnownAndUnknownParametersAndVariadic()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $response = $app->getContainer()
            ->get(ResponseFactoryInterface::class)
            ->createResponse();

        $known = [
            'name' => 'world',
            'greeting' => 'hello',
        ];
        $unknown = [
            'foo' => 'foo',
            'bar' => 'bar',
        ];
        $args = array_merge($known, $unknown);
        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response, $name, $greeting, ...$arguments) use ($known, $unknown) {
            $this->assertSame($name, $known['name']);
            $this->assertSame($greeting, $known['greeting']);
            $this->assertSame($unknown, $arguments);

            return $response;
        };

        $this->assertSame($response, $invocationStrategy($callback, $request, $response, $args));
    }
}
