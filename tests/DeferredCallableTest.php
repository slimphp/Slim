<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests;

use Pimple\Container as Pimple;
use Pimple\Psr11\Container;
use Slim\CallableResolver;
use Slim\DeferredCallable;
use Slim\Tests\Mocks\CallableTest;
use Slim\Tests\Mocks\Psr15MiddlewareTest;

class DeferredCallableTest extends TestCase
{

    public function setUp()
    {
        CallableTest::$CalledCount = 0;
        Psr15MiddlewareTest::$CalledCount = 0;
    }

    public function testItResolvesCallable()
    {
        $pimple = new Pimple();
        $pimple['CallableTest'] = new CallableTest;
        $resolver = new CallableResolver(new Container($pimple));

        $deferred = new DeferredCallable('CallableTest:toCall', $resolver);
        $deferred();

        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testItResolvesPsr15Middleware()
    {
        $pimple = new Pimple();
        $pimple['CallableTest'] = new Psr15MiddlewareTest;
        $resolver = new CallableResolver(new Container($pimple));

        $deferred = new DeferredCallable(Psr15MiddlewareTest::class, $resolver, true);
        $deferred(
            $this->getMockBuilder('Psr\Http\Message\ServerRequestInterface')->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder('Psr\Http\Message\ResponseInterface')->disableOriginalConstructor()->getMock(),
            function ($req, $res) {
                return $res;
            }
        );

        $this->assertEquals(1, Psr15MiddlewareTest::$CalledCount);
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
