<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Factory\Psr17;

use ReflectionProperty;
use Slim\Factory\Psr17\SlimHttpPsr17Factory;
use Slim\Tests\TestCase;

class SlimHttpPsr17FactoryTest extends TestCase
{
    public function testSetResponseFactory()
    {
        SlimHttpPsr17Factory::setResponseFactory('Slim\Http\Factory\AnotherDecoratedResponseFactory');
        $responseFactoryClassProperty = new ReflectionProperty(SlimHttpPsr17Factory::class, 'responseFactoryClass');
        $responseFactoryClassProperty->setAccessible(true);

        $this->assertEquals(
            'Slim\Http\Factory\AnotherDecoratedResponseFactory',
            $responseFactoryClassProperty->getValue()
        );
    }
}
