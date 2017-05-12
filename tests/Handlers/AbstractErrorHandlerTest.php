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
use Slim\Exception\HttpNotAllowedException;
use Slim\Handlers\AbstractErrorHandler;
use Slim\Handlers\ErrorHandler;
use Slim\Http\Response;
use ReflectionClass;

class AbstractErrorHandlerTest extends TestCase
{
    public function testHalfValidContentType()
    {
        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();

        $req->expects($this->any())->method('getHeaderLine')->will($this->returnValue('unknown/+json'));

        $abstractHandler = $this->getMockForAbstractClass(AbstractErrorHandler::class);

        $newTypes = [
            'application/xml',
            'text/xml',
            'text/html',
        ];

        $class = new ReflectionClass(AbstractErrorHandler::class);

        $reflectionProperty = $class->getProperty('knownContentTypes');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($abstractHandler, $newTypes);

        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        $return = $method->invoke($abstractHandler, $req);

        $this->assertEquals('text/html', $return);
    }

    /**
     * Ensure that an acceptable media-type is found in the Accept header even
     * if it's not the first in the list.
     */
    public function testAcceptableMediaTypeIsNotFirstInList()
    {
        $request = $this->getMockBuilder('Slim\Http\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $request->expects($this->any())
            ->method('getHeaderLine')
            ->willReturn('text/plain,text/html');

        // provide access to the determineContentType() as it's a protected method
        $class = new ReflectionClass(AbstractErrorHandler::class);
        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        // use a mock object here as AbstractErrorHandler cannot be directly instantiated
        $abstractHandler = $this->getMockForAbstractClass(AbstractErrorHandler::class);

        // call determineContentType()
        $return = $method->invoke($abstractHandler, $request);

        $this->assertEquals('text/html', $return);
    }

    public function testOptions()
    {
        $handler = new ErrorHandler();
        $exception = new HttpNotAllowedException();
        $exception->setAllowedMethods(['POST', 'PUT']);
        /** @var Response $res */
        $res = $handler->__invoke($this->getRequest('OPTIONS'), new Response(), $exception);
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
