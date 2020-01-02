<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Exception;

use Slim\Exception\HttpUnauthorizedException;
use Slim\Tests\TestCase;

class HttpUnauthorizedExceptionTest extends TestCase
{
    public function testHttpUnauthorizedException()
    {
        $request = $this->createServerRequest('/');
        $exception = new HttpUnauthorizedException($request);

        $this->assertInstanceOf(HttpUnauthorizedException::class, $exception);
    }

    public function testHttpUnauthorizedExceptionWithMessage()
    {
        $request = $this->createServerRequest('/');
        $exception = new HttpUnauthorizedException($request, 'Hello World');

        $this->assertSame('Hello World', $exception->getMessage());
    }
}
