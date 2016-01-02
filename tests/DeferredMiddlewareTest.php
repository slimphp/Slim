<?php


namespace Slim\Tests;


use Slim\Container;
use Slim\DeferredMiddleware;
use Slim\Tests\Mocks\CallableTest;

class DeferredMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public function testItResolvesCallable()
    {
        $container = new Container();
        $container['CallableTest'] = new CallableTest;

        $deferred = new DeferredMiddleware('CallableTest:toCall', $container);
        $deferred();

        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testItBindsClosuresToContainer()
    {
        $assertCalled = $this->getMock('fooClass', ['foo']);
        $assertCalled
            ->expects($this->once())
            ->method('foo');

        $container = new Container();

        $test = $this;

        $closure = function() use($container, $test, $assertCalled){
            $assertCalled->foo();
            $test->assertSame($container, $this);
        };

        $deferred = new DeferredMiddleware($closure, $container);
        $deferred();
    }

    public function testItReturnsInvokedCallableResponse()
    {
        $container = new Container;
        $test = $this;
        $foo = 'foo';
        $bar = 'bar';

        $closure = function($param) use($test, $foo, $bar){
            $test->assertEquals($foo, $param);
            return $bar;
        };

        $deferred = new DeferredMiddleware($closure, $container);

        $response = $deferred($foo);
        $this->assertEquals($bar, $response);
    }
}
