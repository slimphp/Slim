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
     * @param string $psr17factory
     * @param string $expectedServerRequestClass
     */
    public function testCreateAppWithAllImplementations(string $psr17factory, string $expectedServerRequestClass)
    {
        $psr17FactoriesProperty = new ReflectionProperty(ServerRequestCreatorFactory::class, 'psr17Factories');
        $psr17FactoriesProperty->setAccessible(true);
        $psr17FactoriesProperty->setValue([$psr17factory]);

        $serverRequestCreator = ServerRequestCreatorFactory::create();
        $serverRequest = $serverRequestCreator->createServerRequestFromGlobals();

        $this->assertInstanceOf($expectedServerRequestClass, $serverRequest);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDetermineServerRequestCreatorThrowsRuntimeException()
    {
        $psr17FactoriesProperty = new ReflectionProperty(ServerRequestCreatorFactory::class, 'psr17Factories');
        $psr17FactoriesProperty->setAccessible(true);
        $psr17FactoriesProperty->setValue([]);

        ServerRequestCreatorFactory::create();
    }
}
