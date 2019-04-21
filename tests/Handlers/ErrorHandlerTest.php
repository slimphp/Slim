<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Handlers;

use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use Slim\Error\Renderers\JsonErrorRenderer;
use Slim\Error\Renderers\PlainTextErrorRenderer;
use Slim\Error\Renderers\XmlErrorRenderer;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;
use Slim\Tests\Mocks\MockCustomException;
use Slim\Tests\Mocks\MockErrorRenderer;
use Slim\Tests\TestCase;

class ErrorHandlerTest extends TestCase
{
    public function testDetermineContentTypeMethodDoesNotThrowExceptionWhenPassedValidRenderer()
    {
        $handler = $this
            ->getMockBuilder(ErrorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $handler = $this
            ->getMockBuilder(ErrorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $handler = $this
            ->getMockBuilder(ErrorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $request = $this->createServerRequest('/');
        $handler = $this
            ->getMockBuilder(ErrorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $class = new ReflectionClass(ErrorHandler::class);

        $reflectionProperty = $class->getProperty('responseFactory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $this->getResponseFactory());

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
        $request = $this
            ->createServerRequest('/', 'GET')
            ->withHeader('Content-Type', 'unknown/json+');

        $handler = $this
            ->getMockBuilder(ErrorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $newTypes = [
            'application/xml',
            'text/xml',
            'text/html',
        ];

        $class = new ReflectionClass(ErrorHandler::class);

        $reflectionProperty = $class->getProperty('responseFactory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $this->getResponseFactory());

        $reflectionProperty = $class->getProperty('knownContentTypes');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $newTypes);

        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        $contentType = $method->invoke($handler, $request);

        $this->assertEquals('text/html', $contentType);
    }

    public function testDetermineContentTypeTextPlainMultiAcceptHeader()
    {
        $request = $this
            ->createServerRequest('/', 'GET')
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('Accept', 'text/plain,text/xml');

        $handler = $this
            ->getMockBuilder(ErrorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $knownContentTypes = [
            'text/plain',
            'text/xml',
        ];

        $class = new ReflectionClass(ErrorHandler::class);

        $reflectionProperty = $class->getProperty('responseFactory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $this->getResponseFactory());

        $reflectionProperty = $class->getProperty('knownContentTypes');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $knownContentTypes);

        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        $contentType = $method->invoke($handler, $request);

        $this->assertEquals('text/xml', $contentType);
    }

    public function testDetermineContentTypeApplicationJsonOrXml()
    {
        $request = $this
            ->createServerRequest('/', 'GET')
            ->withHeader('Content-Type', 'text/json')
            ->withHeader('Accept', 'application/xhtml+xml');

        $handler = $this
            ->getMockBuilder(ErrorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $knownContentTypes = [
            'application/xml'
        ];

        $class = new ReflectionClass(ErrorHandler::class);

        $reflectionProperty = $class->getProperty('responseFactory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $this->getResponseFactory());

        $reflectionProperty = $class->getProperty('knownContentTypes');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $knownContentTypes);

        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        $contentType = $method->invoke($handler, $request);

        $this->assertEquals('application/xml', $contentType);
    }

    /**
     * Ensure that an acceptable media-type is found in the Accept header even
     * if it's not the first in the list.
     */
    public function testAcceptableMediaTypeIsNotFirstInList()
    {
        $request = $this
            ->createServerRequest('/', 'GET')
            ->withHeader('Content-Type', 'text/plain,text/html');

        // provide access to the determineContentType() as it's a protected method
        $class = new ReflectionClass(ErrorHandler::class);
        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        $reflectionProperty = $class->getProperty('responseFactory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($class, $this->getResponseFactory());

        // use a mock object here as ErrorHandler cannot be directly instantiated
        $handler = $this
            ->getMockBuilder(ErrorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        // call determineContentType()
        $return = $method->invoke($handler, $request);

        $this->assertEquals('text/html', $return);
    }

    public function testOptions()
    {
        $request = $this->createServerRequest('/', 'OPTIONS');
        $handler = new ErrorHandler($this->getResponseFactory());
        $exception = new HttpMethodNotAllowedException($request);
        $exception->setAllowedMethods(['POST', 'PUT']);

        /** @var ResponseInterface $res */
        $res = $handler->__invoke($request, $exception, true, true, true);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertTrue($res->hasHeader('Allow'));
        $this->assertEquals('POST, PUT', $res->getHeaderLine('Allow'));
    }

    public function testWriteToErrorLog()
    {
        $request = $this
            ->createServerRequest('/', 'GET')
            ->withHeader('Accept', 'application/json');

        $handler = $this->getMockBuilder(ErrorHandler::class)
            ->setConstructorArgs(['responseFactory' => $this->getResponseFactory()])
            ->setMethods(['writeToErrorLog', 'logError'])
            ->getMock();

        $exception = new HttpNotFoundException($request);

        $handler
            ->expects($this->once())
            ->method('writeToErrorLog');

        $handler->__invoke($request, $exception, true, true, true);
    }
}
