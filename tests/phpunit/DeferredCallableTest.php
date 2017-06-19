<?php


namespace Slim\Tests;

use PHPUnit\Framework\TestCase;
use Slim\CallableResolver;
use Slim\Container;
use Slim\DeferredCallable;
use Slim\Tests\Mocks\CallableTest;

class DeferredCallableTest extends TestCase
{
    public function testItResolvesCallable()
    {
        $container = new Container();
        $container['CallableTest'] = new CallableTest;
        $resolver = new CallableResolver($container);

        $deferred = new DeferredCallable('CallableTest:toCall', $resolver);
        $deferred();

        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testItBindsClosuresToContainer()
    {
        $assertCalled = $this->getMockBuilder('StdClass')->setMethods(['foo'])->getMock();
        $assertCalled
            ->expects($this->once())
            ->method('foo');

        $container = new Container();
        $resolver = new CallableResolver($container);
        $test = $this;

        $closure = function () use ($container, $test, $assertCalled) {
            $assertCalled->foo();
            $test->assertSame($container, $this);
        };

        $deferred = new DeferredCallable($closure, $resolver);
        $deferred();
    }

    public function testItReturnsInvokedCallableResponse()
    {
        $container = new Container;
        $resolver = new CallableResolver($container);
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
