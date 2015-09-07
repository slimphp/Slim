<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Handlers;

use Slim\Handlers\NotFound;
use Slim\Http\Response;
use Slim\Http\Uri;

class NotFoundTest extends \PHPUnit_Framework_TestCase
{
    public function notFoundProvider()
    {
        return [
            ['application/json', '{'],
            ['application/xml', '<root>'],
            ['text/html', '<html>'],
        ];
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @dataProvider notFoundProvider
     */
    public function testNotFound($contentType, $startOfBody)
    {
        $notAllowed = new NotFound();

        /** @var Response $res */
        $res = $notAllowed->__invoke($this->getRequest('GET', $contentType), new Response(), ['POST', 'PUT']);

        $this->assertSame(404, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slim\Http\Request
     */
    protected function getRequest($method, $contentType = 'text/html')
    {
        $uri = new Uri('http', 'example.com', 80, '/notfound');

        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getHeaderLine')->will($this->returnValue($contentType));
        $req->expects($this->any())->method('getUri')->will($this->returnValue($uri));

        return $req;
    }
}
