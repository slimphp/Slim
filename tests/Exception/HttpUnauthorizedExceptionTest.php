<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Factory\AppFactory;
use Slim\Tests\Traits\AppTestTrait;

final class HttpUnauthorizedExceptionTest extends TestCase
{
    use AppTestTrait;

    public function testHttpUnauthorizedException()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $exception = new HttpUnauthorizedException($request);

        $this->assertInstanceOf(HttpUnauthorizedException::class, $exception);
    }

    public function testHttpUnauthorizedExceptionWithMessage()
    {
        $app = AppFactory::create();

        $request = $app->getContainer()
            ->get(ServerRequestFactoryInterface::class)
            ->createServerRequest('GET', '/');

        $exception = new HttpUnauthorizedException($request, 'Hello World');

        $this->assertSame('Hello World', $exception->getMessage());
    }
}
