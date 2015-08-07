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
    public function testInvalidMethod()
    {
        $notAllowed = new NotAllowed();

        /** @var Response $res */
        $res = $notAllowed->__invoke($this->getRequest('GET'), new Response(), ['POST', 'PUT']);

        $this->assertSame(405, $res->getStatusCode());
        $this->assertTrue($res->hasHeader('Allow'));
        $this->assertEquals('POST, PUT', $res->getHeaderLine('Allow'));
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
    protected function getRequest($method)
    {
        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getMethod')->will($this->returnValue($method));

        return $req;
    }
}
