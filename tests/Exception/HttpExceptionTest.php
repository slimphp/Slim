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
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Tests\Test;

class HttpExceptionTest extends Test
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

        $this->assertEquals('Title', $exception->getTitle());
        $this->assertEquals('Description', $exception->getDescription());
    }

    public function testHttpNotAllowedExceptionGetAllowedMethods()
    {
        $request = $this->createServerRequest('/');

        $exception = new HttpMethodNotAllowedException($request);
        $exception->setAllowedMethods(['GET']);
        $this->assertEquals(['GET'], $exception->getAllowedMethods());

        $exception = new HttpMethodNotAllowedException($request);
        $this->assertEquals([], $exception->getAllowedMethods());
    }
}
