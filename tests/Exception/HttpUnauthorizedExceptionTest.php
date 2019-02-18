<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Exception;

use Psr\Http\Message\ServerRequestInterface;
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
