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
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\AdvancedCallableResolver;
use Slim\Tests\Mocks\CallableTest;
use Slim\Tests\Mocks\InvokableTest;
use Slim\Tests\Mocks\MiddlewareTest;
use Slim\Tests\Mocks\RequestHandlerTest;

class AdvancedCallableResolverTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $containerProphecy;

    public static function setUpBeforeClass()
    {
        function testAdvancedCallable()
        {
            return true;
        }
    }

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
        $resolver = new AdvancedCallableResolver(); // No container injected
        $callable = $resolver->resolve($test);
        $callableRoute = $resolver->resolveRoute($test);
        $callableMiddleware = $resolver->resolveMiddleware($test);

        $this->assertEquals(true, $callable());
        $this->assertEquals(true, $callableRoute());
        $this->assertEquals(true, $callableMiddleware());
    }

    public function testClosureContainer()
    {
        $this->containerProphecy->has('ultimateAnswer')->willReturn(true);
        $this->containerProphecy->get('ultimateAnswer')->willReturn(42);

        $that = $this;
        $test = function () use ($that) {
            $that->assertInstanceOf(ContainerInterface::class, $this);

            /** @var ContainerInterface $this */
            return $this->get('ultimateAnswer');
        };

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $callable = $resolver->resolve($test);
        $callableRoute = $resolver->resolveRoute($test);
        $callableMiddleware = $resolver->resolveMiddleware($test);

        $this->assertEquals(42, $callable());
        $this->assertEquals(42, $callableRoute());
        $this->assertEquals(42, $callableMiddleware());
    }

    public function testFunctionName()
    {
        $resolver = new AdvancedCallableResolver(); // No container injected
        $callable = $resolver->resolve(__NAMESPACE__.'\testAdvancedCallable');
        $callableRoute = $resolver->resolveRoute(__NAMESPACE__.'\testAdvancedCallable');
        $callableMiddleware = $resolver->resolveMiddleware(__NAMESPACE__.'\testAdvancedCallable');

        $this->assertEquals(true, $callable());
        $this->assertEquals(true, $callableRoute());
        $this->assertEquals(true, $callableMiddleware());
    }

    public function testObjMethodArray()
    {
        $obj = new CallableTest();
        $resolver = new AdvancedCallableResolver(); // No container injected
        $callable = $resolver->resolve([$obj, 'toCall']);
        $callableRoute = $resolver->resolveRoute([$obj, 'toCall']);
        $callableMiddleware = $resolver->resolveMiddleware([$obj, 'toCall']);

        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);

        $callableRoute();
        $this->assertEquals(2, CallableTest::$CalledCount);

        $callableMiddleware();
        $this->assertEquals(3, CallableTest::$CalledCount);
    }

    public function testSlimCallable()
    {
        $resolver = new AdvancedCallableResolver(); // No container injected
        $callable = $resolver->resolve('Slim\Tests\Mocks\CallableTest:toCall');
        $callableRoute = $resolver->resolveRoute('Slim\Tests\Mocks\CallableTest:toCall');
        $callableMiddleware = $resolver->resolveMiddleware('Slim\Tests\Mocks\CallableTest:toCall');

        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);

        $callableRoute();
        $this->assertEquals(2, CallableTest::$CalledCount);

        $callableMiddleware();
        $this->assertEquals(3, CallableTest::$CalledCount);
    }

    public function testSlimCallableContainer()
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolve('Slim\Tests\Mocks\CallableTest:toCall');
        $this->assertEquals($container, CallableTest::$CalledContainer);

        CallableTest::$CalledContainer = null;
        $resolver->resolveRoute('Slim\Tests\Mocks\CallableTest:toCall');
        $this->assertEquals($container, CallableTest::$CalledContainer);

        CallableTest::$CalledContainer = null;
        $resolver->resolveMiddleware('Slim\Tests\Mocks\CallableTest:toCall');
        $this->assertEquals($container, CallableTest::$CalledContainer);
    }

    public function testContainer()
    {
        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();

        $resolver = new AdvancedCallableResolver($container);
        $callable = $resolver->resolve('callable_service:toCall');
        $callableRoute = $resolver->resolveRoute('callable_service:toCall');
        $callableMiddleware = $resolver->resolveMiddleware('callable_service:toCall');

        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);

        $callableRoute();
        $this->assertEquals(2, CallableTest::$CalledCount);

        $callableMiddleware();
        $this->assertEquals(3, CallableTest::$CalledCount);
    }

    public function testResolutionToAnInvokableClassInContainer()
    {
        $this->containerProphecy->has('an_invokable')->willReturn(true);
        $this->containerProphecy->get('an_invokable')->willReturn(new InvokableTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();

        $resolver = new AdvancedCallableResolver($container);
        $callable = $resolver->resolve('an_invokable');
        $callableRoute = $resolver->resolveRoute('an_invokable');
        $callableMiddleware = $resolver->resolveMiddleware('an_invokable');

        $callable();
        $this->assertEquals(1, InvokableTest::$CalledCount);

        $callableRoute();
        $this->assertEquals(2, InvokableTest::$CalledCount);

        $callableMiddleware();
        $this->assertEquals(3, InvokableTest::$CalledCount);
    }

    public function testResolutionToAnInvokableClass()
    {
        $resolver = new AdvancedCallableResolver(); // No container injected
        $callable = $resolver->resolve('Slim\Tests\Mocks\InvokableTest');
        $callableRoute = $resolver->resolveRoute('Slim\Tests\Mocks\InvokableTest');
        $callableMiddleware = $resolver->resolveMiddleware('Slim\Tests\Mocks\InvokableTest');

        $callable();
        $this->assertEquals(1, InvokableTest::$CalledCount);

        $callableRoute();
        $this->assertEquals(2, InvokableTest::$CalledCount);

        $callableMiddleware();
        $this->assertEquals(3, InvokableTest::$CalledCount);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Slim\Tests\Mocks\RequestHandlerTest is not resolvable
     */
    public function testResolutionToAPsrRequestHandlerClass()
    {
        $resolver = new AdvancedCallableResolver(); // No container injected
        $resolver->resolve(RequestHandlerTest::class);
    }

    public function testRouteResolutionToAPsrRequestHandlerClass()
    {
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new AdvancedCallableResolver(); // No container injected
        $callableRoute = $resolver->resolveRoute(RequestHandlerTest::class);
        $callableRoute($request);
        $this->assertEquals('1', RequestHandlerTest::$CalledCount);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Slim\Tests\Mocks\RequestHandlerTest is not resolvable
     */
    public function testMiddlewareResolutionToAPsrRequestHandlerClass()
    {
        $resolver = new AdvancedCallableResolver(); // No container injected
        $resolver->resolveMiddleware(RequestHandlerTest::class);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage {} is not resolvable
     */
    public function testObjPsrRequestHandlerClass()
    {
        $obj = new RequestHandlerTest();
        $resolver = new AdvancedCallableResolver(); // No container injected
        $resolver->resolve($obj);
    }

    public function testRouteObjPsrRequestHandlerClass()
    {
        $obj = new RequestHandlerTest();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new AdvancedCallableResolver(); // No container injected
        $callableRoute = $resolver->resolveRoute($obj);
        $callableRoute($request);
        $this->assertEquals('1', RequestHandlerTest::$CalledCount);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage {} is not resolvable
     */
    public function testMiddlewareObjPsrRequestHandlerClass()
    {
        $obj = new RequestHandlerTest();
        $resolver = new AdvancedCallableResolver(); // No container injected
        $resolver->resolveMiddleware($obj);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage a_requesthandler is not resolvable
     */
    public function testObjPsrRequestHandlerClassInContainer()
    {
        $this->containerProphecy->has('a_requesthandler')->willReturn(true);
        $this->containerProphecy->get('a_requesthandler')->willReturn(new RequestHandlerTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolve('a_requesthandler');
    }

    public function testRouteObjPsrRequestHandlerClassInContainer()
    {
        $this->containerProphecy->has('a_requesthandler')->willReturn(true);
        $this->containerProphecy->get('a_requesthandler')->willReturn(new RequestHandlerTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new AdvancedCallableResolver($container);
        $callable = $resolver->resolveRoute('a_requesthandler');
        $callable($request);

        $this->assertEquals('1', RequestHandlerTest::$CalledCount);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage a_requesthandler is not resolvable
     */
    public function testMiddlewareObjPsrRequestHandlerClassInContainer()
    {
        $this->containerProphecy->has('a_requesthandler')->willReturn(true);
        $this->containerProphecy->get('a_requesthandler')->willReturn(new RequestHandlerTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolveMiddleware('a_requesthandler');
    }

    public function testResolutionToAPsrRequestHandlerClassWithCustomMethod()
    {
        $resolver = new AdvancedCallableResolver(); // No container injected
        $callable = $resolver->resolve(RequestHandlerTest::class.':custom');
        $callableRoute = $resolver->resolveRoute(RequestHandlerTest::class.':custom');
        $callableMiddleware = $resolver->resolveMiddleware(RequestHandlerTest::class.':custom');

        $this->assertIsArray($callable);
        $this->assertInstanceOf(RequestHandlerTest::class, $callable[0]);
        $this->assertEquals('custom', $callable[1]);

        $this->assertIsArray($callableRoute);
        $this->assertInstanceOf(RequestHandlerTest::class, $callableRoute[0]);
        $this->assertEquals('custom', $callableRoute[1]);

        $this->assertIsArray($callableMiddleware);
        $this->assertInstanceOf(RequestHandlerTest::class, $callableMiddleware[0]);
        $this->assertEquals('custom', $callableMiddleware[1]);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage {} is not resolvable
     */
    public function testObjMiddlewareClass()
    {
        $obj = new MiddlewareTest();
        $resolver = new AdvancedCallableResolver(); // No container injected
        $resolver->resolve($obj);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage {} is not resolvable
     */
    public function testRouteObjMiddlewareClass()
    {
        $obj = new MiddlewareTest();
        $resolver = new AdvancedCallableResolver(); // No container injected
        $resolver->resolveRoute($obj);
    }

    public function testMiddlewareObjMiddlewareClass()
    {
        $obj = new MiddlewareTest();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new AdvancedCallableResolver(); // No container injected
        $callableRouteMiddleware = $resolver->resolveMiddleware($obj);
        $callableRouteMiddleware($request, $this->createMock(RequestHandlerInterface::class));
        $this->assertEquals('1', MiddlewareTest::$CalledCount);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage callable_service:notFound is not resolvable
     */
    public function testMethodNotFoundThrowException()
    {
        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolve('callable_service:notFound');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage callable_service:notFound is not resolvable
     */
    public function testRouteMethodNotFoundThrowException()
    {
        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolveRoute('callable_service:notFound');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage callable_service:notFound is not resolvable
     */
    public function testMiddlewareMethodNotFoundThrowException()
    {
        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolveMiddleware('callable_service:notFound');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Callable notFound does not exist
     */
    public function testFunctionNotFoundThrowException()
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolve('notFound');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Callable notFound does not exist
     */
    public function testRouteFunctionNotFoundThrowException()
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolveRoute('notFound');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Callable notFound does not exist
     */
    public function testMiddlewareFunctionNotFoundThrowException()
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolveMiddleware('notFound');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Callable Unknown does not exist
     */
    public function testClassNotFoundThrowException()
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolve('Unknown:notFound');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Callable Unknown does not exist
     */
    public function testRouteClassNotFoundThrowException()
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolveRoute('Unknown:notFound');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Callable Unknown does not exist
     */
    public function testMiddlewareClassNotFoundThrowException()
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolveMiddleware('Unknown:notFound');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage is not resolvable
     */
    public function testCallableClassNotFoundThrowException()
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolve(['Unknown', 'notFound']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage is not resolvable
     */
    public function testRouteCallableClassNotFoundThrowException()
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolveRoute(['Unknown', 'notFound']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage is not resolvable
     */
    public function testMiddlewareCallableClassNotFoundThrowException()
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new AdvancedCallableResolver($container);
        $resolver->resolveMiddleware(['Unknown', 'notFound']);
    }
}
