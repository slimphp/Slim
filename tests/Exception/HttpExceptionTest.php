<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Http;

use PHPUnit\Framework\TestCase;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Http\Request;

class HttpExceptionTest extends TestCase
{
    public function testHttpExceptionRequestReponseGetterSetters()
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $exception = new HttpNotFoundException($request);

        $this->assertInstanceOf(Request::class, $exception->getRequest());
    }

    public function testHttpExceptionAttributeGettersSetters()
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $exception = new HttpNotFoundException($request);
        $exception->setTitle('Title');
        $exception->setDescription('Description');

        $this->assertEquals('Title', $exception->getTitle());
        $this->assertEquals('Description', $exception->getDescription());
    }

    public function testHttpNotAllowedExceptionGetAllowedMethods()
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $exception = new HttpMethodNotAllowedException($request);
        $exception->setAllowedMethods(['GET']);
        $this->assertEquals(['GET'], $exception->getAllowedMethods());

        $exception = new HttpMethodNotAllowedException($request);
        $this->assertEquals([], $exception->getAllowedMethods());
    }
}
