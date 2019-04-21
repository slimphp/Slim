<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Factory;

use Http\Factory\Guzzle\ResponseFactory as GuzzleResponseFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use ReflectionProperty;
use Slim\Factory\AppFactory;
use Slim\Factory\Psr17\GuzzlePsr17Factory;
use Slim\Factory\Psr17\NyholmPsr17Factory;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Factory\Psr17\ZendDiactorosPsr17Factory;
use Slim\Psr7\Factory\ResponseFactory as SlimResponseFactory;
use Slim\Routing\RouteCollector;
use Slim\Tests\TestCase;
use Zend\Diactoros\ResponseFactory as ZendDiactorosResponseFactory;

class AppFactoryTest extends TestCase
{
    public function provideImplementations()
    {
        return [
            [SlimPsr17Factory::class, SlimResponseFactory::class],
            [NyholmPsr17Factory::class, Psr17Factory::class],
            [GuzzlePsr17Factory::class, GuzzleResponseFactory::class],
            [ZendDiactorosPsr17Factory::class, ZendDiactorosResponseFactory::class],
        ];
    }

    /**
     * @dataProvider provideImplementations
     * @param string $implementation
     * @param string $expectedResponseFactoryClass
     */
    public function testCreateAppWithAllImplementations(string $implementation, string $expectedResponseFactoryClass)
    {
        $implementationsProperty = new ReflectionProperty(AppFactory::class, 'implementations');
        $implementationsProperty->setAccessible(true);
        $implementationsProperty->setValue([$implementation]);

        $app = AppFactory::create();

        $routeCollector = $app->getRouteCollector();

        $responseFactoryProperty = new ReflectionProperty(RouteCollector::class, 'responseFactory');
        $responseFactoryProperty->setAccessible(true);

        $responseFactory = $responseFactoryProperty->getValue($routeCollector);

        $this->assertInstanceOf($expectedResponseFactoryClass, $responseFactory);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDetermineResponseFactoryThrowsRuntimeException()
    {
        $implementationsProperty = new ReflectionProperty(AppFactory::class, 'implementations');
        $implementationsProperty->setAccessible(true);
        $implementationsProperty->setValue([]);

        AppFactory::create();
    }
}
