<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Tests\TestCase;

class HttpExceptionTest extends TestCase
{
    public function testHttpExceptionRequestReponseGetterSetters()
    {
        $request = $this->createServerRequest('/');
        $exception = new HttpNotFoundException($request);

        $this->assertInstanceOf(ServerRequestInterface::class, $exception->getRequest());
    }

    public function testHttpExceptionAttributeGettersSetters()
    {
        $request = $this->createServerRequest('/');

        $exception = new HttpNotFoundException($request);
        $exception->setTitle('Title');
        $exception->setDescription('Description');

        $this->assertSame('Title', $exception->getTitle());
        $this->assertSame('Description', $exception->getDescription());
    }

    public function testHttpNotAllowedExceptionGetAllowedMethods()
    {
        $request = $this->createServerRequest('/');

        $exception = new HttpMethodNotAllowedException($request);
        $exception->setAllowedMethods(['GET']);
        $this->assertSame(['GET'], $exception->getAllowedMethods());
        $this->assertSame('Method not allowed. Must be one of: GET', $exception->getMessage());

        $exception = new HttpMethodNotAllowedException($request);
        $this->assertSame([], $exception->getAllowedMethods());
        $this->assertSame('Method not allowed.', $exception->getMessage());
    }
}
