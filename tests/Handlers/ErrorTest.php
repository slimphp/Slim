<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
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
            ['application/json', '{'],
            ['application/xml', '<root>'],
            ['text/html', '<html>'],
        ];
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @dataProvider errorProvider
     */
    public function testError($contentType, $startOfBody)
    {
        $notAllowed = new Error();
        $e  = new \Exception("Oops");

        /** @var Response $res */
        $res = $notAllowed->__invoke($this->getRequest('GET', $contentType), new Response(), $e);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slim\Http\Request
     */
    protected function getRequest($method, $contentType = 'text/html')
    {
        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getHeaderLine')->will($this->returnValue($contentType));

        return $req;
    }
}
