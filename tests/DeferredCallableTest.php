<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use PHPUnit\Framework\TestCase;
use Pimple\Container as Pimple;
use Pimple\Psr11\Container;
use Slim\CallableResolver;
use Slim\DeferredCallable;
use Slim\Tests\Mocks\CallableTest;

class DeferredCallableTest extends TestCase
{
    public function testItResolvesCallable()
    {
        $pimple = new Pimple();
        $pimple['CallableTest'] = new CallableTest;
        $resolver = new CallableResolver(new Container($pimple));

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

        $pimple = new Pimple();
        $container = new Container($pimple);
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
        $pimple = new Pimple();
        $resolver = new CallableResolver(new Container($pimple));
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
