<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use Psr\Container\ContainerInterface;
use RuntimeException;
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

    public function testFunctionNameIfNoResolver()
    {
        $deferredTrim = new DeferredCallable('trim');
        $this->assertSame('foo', $deferredTrim(' foo '));
    }

    public function testClosureIfNoResolver()
    {
        $closure = function ($a, $b) {
            return $a + $b;
        };

        $deferredClosure = new DeferredCallable($closure);
        $this->assertSame(42, $deferredClosure(31, 11));
    }

    public static function getFoo(): string
    {
        return 'foo';
    }

    public function testClassNameMethodNameNotation()
    {
        // Test `ClassName::methodName` notation for a static method.
        $deferredGetFoo = new DeferredCallable(get_class($this) . '::getFoo');
        $this->assertSame('foo', $deferredGetFoo());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Slim callable notation CallableTest:toCall is not allowed without callable resolver.
     */
    public function testSlimCallableNotationThrowsExceptionIfNoResolver()
    {
        new DeferredCallable('CallableTest:toCall');
    }
}
