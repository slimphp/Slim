<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Container;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use ReflectionClass;
use RuntimeException;
use Slim\Container\Definition\HttpDefinitions;

class HttpDefinitionsTest extends TestCase
{
    public function testInvokeThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);

        // Create a mock for the class_exists function
        $classExistsMock = fn () => false;

        $httpDefinitions = new HttpDefinitions();

        // Use reflection to inject the mock callable into the $classExists property
        $reflection = new ReflectionClass($httpDefinitions);
        $classExistsProperty = $reflection->getProperty('classExists');
        $classExistsProperty->setAccessible(true);
        $classExistsProperty->setValue($httpDefinitions, $classExistsMock);

        $httpDefinitions->getDefinitions();
    }

    public function testServerRequestFactoryInterface()
    {
        $definitions = (new HttpDefinitions())->getDefinitions();

        $container = new Container($definitions);
        $serverRequestFactory = $container->get(ServerRequestFactoryInterface::class);

        $this->assertInstanceOf(ServerRequestFactoryInterface::class, $serverRequestFactory);
    }
}
