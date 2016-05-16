<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Handlers;

use Slim\Handlers\Error;
use Slim\Http\Response;

class ErrorTest extends \PHPUnit_Framework_TestCase
{
    public function errorProvider()
    {
        return [
            ['application/json', 'application/json', '{'],
            ['application/vnd.api+json', 'application/json', '{'],
            ['application/xml', 'application/xml', '<error>'],
            ['application/hal+xml', 'application/xml', '<error>'],
            ['text/xml', 'text/xml', '<error>'],
            ['text/html', 'text/html', '<html>'],
        ];
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @dataProvider errorProvider
     */
    public function testError($acceptHeader, $contentType, $startOfBody)
    {
        $error = new Error();
        $e = new \Exception("Oops");

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $e);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * Test the exception rendering runs to completion (no infinite loop)
     *
     * @dataProvider errorProvider
     */
    public function testErrorExceptionWithPrevious($acceptHeader, $contentType, $startOfBody)
    {
        $error = new Error();
        $e = new \Exception("Oops", 1, new \Exception());

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $e);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
        // if we have reached this point, there is no infinite loop, otherwise an out of memory error will occur
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slim\Http\Request
     */
    protected function getRequest($method, $acceptHeader)
    {
        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getHeaderLine')->will($this->returnValue($acceptHeader));

        return $req;
    }
}
