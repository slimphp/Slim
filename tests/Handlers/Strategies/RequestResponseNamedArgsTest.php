<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Handlers\Strategies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\Strategies\RequestResponseNamedArgs;
use Slim\Tests\TestCase;

class RequestResponseNamedArgsTest extends TestCase
{
    private ServerRequestInterface $request;
    private ResponseInterface $response;

    private const PHP_8_0_VERSION_ID = 80000;

    public function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
    }

    public function testCallingWithEmptyArguments()
    {
        if (PHP_VERSION_ID < self::PHP_8_0_VERSION_ID) {
            $this->markTestSkipped('Named arguments are not supported in PHP versions prior to 8.0');
        }

        $args = [];
        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);

            return $response;
        };

        $this->assertSame($this->response, $invocationStrategy($callback, $this->request, $this->response, $args));
    }

    public function testCallingWithKnownArguments()
    {
        if (PHP_VERSION_ID < self::PHP_8_0_VERSION_ID) {
            $this->markTestSkipped('Named arguments are not supported in PHP versions prior to 8.0');
        }

        $args = [
            'name' => 'world',
            'greeting' => 'hello',
        ];

        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response, $greeting, $name) use ($args) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);
            $this->assertSame($greeting, $args['greeting']);
            $this->assertSame($name, $args['name']);

            return $response;
        };

        $this->assertSame($this->response, $invocationStrategy($callback, $this->request, $this->response, $args));
    }

    public function testCallingWithOptionalArguments()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Named arguments are not supported in PHP versions prior to 8.0');
        }

        $args = [
            'name' => 'world',
        ];

        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response, $greeting = 'Hello', $name = 'Rob') use ($args) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);
            $this->assertSame($greeting, 'Hello');
            $this->assertSame($name, $args['name']);

            return $response;
        };

        $this->assertSame($this->response, $invocationStrategy($callback, $this->request, $this->response, $args));
    }

    public function testCallingWithUnknownAndVariadic()
    {
        if (PHP_VERSION_ID < self::PHP_8_0_VERSION_ID) {
            $this->markTestSkipped('Named arguments are not supported in PHP versions prior to 8.0');
        }

        $args = [
            'name' => 'world',
            'greeting' => 'hello',
        ];

        $invocationStrategy = new RequestResponseNamedArgs();

        $callback = function ($request, $response, ...$arguments) use ($args) {
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);
            $this->assertSame($args, $arguments);

            return $response;
        };

        $this->assertSame($this->response, $invocationStrategy($callback, $this->request, $this->response, $args));
    }

    public function testCallingWithMixedKnownAndUnknownParametersAndVariadic()
    {
        if (PHP_VERSION_ID < self::PHP_8_0_VERSION_ID) {
            $this->markTestSkipped('Named arguments are not supported in PHP versions prior to 8.0');
        }

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
            $this->assertSame($this->request, $request);
            $this->assertSame($this->response, $response);
            $this->assertSame($name, $known['name']);
            $this->assertSame($greeting, $known['greeting']);
            $this->assertSame($unknown, $arguments);

            return $response;
        };

        $this->assertSame($this->response, $invocationStrategy($callback, $this->request, $this->response, $args));
    }
}
