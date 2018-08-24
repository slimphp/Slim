<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Handlers;

use PHPUnit\Framework\TestCase;
use Slim\Error\Renderers\JsonErrorRenderer;
use Slim\Error\Renderers\PlainTextErrorRenderer;
use Slim\Error\Renderers\XmlErrorRenderer;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Tests\Mocks\MockCustomException;
use Slim\Tests\Mocks\MockErrorRenderer;
use ReflectionClass;

class ErrorHandlerTest extends TestCase
{
    public function testDetermineContentTypeMethodDoesNotThrowExceptionWhenPassedValidRenderer()
    {
        $handler = $this->getMockBuilder(ErrorHandler::class)->getMock();
        $class = new ReflectionClass(ErrorHandler::class);

        $reflectionProperty = $class->getProperty('renderer');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, MockErrorRenderer::class);

        $method = $class->getMethod('determineRenderer');
        $method->setAccessible(true);
        $method->invoke($handler);

        $this->addToAssertionCount(1);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDetermineContentTypeMethodThrowsExceptionWhenPassedAnInvalidRenderer()
    {
        $handler = $this->getMockBuilder(ErrorHandler::class)->getMock();
        $class = new ReflectionClass(ErrorHandler::class);

        $reflectionProperty = $class->getProperty('renderer');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, 'NonExistentRenderer::class');

        $method = $class->getMethod('determineRenderer');
        $method->setAccessible(true);
        $method->invoke($handler);
    }

    public function testDetermineRenderer()
    {
        $handler = $this->getMockBuilder(ErrorHandler::class)->getMock();
        $class = new ReflectionClass(ErrorHandler::class);

        $reflectionProperty = $class->getProperty('contentType');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, 'application/json');

        $method = $class->getMethod('determineRenderer');
        $method->setAccessible(true);

        $renderer = $method->invoke($handler);
        $this->assertInstanceOf(JsonErrorRenderer::class, $renderer);

        $reflectionProperty->setValue($handler, 'application/xml');
        $renderer = $method->invoke($handler);
        $this->assertInstanceOf(XmlErrorRenderer::class, $renderer);

        $reflectionProperty->setValue($handler, 'text/plain');
        $renderer = $method->invoke($handler);
        $this->assertInstanceOf(PlainTextErrorRenderer::class, $renderer);
    }

    public function testDetermineStatusCode()
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $handler = $this->getMockBuilder(ErrorHandler::class)->getMock();
        $class = new ReflectionClass(ErrorHandler::class);

        $reflectionProperty = $class->getProperty('exception');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, new HttpNotFoundException($request));

        $method = $class->getMethod('determineStatusCode');
        $method->setAccessible(true);

        $statusCode = $method->invoke($handler);
        $this->assertEquals($statusCode, 404);

        $reflectionProperty->setValue($handler, new MockCustomException());

        $statusCode = $method->invoke($handler);
        $this->assertEquals($statusCode, 500);
    }

    public function testHalfValidContentType()
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getHeaderLine')->will($this->returnValue('unknown/+json'));

        $handler = $this->getMockBuilder(ErrorHandler::class)->getMock();
        $newTypes = [
            'application/xml',
            'text/xml',
            'text/html',
        ];

        $class = new ReflectionClass(ErrorHandler::class);

        $reflectionProperty = $class->getProperty('knownContentTypes');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $newTypes);

        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        $contentType = $method->invoke($handler, $request);

        $this->assertEquals('text/html', $contentType);
    }

    /**
     * Ensure that an acceptable media-type is found in the Accept header even
     * if it's not the first in the list.
     */
    public function testAcceptableMediaTypeIsNotFirstInList()
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->expects($this->any())
            ->method('getHeaderLine')
            ->willReturn('text/plain,text/html');

        // provide access to the determineContentType() as it's a protected method
        $class = new ReflectionClass(ErrorHandler::class);
        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        // use a mock object here as ErrorHandler cannot be directly instantiated
        $handler = $this->getMockBuilder(ErrorHandler::class)->getMock();

        // call determineContentType()
        $return = $method->invoke($handler, $request);

        $this->assertEquals('text/html', $return);
    }

    public function testOptions()
    {
        $request = $this->getRequest('OPTIONS');
        $handler = new ErrorHandler();
        $exception = new HttpMethodNotAllowedException($request);
        $exception->setAllowedMethods(['POST', 'PUT']);

        /** @var Response $res */
        $res = $handler->__invoke($request, $exception, true, true, true);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertTrue($res->hasHeader('Allow'));
        $this->assertEquals('POST, PUT', $res->getHeaderLine('Allow'));
    }

    public function testWriteToErrorLog()
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->any())->method('getHeaderLine')->with('Accept')->willReturn('application/json');

        $handler = $this->getMockBuilder(ErrorHandler::class)
            ->setMethods(['writeToErrorLog', 'logError'])
            ->getMock();

        $exception = new HttpNotFoundException($request);

        $handler->expects($this->once())
                ->method('writeToErrorLog');

        $handler->__invoke($request, $exception, true, true, true);
    }

    /**
     * @param string $method
     * @return \PHPUnit_Framework_MockObject_MockObject|\Slim\Http\Request
     */
    protected function getRequest($method, $contentType = 'text/html')
    {
        $req = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getMethod')->will($this->returnValue($method));
        $req->expects($this->any())->method('getHeaderLine')->will($this->returnValue($contentType));
        return $req;
    }
}
