<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Handlers;

use PHPUnit\Framework\TestCase;
use Slim\Handlers\ErrorHandler;
use Slim\Http\Response;

class ErrorTest extends TestCase
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
    public function testErrorHandler($acceptHeader, $contentType, $startOfBody)
    {
        $errorHandler = new ErrorHandler();
        $e = new \Exception("Oops", 1, new \Exception('Previous oops'));

        /** @var Response $res */
        $res = $errorHandler->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $e);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * Test invalid method returns the correct code and content type with details
     *
     * @dataProvider errorProvider
     */
    public function testErrorHandlerDisplayDetails($acceptHeader, $contentType, $startOfBody)
    {
        $errorHandler = new ErrorHandler(true);
        $e = new \Exception('Oops', 1, new \Exception('Opps before'));

        /** @var Response $res */
        $res = $errorHandler->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $e);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * @expectedException \Error
     */
    public function testNotFoundContentType()
    {
        $errorMock = $this->getMockBuilder(ErrorHandler::class)->setMethods(['resolveContentType'])->getMock();
        $errorMock->method('resolveContentType')
            ->will($this->returnValue('unknown/type'));

        $e = new \Exception("Oops");

        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();

        $errorMock->__invoke($req, new Response(), $e);
    }

    /**
     * Test that an exception with a previous exception provides correct output
     * to the error log
     */
    public function testPreviousException()
    {
        $error = $this->getMockBuilder('\Slim\Handlers\ErrorHandler')->setMethods(['logError'])->getMock();
        $error->expects($this->once())->method('logError')->with(
            $this->logicalAnd(
                $this->stringContains("Type: Exception" . PHP_EOL . "Message: Second Oops"),
                $this->stringContains("Previous Error:" . PHP_EOL . "Type: Exception" . PHP_EOL . "Message: First Oops")
            )
        );

        $first = new \Exception("First Oops");
        $second = new \Exception("Second Oops", 0, $first);

        $error->__invoke($this->getRequest('GET', 'application/json'), new Response(), $second);
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
