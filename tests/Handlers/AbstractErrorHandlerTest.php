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
use Slim\Handlers\AbstractErrorHandler;

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

        $class = new \ReflectionClass(AbstractErrorHandler::class);

        $reflectionProperty = $class->getProperty('knownContentTypes');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($abstractHandler, $newTypes);

        $method = $class->getMethod('resolveContentType');
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

        // provide access to the resolveContentType() as it's a protected method
        $class = new \ReflectionClass(AbstractErrorHandler::class);
        $method = $class->getMethod('resolveContentType');
        $method->setAccessible(true);

        // use a mock object here as AbstractErrorHandler cannot be directly instantiated
        $abstractHandler = $this->getMockForAbstractClass(AbstractErrorHandler::class);

        // call resolveContentType()
        $return = $method->invoke($abstractHandler, $request);

        $this->assertEquals('text/html', $return);
    }
}
