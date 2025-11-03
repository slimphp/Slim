<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Factory;

use GuzzleHttp\Psr7\ServerRequest as GuzzleServerRequest;
use Laminas\Diactoros\ServerRequest as LaminasDiactorosServerRequest;
use Nyholm\Psr7\ServerRequest as NyholmServerRequest;
use HttpSoft\Message\ServerRequest as HttpSoftServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Factory\Psr17\GuzzlePsr17Factory;
use Slim\Factory\Psr17\HttpSoftPsr17Factory;
use Slim\Factory\Psr17\LaminasDiactorosPsr17Factory;
use Slim\Factory\Psr17\NyholmPsr17Factory;
use Slim\Factory\Psr17\Psr17FactoryProvider;
use Slim\Factory\Psr17\SlimHttpServerRequestCreator;
use Slim\Factory\Psr17\SlimPsr17Factory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Http\ServerRequest;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Psr7\Request as SlimServerRequest;
use Slim\Tests\TestCase;

class ServerRequestCreatorFactoryTest extends TestCase
{
    public static function provideImplementations()
    {
        return [
            [SlimPsr17Factory::class, SlimServerRequest::class],
            [HttpSoftPsr17Factory::class, HttpSoftServerRequest::class],
            [NyholmPsr17Factory::class, NyholmServerRequest::class],
            [GuzzlePsr17Factory::class, GuzzleServerRequest::class],
            [LaminasDiactorosPsr17Factory::class, LaminasDiactorosServerRequest::class],
        ];
    }

    /**
     * @dataProvider provideImplementations
     * @param string $psr17factory
     * @param string $expectedServerRequestClass
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provideImplementations')]
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
     * @runInSeparateProcess - Psr17FactoryProvider::setFactories breaks other tests
     */
     #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testDetermineServerRequestCreatorThrowsRuntimeException()
    {
        $this->expectException(RuntimeException::class);

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

    /**
     * @runInSeparateProcess - ServerRequestCreatorFactory::setServerRequestCreator breaks other tests
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testSetServerRequestCreatorWithoutDecorators()
    {
        ServerRequestCreatorFactory::setSlimHttpDecoratorsAutomaticDetection(false);
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

    /**
     * @runInSeparateProcess - ServerRequestCreatorFactory::setServerRequestCreator breaks other tests
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testSetServerRequestCreatorWithDecorators()
    {
        ServerRequestCreatorFactory::setSlimHttpDecoratorsAutomaticDetection(true);
        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);

        $serverRequestCreatorProphecy = $this->prophesize(ServerRequestCreatorInterface::class);
        $serverRequestCreatorProphecy
            ->createServerRequestFromGlobals()
            ->willReturn($serverRequestProphecy->reveal())
            ->shouldBeCalledOnce();

        ServerRequestCreatorFactory::setServerRequestCreator($serverRequestCreatorProphecy->reveal());

        $serverRequestCreator = ServerRequestCreatorFactory::create();

        $this->assertInstanceOf(ServerRequest::class, $serverRequestCreator->createServerRequestFromGlobals());
    }
}
