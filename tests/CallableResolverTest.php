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
use Slim\CallableResolver;
use Slim\Tests\Mocks\CallableTest;
use Slim\Tests\Mocks\InvokableTest;
use Slim\Tests\Mocks\MiddlewareTest;
use Slim\Tests\Mocks\RequestHandlerTest;

class CallableResolverTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $containerProphecy;

    public static function setUpBeforeClass(): void
    {
        function testAdvancedCallable()
        {
            return true;
        }
    }

    public function setUp(): void
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
        $resolver = new CallableResolver($container);
        $callable = $resolver->resolve($test);
        $callableRoute = $resolver->resolveRoute($test);
        $callableMiddleware = $resolver->resolveMiddleware($test);

        $this->assertEquals(42, $callable());
        $this->assertEquals(42, $callableRoute());
        $this->assertEquals(42, $callableMiddleware());
    }

    public function testFunctionName()
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(__NAMESPACE__ . '\testAdvancedCallable');
        $callableRoute = $resolver->resolveRoute(__NAMESPACE__ . '\testAdvancedCallable');
        $callableMiddleware = $resolver->resolveMiddleware(__NAMESPACE__ . '\testAdvancedCallable');

        $this->assertEquals(true, $callable());
        $this->assertEquals(true, $callableRoute());
        $this->assertEquals(true, $callableMiddleware());
    }

    public function testObjMethodArray()
    {
        $obj = new CallableTest();
        $resolver = new CallableResolver(); // No container injected
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
        $resolver = new CallableResolver(); // No container injected
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

    public function testSlimCallableAsArray()
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve([CallableTest::class, 'toCall']);
        $callableRoute = $resolver->resolveRoute([CallableTest::class, 'toCall']);
        $callableMiddleware = $resolver->resolveMiddleware([CallableTest::class, 'toCall']);

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
        $resolver = new CallableResolver($container);
        $resolver->resolve('Slim\Tests\Mocks\CallableTest:toCall');
        $this->assertEquals($container, CallableTest::$CalledContainer);

        CallableTest::$CalledContainer = null;
        $resolver->resolveRoute('Slim\Tests\Mocks\CallableTest:toCall');
        $this->assertEquals($container, CallableTest::$CalledContainer);

        CallableTest::$CalledContainer = null;
        $resolver->resolveMiddleware('Slim\Tests\Mocks\CallableTest:toCall');
        $this->assertEquals($container, CallableTest::$CalledContainer);
    }

    public function testSlimCallableAsArrayContainer()
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve([CallableTest::class, 'toCall']);
        $this->assertEquals($container, CallableTest::$CalledContainer);

        CallableTest::$CalledContainer = null;
        $resolver->resolveRoute([CallableTest::class, 'toCall']);
        $this->assertEquals($container, CallableTest::$CalledContainer);

        CallableTest::$CalledContainer = null;
        $resolver->resolveMiddleware([CallableTest::class ,'toCall']);
        $this->assertEquals($container, CallableTest::$CalledContainer);
    }

    public function testContainer()
    {
        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();

        $resolver = new CallableResolver($container);
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

        $resolver = new CallableResolver($container);
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
        $resolver = new CallableResolver(); // No container injected
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

    public function testResolutionToAPsrRequestHandlerClass()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Slim\\Tests\\Mocks\\RequestHandlerTest is not resolvable');

        $resolver = new CallableResolver(); // No container injected
        $resolver->resolve(RequestHandlerTest::class);
    }

    public function testRouteResolutionToAPsrRequestHandlerClass()
    {
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callableRoute = $resolver->resolveRoute(RequestHandlerTest::class);
        $callableRoute($request);
        $this->assertEquals('1', RequestHandlerTest::$CalledCount);
    }

    public function testMiddlewareResolutionToAPsrRequestHandlerClass()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Slim\\Tests\\Mocks\\RequestHandlerTest is not resolvable');

        $resolver = new CallableResolver(); // No container injected
        $resolver->resolveMiddleware(RequestHandlerTest::class);
    }

    public function testObjPsrRequestHandlerClass()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('{} is not resolvable');

        $obj = new RequestHandlerTest();
        $resolver = new CallableResolver(); // No container injected
        $resolver->resolve($obj);
    }

    public function testRouteObjPsrRequestHandlerClass()
    {
        $obj = new RequestHandlerTest();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callableRoute = $resolver->resolveRoute($obj);
        $callableRoute($request);
        $this->assertEquals('1', RequestHandlerTest::$CalledCount);
    }

    public function testMiddlewareObjPsrRequestHandlerClass()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('{} is not resolvable');

        $obj = new RequestHandlerTest();
        $resolver = new CallableResolver(); // No container injected
        $resolver->resolveMiddleware($obj);
    }

    public function testObjPsrRequestHandlerClassInContainer()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('a_requesthandler is not resolvable');

        $this->containerProphecy->has('a_requesthandler')->willReturn(true);
        $this->containerProphecy->get('a_requesthandler')->willReturn(new RequestHandlerTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('a_requesthandler');
    }

    public function testRouteObjPsrRequestHandlerClassInContainer()
    {
        $this->containerProphecy->has('a_requesthandler')->willReturn(true);
        $this->containerProphecy->get('a_requesthandler')->willReturn(new RequestHandlerTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver($container);
        $callable = $resolver->resolveRoute('a_requesthandler');
        $callable($request);

        $this->assertEquals('1', RequestHandlerTest::$CalledCount);
    }

    public function testMiddlewareObjPsrRequestHandlerClassInContainer()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('a_requesthandler is not resolvable');

        $this->containerProphecy->has('a_requesthandler')->willReturn(true);
        $this->containerProphecy->get('a_requesthandler')->willReturn(new RequestHandlerTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware('a_requesthandler');
    }

    public function testResolutionToAPsrRequestHandlerClassWithCustomMethod()
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(RequestHandlerTest::class . ':custom');
        $callableRoute = $resolver->resolveRoute(RequestHandlerTest::class . ':custom');
        $callableMiddleware = $resolver->resolveMiddleware(RequestHandlerTest::class . ':custom');

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

    public function testObjMiddlewareClass()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('{} is not resolvable');

        $obj = new MiddlewareTest();
        $resolver = new CallableResolver(); // No container injected
        $resolver->resolve($obj);
    }

    public function testRouteObjMiddlewareClass()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('{} is not resolvable');

        $obj = new MiddlewareTest();
        $resolver = new CallableResolver(); // No container injected
        $resolver->resolveRoute($obj);
    }

    public function testMiddlewareObjMiddlewareClass()
    {
        $obj = new MiddlewareTest();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callableRouteMiddleware = $resolver->resolveMiddleware($obj);
        $callableRouteMiddleware($request, $this->createMock(RequestHandlerInterface::class));
        $this->assertEquals('1', MiddlewareTest::$CalledCount);
    }

    public function testMethodNotFoundThrowException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('callable_service:notFound is not resolvable');

        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('callable_service:notFound');
    }

    public function testRouteMethodNotFoundThrowException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('callable_service:notFound is not resolvable');

        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveRoute('callable_service:notFound');
    }

    public function testMiddlewareMethodNotFoundThrowException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('callable_service:notFound is not resolvable');

        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn(new CallableTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware('callable_service:notFound');
    }

    public function testFunctionNotFoundThrowException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable notFound does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('notFound');
    }

    public function testRouteFunctionNotFoundThrowException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable notFound does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveRoute('notFound');
    }

    public function testMiddlewareFunctionNotFoundThrowException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable notFound does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware('notFound');
    }

    public function testClassNotFoundThrowException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('Unknown:notFound');
    }

    public function testRouteClassNotFoundThrowException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveRoute('Unknown:notFound');
    }

    public function testMiddlewareClassNotFoundThrowException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware('Unknown:notFound');
    }

    public function testCallableClassNotFoundThrowException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve(['Unknown', 'notFound']);
    }

    public function testRouteCallableClassNotFoundThrowException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveRoute(['Unknown', 'notFound']);
    }

    public function testMiddlewareCallableClassNotFoundThrowException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware(['Unknown', 'notFound']);
    }
}
