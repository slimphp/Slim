<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Factory\Psr17;

use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;
use RuntimeException;
use Slim\Factory\Psr17\SlimHttpServerRequestCreator;
use Slim\Http\ServerRequest;
use Slim\Interfaces\ServerRequestCreatorInterface;
use Slim\Tests\TestCase;

class SlimHttpServerRequestCreatorTest extends TestCase
{
    /**
     * We need to reset the static property of SlimHttpServerRequestCreator back to its original value
     * Otherwise other tests will fail
     */
    public function tearDown()
    {
        $serverRequestCreatorProphecy = $this->prophesize(ServerRequestCreatorInterface::class);

        $slimHttpServerRequestCreator = new SlimHttpServerRequestCreator($serverRequestCreatorProphecy->reveal());

        $serverRequestDecoratorClassProperty = new ReflectionProperty(
            SlimHttpServerRequestCreator::class,
            'serverRequestDecoratorClass'
        );
        $serverRequestDecoratorClassProperty->setAccessible(true);
        $serverRequestDecoratorClassProperty->setValue($slimHttpServerRequestCreator, ServerRequest::class);
    }

    public function testCreateServerRequestFromGlobals()
    {
        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);

        $serverRequestCreatorProphecy = $this->prophesize(ServerRequestCreatorInterface::class);

        $serverRequestCreatorProphecy
            ->createServerRequestFromGlobals()
            ->willReturn($serverRequestProphecy->reveal())
            ->shouldBeCalledOnce();

        $slimHttpServerRequestCreator = new SlimHttpServerRequestCreator($serverRequestCreatorProphecy->reveal());

        $this->assertInstanceOf(ServerRequest::class, $slimHttpServerRequestCreator->createServerRequestFromGlobals());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The Slim-Http ServerRequest decorator is not available.
     */
    public function testCreateServerRequestFromGlobalsThrowsRuntimeException()
    {
        $serverRequestCreatorProphecy = $this->prophesize(ServerRequestCreatorInterface::class);

        $slimHttpServerRequestCreator = new SlimHttpServerRequestCreator($serverRequestCreatorProphecy->reveal());

        $serverRequestDecoratorClassProperty = new ReflectionProperty(
            SlimHttpServerRequestCreator::class,
            'serverRequestDecoratorClass'
        );
        $serverRequestDecoratorClassProperty->setAccessible(true);
        $serverRequestDecoratorClassProperty->setValue($slimHttpServerRequestCreator, '');

        $slimHttpServerRequestCreator->createServerRequestFromGlobals();
    }
}
