<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Handlers;

use PHPUnit_Framework_TestCase;
use ReflectionClass;
use Slim\Handlers\AbstractHandler;

class AbstractHandlerTest extends PHPUnit_Framework_TestCase
{
    public function testHalfValidContentType()
    {
        $req = $this->getMockBuilder('Slim\Http\Request')->disableOriginalConstructor()->getMock();

        $req->expects($this->any())->method('getHeaderLine')->will($this->returnValue('unknown/+json'));

        $abstractHandler = $this->getMockForAbstractClass(AbstractHandler::class);

        $newTypes = [
            'application/xml',
            'text/xml',
            'text/html',
        ];

        $class = new ReflectionClass(AbstractHandler::class);

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
        $class = new ReflectionClass(AbstractHandler::class);
        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        // use a mock object here as AbstractHandler cannot be directly instantiated
        $abstractHandler = $this->getMockForAbstractClass(AbstractHandler::class);

        // call determineContentType()
        $return = $method->invoke($abstractHandler, $request);

        $this->assertEquals('text/html', $return);
    }
}
