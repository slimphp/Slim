<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/master/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Handlers;

use Slim\Handlers\AbstractHandler;

class AbstractHandlerTest extends \PHPUnit_Framework_TestCase
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

        $class = new \ReflectionClass(AbstractHandler::class);

        $reflectionProperty = $class->getProperty('knownContentTypes');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($abstractHandler, $newTypes);

        $method = $class->getMethod('determineContentType');
        $method->setAccessible(true);

        $return = $method->invoke($abstractHandler, $req);

        $this->assertEquals('text/html', $return);
    }
}
