<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Slim\CallableResolver;
use Slim\DeferredCallable;
use Slim\Tests\Mocks\CallableTest;
use Slim\Tests\Mocks\MockContainer;

class DeferredCallableTest extends TestCase
{
    public function testItResolvesCallable()
    {
        $container = new MockContainer();
        $container['CallableTest'] = new CallableTest;
        $resolver = new CallableResolver($container);

        $deferred = new DeferredCallable('CallableTest:toCall', $resolver);
        $deferred();

        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testItBindsClosuresToContainer()
    {
        $container = new MockContainer();
        $resolver = new CallableResolver($container);

        $assertCalled = $this->getMockBuilder('StdClass')->setMethods(['foo'])->getMock();
        $assertCalled
            ->expects($this->once())
            ->method('foo');

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
        $container = new MockContainer();
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
