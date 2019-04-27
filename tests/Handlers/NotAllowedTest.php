<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Handlers;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Slim\Handlers\NotAllowed;
use Slim\Http\Request;
use Slim\Http\Response;

class NotAllowedTest extends PHPUnit_Framework_TestCase
{
    public function invalidMethodProvider()
    {
        return [
            ['application/json', 'application/json', '{'],
            ['application/vnd.api+json', 'application/json', '{'],
            ['application/xml', 'application/xml', '<root>'],
            ['application/hal+xml', 'application/xml', '<root>'],
            ['text/xml', 'text/xml', '<root>'],
            ['text/html', 'text/html', '<html>'],
        ];
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @dataProvider invalidMethodProvider
     */
    public function testInvalidMethod($acceptHeader, $contentType, $startOfBody)
    {
        $notAllowed = new NotAllowed();

        /** @var Response $res */
        $res = $notAllowed->__invoke($this->getRequest('GET', $acceptHeader), new Response(), ['POST', 'PUT']);

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

    public function testNotFoundContentType()
    {
        $errorMock = $this->getMockBuilder(NotAllowed::class)->setMethods(['determineContentType'])->getMock();
        $errorMock->method('determineContentType')
            ->will($this->returnValue('unknown/type'));

        $this->setExpectedException('\UnexpectedValueException');
        $errorMock->__invoke($this->getRequest('GET', 'unknown/type'), new Response(), ['POST']);
    }

    /**
     * @param string $method
     *
     * @return PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected function getRequest($method, $contentType = 'text/html')
    {
        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getMethod')->will($this->returnValue($method));
        $req->expects($this->any())->method('getHeaderLine')->will($this->returnValue($contentType));

        return $req;
    }
}
