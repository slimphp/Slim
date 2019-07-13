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
use Slim\Error\Renderers\HtmlErrorRenderer;
use Slim\Error\Renderers\JsonErrorRenderer;
use Slim\Error\Renderers\PlainTextErrorRenderer;
use Slim\Error\Renderers\XmlErrorRenderer;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler;
use Slim\Tests\Mocks\MockCustomException;
use Slim\Tests\TestCase;

class ErrorHandlerTest extends TestCase
{
    public function testDetermineRenderer()
    {
        $handler = $this
            ->getMockBuilder(ErrorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $class = new ReflectionClass(ErrorHandler::class);

        $callableResolverProperty = $class->getProperty('callableResolver');
        $callableResolverProperty->setAccessible(true);
        $callableResolverProperty->setValue($handler, $this->getCallableResolver());

        $reflectionProperty = $class->getProperty('contentType');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, 'application/json');

        $method = $class->getMethod('determineRenderer');
        $method->setAccessible(true);

        $renderer = $method->invoke($handler);
        $this->assertIsCallable($renderer);
        $this->assertInstanceOf(JsonErrorRenderer::class, $renderer[0]);

        $reflectionProperty->setValue($handler, 'application/xml');
        $renderer = $method->invoke($handler);
        $this->assertIsCallable($renderer);
        $this->assertInstanceOf(XmlErrorRenderer::class, $renderer[0]);

        $reflectionProperty->setValue($handler, 'text/plain');
        $renderer = $method->invoke($handler);
        $this->assertIsCallable($renderer);
        $this->assertInstanceOf(PlainTextErrorRenderer::class, $renderer[0]);

        // Test the default error renderer
        $reflectionProperty->setValue($handler, 'text/unknown');
        $renderer = $method->invoke($handler);
        $this->assertIsCallable($renderer);
        $this->assertInstanceOf(HtmlErrorRenderer::class, $renderer[0]);
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

    /**
     * Test if we can force the content type of all error handler responses.
     */
    public function testForceContentType()
    {
        $request = $this
            ->createServerRequest('/not-defined', 'GET')
            ->withHeader('Accept', 'text/plain,text/xml');

        $handler = new ErrorHandler($this->getCallableResolver(), $this->getResponseFactory());
        $handler->forceContentType('application/json');

        $exception = new HttpNotFoundException($request);

        /** @var ResponseInterface $response */
        $response = $handler->__invoke($request, $exception, false, false, false);

        $this->assertSame(['application/json'], $response->getHeader('Content-Type'));
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
        $newErrorRenderers = [
            'application/xml' => XmlErrorRenderer::class,
            'text/xml' => XmlErrorRenderer::class,
            'text/html' => HtmlErrorRenderer::class,
        ];

        $class = new ReflectionClass(ErrorHandler::class);

        $reflectionProperty = $class->getProperty('responseFactory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $this->getResponseFactory());

        $reflectionProperty = $class->getProperty('errorRenderers');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $newErrorRenderers);

        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        $contentType = $method->invoke($handler, $request);

        $this->assertNull($contentType);
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

        $errorRenderers = [
            'text/plain' => PlainTextErrorRenderer::class,
            'text/xml' => XmlErrorRenderer::class,
        ];

        $class = new ReflectionClass(ErrorHandler::class);

        $reflectionProperty = $class->getProperty('responseFactory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $this->getResponseFactory());

        $reflectionProperty = $class->getProperty('errorRenderers');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $errorRenderers);

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

        $errorRenderers = [
            'application/xml' => XmlErrorRenderer::class
        ];

        $class = new ReflectionClass(ErrorHandler::class);

        $reflectionProperty = $class->getProperty('responseFactory');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $this->getResponseFactory());

        $reflectionProperty = $class->getProperty('errorRenderers');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($handler, $errorRenderers);

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
            ->withHeader('Accept', 'text/plain,text/html');

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

    public function testRegisterErrorRenderer()
    {
        $handler = new ErrorHandler($this->getCallableResolver(), $this->getResponseFactory());
        $handler->registerErrorRenderer('application/slim', PlainTextErrorRenderer::class);

        $reflectionClass = new ReflectionClass(ErrorHandler::class);
        $reflectionProperty = $reflectionClass->getProperty('errorRenderers');
        $reflectionProperty->setAccessible(true);
        $errorRenderers = $reflectionProperty->getValue($handler);

        $this->assertArrayHasKey('application/slim', $errorRenderers);
    }

    public function testSetDefaultErrorRenderer()
    {
        $handler = new ErrorHandler($this->getCallableResolver(), $this->getResponseFactory());
        $handler->setDefaultErrorRenderer('text/plain', PlainTextErrorRenderer::class);

        $reflectionClass = new ReflectionClass(ErrorHandler::class);
        $reflectionProperty = $reflectionClass->getProperty('defaultErrorRenderer');
        $reflectionProperty->setAccessible(true);
        $defaultErrorRenderer = $reflectionProperty->getValue($handler);

        $defaultErrorRendererContentTypeProperty = $reflectionClass->getProperty('defaultErrorRendererContentType');
        $defaultErrorRendererContentTypeProperty->setAccessible(true);
        $defaultErrorRendererContentType = $defaultErrorRendererContentTypeProperty->getValue($handler);

        $this->assertEquals(PlainTextErrorRenderer::class, $defaultErrorRenderer);
        $this->assertEquals('text/plain', $defaultErrorRendererContentType);
    }

    public function testOptions()
    {
        $request = $this->createServerRequest('/', 'OPTIONS');
        $handler = new ErrorHandler($this->getCallableResolver(), $this->getResponseFactory());
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
            ->setConstructorArgs([
                'callableResolver' => $this->getCallableResolver(),
                'responseFactory' => $this->getResponseFactory(),
            ])
            ->setMethods(['writeToErrorLog', 'logError'])
            ->getMock();

        $exception = new HttpNotFoundException($request);

        $handler
            ->expects($this->once())
            ->method('writeToErrorLog');

        $handler->__invoke($request, $exception, true, true, true);
    }

    public function testDefaultErrorRenderer()
    {
        $request = $this
            ->createServerRequest('/', 'GET')
            ->withHeader('Accept', 'application/unknown');

        $handler = new ErrorHandler($this->getCallableResolver(), $this->getResponseFactory());
        $exception = new \RuntimeException();

        /** @var ResponseInterface $res */
        $res = $handler->__invoke($request, $exception, true, true, true);

        $this->assertTrue($res->hasHeader('Content-Type'));
        $this->assertEquals('text/html', $res->getHeaderLine('Content-Type'));
    }
}
