<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Factory;

use GuzzleHttp\Psr7\ServerRequest as GuzzleServerRequest;
use Nyholm\Psr7\ServerRequest as NyholmServerRequest;
use ReflectionProperty;
use Slim\Factory\Psr17\GuzzlePsr17Factory;
use Slim\Factory\Psr17\NyholmPsr17Factory;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Factory\Psr17\ZendDiactorosPsr17Factory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Psr7\Request as SlimServerRequest;
use Slim\Tests\TestCase;
use Zend\Diactoros\ServerRequest as ZendServerRequest;

class ServerRequestCreatorFactoryTest extends TestCase
{
    public function provideImplementations()
    {
        return [
            [SlimPsr17Factory::class, SlimServerRequest::class],
            [NyholmPsr17Factory::class, NyholmServerRequest::class],
            [GuzzlePsr17Factory::class, GuzzleServerRequest::class],
            [ZendDiactorosPsr17Factory::class, ZendServerRequest::class],
        ];
    }

    /**
     * @dataProvider provideImplementations
     * @param string $implementation
     * @param string $expectedServerRequestClass
     */
    public function testCreateAppWithAllImplementations(string $implementation, string $expectedServerRequestClass)
    {
        $implementationsProperty = new ReflectionProperty(ServerRequestCreatorFactory::class, 'implementations');
        $implementationsProperty->setAccessible(true);
        $implementationsProperty->setValue([$implementation]);

        $serverRequestCreator = ServerRequestCreatorFactory::create();
        $serverRequest = $serverRequestCreator->createServerRequestFromGlobals();

        $this->assertInstanceOf($expectedServerRequestClass, $serverRequest);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDetermineServerRequestCreatorThrowsRuntimeException()
    {
        $implementationsProperty = new ReflectionProperty(ServerRequestCreatorFactory::class, 'implementations');
        $implementationsProperty->setAccessible(true);
        $implementationsProperty->setValue([]);

        ServerRequestCreatorFactory::create();
    }
}
