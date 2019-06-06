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
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Factory\Psr17\GuzzlePsr17Factory;
use Slim\Factory\Psr17\NyholmPsr17Factory;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Factory\Psr17\SlimHttpServerRequestCreator;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Factory\Psr17\ZendDiactorosPsr17Factory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Http\ServerRequest;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
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
        Psr17FactoryProvider::setFactories([$psr17factory]);
        ServerRequestCreatorFactory::setSlimHttpDecoratorsAutomaticDetection(false);

        $serverRequestCreator = ServerRequestCreatorFactory::create();
        $serverRequest = $serverRequestCreator->createServerRequestFromGlobals();

        $this->assertInstanceOf($expectedServerRequestClass, $serverRequest);
    }

    public function testDetermineServerRequestCreatorReturnsDecoratedServerRequestCreator()
    {
        Psr17FactoryProvider::setFactories([SlimPsr17Factory::class]);
        ServerRequestCreatorFactory::setSlimHttpDecoratorsAutomaticDetection(true);

        $serverRequestCreator = ServerRequestCreatorFactory::create();

        $this->assertInstanceOf(SlimHttpServerRequestCreator::class, $serverRequestCreator);
        $this->assertInstanceOf(ServerRequest::class, $serverRequestCreator->createServerRequestFromGlobals());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testDetermineServerRequestCreatorThrowsRuntimeException()
    {
        Psr17FactoryProvider::setFactories([]);
        ServerRequestCreatorFactory::create();
    }

    public function testSetPsr17FactoryProvider()
    {
        $psr17FactoryProvider = new Psr17FactoryProvider();
        $psr17FactoryProvider::setFactories([SlimPsr17Factory::class]);

        ServerRequestCreatorFactory::setPsr17FactoryProvider($psr17FactoryProvider);
        ServerRequestCreatorFactory::setSlimHttpDecoratorsAutomaticDetection(false);

        $serverRequestCreator = ServerRequestCreatorFactory::create();

        $this->assertInstanceOf(SlimServerRequest::class, $serverRequestCreator->createServerRequestFromGlobals());
    }

    public function testSetServerRequestCreator()
    {
        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);

        $serverRequestCreatorProphecy = $this->prophesize(ServerRequestCreatorInterface::class);
        $serverRequestCreatorProphecy
            ->createServerRequestFromGlobals()
            ->willReturn($serverRequestProphecy->reveal())
            ->shouldBeCalledOnce();

        ServerRequestCreatorFactory::setServerRequestCreator($serverRequestCreatorProphecy->reveal());

        $serverRequestCreator = ServerRequestCreatorFactory::create();

        $this->assertSame($serverRequestProphecy->reveal(), $serverRequestCreator->createServerRequestFromGlobals());
    }
}
