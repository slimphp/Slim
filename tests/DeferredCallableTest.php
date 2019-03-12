<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use Psr\Container\ContainerInterface;
use Slim\CallableResolver;
use Slim\DeferredCallable;
use Slim\Tests\Mocks\CallableTest;

class DeferredCallableTest extends TestCase
{
    public function testItResolvesCallable()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $containerProphecy->has('CallableTest')->willReturn(true);
        $containerProphecy->get('CallableTest')->willReturn(new CallableTest());
        $resolver = new CallableResolver($containerProphecy->reveal());

        $deferred = new DeferredCallable('CallableTest:toCall', $resolver);
        $deferred();

        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testItBindsClosuresToContainer()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $resolver = new CallableResolver($containerProphecy->reveal());

        $assertCalled = $this->getMockBuilder('StdClass')->setMethods(['foo'])->getMock();
        $assertCalled
            ->expects($this->once())
            ->method('foo');

        $test = $this;
        $closure = function () use ($containerProphecy, $test, $assertCalled) {
            $assertCalled->foo();
            $test->assertSame($containerProphecy->reveal(), $this);
        };

        $deferred = new DeferredCallable($closure, $resolver);
        $deferred();
    }

    public function testItReturnsInvokedCallableResponse()
    {
        $containerProphecy = $this->prophesize(ContainerInterface::class);
        $resolver = new CallableResolver($containerProphecy->reveal());

        $test = $this;
        $foo = 'foo';
        $bar = 'bar';

        $closure = function ($param) use ($test, $foo, $bar) {
            $test->assertEquals($foo, $param);
            return $bar;
        };

        $deferred = new DeferredCallable($closure, $resolver);

        $response = $deferred($foo);
        $this->assertEquals($bar, $response);
    }
}
