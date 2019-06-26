<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests;

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Slim\CallableResolver;
use Slim\Tests\Mocks\CallableTest;
use Slim\Tests\Mocks\InvokableTest;
use Slim\Tests\Mocks\RequestHandlerTest;

class CallableResolverTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $containerProphecy;

    public function setUp()
    {
        CallableTest::$CalledCount = 0;
        InvokableTest::$CalledCount = 0;
        RequestHandlerTest::$CalledCount = 0;

        $this->containerProphecy = $this->prophesize(ContainerInterface::class);
        $this->containerProphecy->has(Argument::type('string'))->willReturn(false);
    }

    public function testClosure()
    {
        $test = function () {
            return true;
        };
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve($test);

        $this->assertEquals(true, $callable());
    }

    public function testFunctionName()
    {
        function testCallable()
        {
            return true;
        }

        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(__NAMESPACE__ . '\testCallable');

        $this->assertEquals(true, $callable());
    }

    public function testObjMethodArray()
    {
        $obj = new CallableTest();
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve([$obj, 'toCall']);
        $callable();

        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testSlimCallable()
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve('Slim\Tests\Mocks\CallableTest:toCall');
        $callable();

        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testSlimCallableContainer()
    {
        $resolver = new CallableResolver($this->containerProphecy->reveal());
        $resolver->resolve('Slim\Tests\Mocks\CallableTest:toCall');

        $this->assertEquals($this->containerProphecy->reveal(), CallableTest::$CalledContainer);
    }

    public function testContainer()
    {
        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTest());
        $resolver = new CallableResolver($this->containerProphecy->reveal());
        $callable = $resolver->resolve('callable_service:toCall');
        $callable();

        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testResolutionToAnInvokableClassInContainer()
    {
        $this->containerProphecy->has('an_invokable')->willReturn(true);
        $this->containerProphecy->get('an_invokable')->willReturn(new InvokableTest());
        $resolver = new CallableResolver($this->containerProphecy->reveal());
        $callable = $resolver->resolve('an_invokable');
        $callable();

        $this->assertEquals(1, InvokableTest::$CalledCount);
    }

    public function testResolutionToAnInvokableClass()
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve('Slim\Tests\Mocks\InvokableTest');
        $callable();

        $this->assertEquals(1, InvokableTest::$CalledCount);
    }

    public function testResolutionToAPsrRequestHandlerClass()
    {
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(RequestHandlerTest::class);
        $callable($request);

        $this->assertEquals("1", RequestHandlerTest::$CalledCount);
    }

    public function testObjPsrRequestHandlerClass()
    {
        $obj = new RequestHandlerTest();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve($obj);
        $callable($request);

        $this->assertEquals("1", RequestHandlerTest::$CalledCount);
    }

    public function testObjPsrRequestHandlerClassInContainer()
    {
        $this->containerProphecy->has('a_requesthandler')->willReturn(true);
        $this->containerProphecy->get('a_requesthandler')->willReturn(new RequestHandlerTest());
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver($this->containerProphecy->reveal());
        $callable = $resolver->resolve('a_requesthandler');
        $callable($request);

        $this->assertEquals("1", RequestHandlerTest::$CalledCount);
    }

    public function testResolutionToAPsrRequestHandlerClassWithCustomMethod()
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(RequestHandlerTest::class . ':custom');
        $this->assertInternalType('array', $callable);
        $this->assertInstanceOf(RequestHandlerTest::class, $callable[0]);
        $this->assertEquals('custom', $callable[1]);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testMethodNotFoundThrowException()
    {
        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTest);
        $resolver = new CallableResolver($this->containerProphecy->reveal());
        $resolver->resolve('callable_service:notFound');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFunctionNotFoundThrowException()
    {
        $resolver = new CallableResolver($this->containerProphecy->reveal());
        $resolver->resolve('notFound');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Callable Unknown does not exist
     */
    public function testClassNotFoundThrowException()
    {
        $resolver = new CallableResolver($this->containerProphecy->reveal());
        $resolver->resolve('Unknown:notFound');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage is not resolvable
     */
    public function testCallableClassNotFoundThrowException()
    {
        $resolver = new CallableResolver($this->containerProphecy->reveal());
        $resolver->resolve(['Unknown', 'notFound']);
    }
}
