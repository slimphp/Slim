<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Handlers;

use Slim\Handlers\NotAllowed;
use Slim\Http\Response;

class NotAllowedTest extends \PHPUnit_Framework_TestCase
{
    public function invalidMethodProvider()
    {
        return [
            ['application/json', '{'],
            ['application/xml', '<root>'],
            ['text/xml', '<root>'],
            ['text/html', '<html>'],
        ];
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @dataProvider invalidMethodProvider
     */
    public function testInvalidMethod($contentType, $startOfBody)
    {
        $notAllowed = new NotAllowed();

        /** @var Response $res */
        $res = $notAllowed->__invoke($this->getRequest('GET', $contentType), new Response(), ['POST', 'PUT']);

        $this->assertSame(405, $res->getStatusCode());
        $this->assertTrue($res->hasHeader('Allow'));
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals('POST, PUT', $res->getHeaderLine('Allow'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    public function testOptions()
    {
        $notAllowed = new NotAllowed();

        /** @var Response $res */
        $res = $notAllowed->__invoke($this->getRequest('OPTIONS'), new Response(), ['POST', 'PUT']);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertTrue($res->hasHeader('Allow'));
        $this->assertEquals('POST, PUT', $res->getHeaderLine('Allow'));
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slim\Http\Request
     */
    protected function getRequest($method, $contentType = 'text/html')
    {
        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getMethod')->will($this->returnValue($method));
        $req->expects($this->any())->method('getHeaderLine')->will($this->returnValue($contentType));

        return $req;
    }
}
