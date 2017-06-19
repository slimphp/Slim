<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Handlers;

use Doctrine\Instantiator\Exception\UnexpectedValueException;
use PHPUnit\Framework\TestCase;
use Slim\Handlers\PhpError;
use Slim\Http\Response;

class PhpErrorTest extends TestCase
{
    public function phpErrorProvider()
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
     * @requires PHP 7.0
     * @dataProvider phpErrorProvider
     */
    public function testPhpError($acceptHeader, $contentType, $startOfBody)
    {
        $error = new PhpError();

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), new \Exception());

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @requires PHP 7.0
     * @dataProvider phpErrorProvider
     */
    public function testPhpErrorDisplayDetails($acceptHeader, $contentType, $startOfBody)
    {
        $error = new PhpError(true);

        $exception = new \Exception('Oops', 1, new \Exception('Opps before'));

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $exception);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * @requires PHP 7.0
     * @expectedException \UnexpectedValueException
     */
    public function testNotFoundContentType()
    {
        $errorMock = $this->getMockBuilder(PhpError::class)->setMethods(['determineContentType'])->getMock();
        $errorMock->method('determineContentType')
            ->will($this->returnValue('unknown/type'));

        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();

        $errorMock->__invoke($req, new Response(), new \Exception());
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @requires PHP 5.0
     * @dataProvider phpErrorProvider
     */
    public function testPhpError5($acceptHeader, $contentType, $startOfBody)
    {
        $this->skipIfPhp70();
        $error = new PhpError();

        $throwable = $this->getMockBuilder('\Throwable')
            ->setMethods(['getCode', 'getMessage', 'getFile', 'getLine', 'getTraceAsString', 'getPrevious'])->getMock();

        /** @var \Throwable $throwable */

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $throwable);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }



    /**
     * Test invalid method returns the correct code and content type
     *
     * @dataProvider phpErrorProvider
     */
    public function testPhpErrorDisplayDetails5($acceptHeader, $contentType, $startOfBody)
    {
        $this->skipIfPhp70();

        $error = new PhpError(true);

        $throwable = $this->getMockBuilder('\Throwable')
            ->setMethods(['getCode', 'getMessage', 'getFile', 'getLine', 'getTraceAsString', 'getPrevious'])->getMock();

        $throwablePrev = clone $throwable;

        $throwable->method('getCode')->will($this->returnValue(1));
        $throwable->method('getMessage')->will($this->returnValue('Oops'));
        $throwable->method('getFile')->will($this->returnValue('test.php'));
        $throwable->method('getLine')->will($this->returnValue('1'));
        $throwable->method('getTraceAsString')->will($this->returnValue('This is error'));
        $throwable->method('getPrevious')->will($this->returnValue($throwablePrev));

        /** @var \Throwable $throwable */

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $throwable);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * @requires PHP 5.0
     * @expectedException \UnexpectedValueException
     */
    public function testNotFoundContentType5()
    {
        $this->skipIfPhp70();
        $errorMock = $this->getMockBuilder(PhpError::class)->setMethods(['determineContentType'])->getMock();

        $errorMock->method('determineContentType')
            ->will($this->returnValue('unknown/type'));

        $throwable = $this->getMockBuilder('\Throwable')->getMock();
        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();

        $errorMock->__invoke($req, new Response(), $throwable);
    }

    /**
     * @param string $method
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slim\Http\Request
     */
    protected function getRequest($method, $acceptHeader)
    {
        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getHeaderLine')->will($this->returnValue($acceptHeader));

        return $req;
    }

    /**
     * @return mixed
     */
    protected function skipIfPhp70()
    {
        if (version_compare(PHP_VERSION, '7.0', '>=')) {
            $this->markTestSkipped();
        }
    }
}
