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
    private ObjectProphecy $containerProphecy;

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

    public function testClosure(): void
    {
        $test = function () {
            return true;
        };
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve($test);
        $callableRoute = $resolver->resolveRoute($test);
        $callableMiddleware = $resolver->resolveMiddleware($test);

        $this->assertTrue($callable());
        $this->assertTrue($callableRoute());
        $this->assertTrue($callableMiddleware());
    }

    public function testClosureContainer(): void
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

        $this->assertSame(42, $callable());
        $this->assertSame(42, $callableRoute());
        $this->assertSame(42, $callableMiddleware());
    }

    public function testFunctionName(): void
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(__NAMESPACE__ . '\testAdvancedCallable');
        $callableRoute = $resolver->resolveRoute(__NAMESPACE__ . '\testAdvancedCallable');
        $callableMiddleware = $resolver->resolveMiddleware(__NAMESPACE__ . '\testAdvancedCallable');

        $this->assertTrue($callable());
        $this->assertTrue($callableRoute());
        $this->assertTrue($callableMiddleware());
    }

    public function testObjMethodArray(): void
    {
        $obj = new CallableTest();
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve([$obj, 'toCall']);
        $callableRoute = $resolver->resolveRoute([$obj, 'toCall']);
        $callableMiddleware = $resolver->resolveMiddleware([$obj, 'toCall']);

        $callable();
        $this->assertSame(1, CallableTest::$CalledCount);

        $callableRoute();
        $this->assertSame(2, CallableTest::$CalledCount);

        $callableMiddleware();
        $this->assertSame(3, CallableTest::$CalledCount);
    }

    public function testSlimCallable(): void
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve('Slim\Tests\Mocks\CallableTest:toCall');
        $callableRoute = $resolver->resolveRoute('Slim\Tests\Mocks\CallableTest:toCall');
        $callableMiddleware = $resolver->resolveMiddleware('Slim\Tests\Mocks\CallableTest:toCall');

        $callable();
        $this->assertSame(1, CallableTest::$CalledCount);

        $callableRoute();
        $this->assertSame(2, CallableTest::$CalledCount);

        $callableMiddleware();
        $this->assertSame(3, CallableTest::$CalledCount);
    }

    public function testSlimCallableAsArray(): void
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve([CallableTest::class, 'toCall']);
        $callableRoute = $resolver->resolveRoute([CallableTest::class, 'toCall']);
        $callableMiddleware = $resolver->resolveMiddleware([CallableTest::class, 'toCall']);

        $callable();
        $this->assertSame(1, CallableTest::$CalledCount);

        $callableRoute();
        $this->assertSame(2, CallableTest::$CalledCount);

        $callableMiddleware();
        $this->assertSame(3, CallableTest::$CalledCount);
    }

    public function testSlimCallableContainer(): void
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('Slim\Tests\Mocks\CallableTest:toCall');
        $this->assertSame($container, CallableTest::$CalledContainer);

        CallableTest::$CalledContainer = null;
        $resolver->resolveRoute('Slim\Tests\Mocks\CallableTest:toCall');
        $this->assertSame($container, CallableTest::$CalledContainer);

        CallableTest::$CalledContainer = null;
        $resolver->resolveMiddleware('Slim\Tests\Mocks\CallableTest:toCall');
        $this->assertSame($container, CallableTest::$CalledContainer);
    }

    public function testSlimCallableAsArrayContainer(): void
    {
        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve([CallableTest::class, 'toCall']);
        $this->assertSame($container, CallableTest::$CalledContainer);

        CallableTest::$CalledContainer = null;
        $resolver->resolveRoute([CallableTest::class, 'toCall']);
        $this->assertSame($container, CallableTest::$CalledContainer);

        CallableTest::$CalledContainer = null;
        $resolver->resolveMiddleware([CallableTest::class ,'toCall']);
        $this->assertSame($container, CallableTest::$CalledContainer);
    }

    public function testContainer(): void
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
        $this->assertSame(1, CallableTest::$CalledCount);

        $callableRoute();
        $this->assertSame(2, CallableTest::$CalledCount);

        $callableMiddleware();
        $this->assertSame(3, CallableTest::$CalledCount);
    }

    public function testResolutionToAnInvokableClassInContainer(): void
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
        $this->assertSame(1, InvokableTest::$CalledCount);

        $callableRoute();
        $this->assertSame(2, InvokableTest::$CalledCount);

        $callableMiddleware();
        $this->assertSame(3, InvokableTest::$CalledCount);
    }

    public function testResolutionToAnInvokableClass(): void
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve('Slim\Tests\Mocks\InvokableTest');
        $callableRoute = $resolver->resolveRoute('Slim\Tests\Mocks\InvokableTest');
        $callableMiddleware = $resolver->resolveMiddleware('Slim\Tests\Mocks\InvokableTest');

        $callable();
        $this->assertSame(1, InvokableTest::$CalledCount);

        $callableRoute();
        $this->assertSame(2, InvokableTest::$CalledCount);

        $callableMiddleware();
        $this->assertSame(3, InvokableTest::$CalledCount);
    }

    public function testResolutionToAPsrRequestHandlerClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Slim\\Tests\\Mocks\\RequestHandlerTest is not resolvable');

        $resolver = new CallableResolver(); // No container injected
        $resolver->resolve(RequestHandlerTest::class);
    }

    public function testRouteResolutionToAPsrRequestHandlerClass(): void
    {
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callableRoute = $resolver->resolveRoute(RequestHandlerTest::class);
        $callableRoute($request);
        $this->assertSame(1, RequestHandlerTest::$CalledCount);
    }

    public function testMiddlewareResolutionToAPsrRequestHandlerClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Slim\\Tests\\Mocks\\RequestHandlerTest is not resolvable');

        $resolver = new CallableResolver(); // No container injected
        $resolver->resolveMiddleware(RequestHandlerTest::class);
    }

    public function testObjPsrRequestHandlerClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('{} is not resolvable');

        $obj = new RequestHandlerTest();
        $resolver = new CallableResolver(); // No container injected
        $resolver->resolve($obj);
    }

    public function testRouteObjPsrRequestHandlerClass(): void
    {
        $obj = new RequestHandlerTest();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callableRoute = $resolver->resolveRoute($obj);
        $callableRoute($request);
        $this->assertSame(1, RequestHandlerTest::$CalledCount);
    }

    public function testMiddlewareObjPsrRequestHandlerClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('{} is not resolvable');

        $obj = new RequestHandlerTest();
        $resolver = new CallableResolver(); // No container injected
        $resolver->resolveMiddleware($obj);
    }

    public function testObjPsrRequestHandlerClassInContainer(): void
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

    public function testRouteObjPsrRequestHandlerClassInContainer(): void
    {
        $this->containerProphecy->has('a_requesthandler')->willReturn(true);
        $this->containerProphecy->get('a_requesthandler')->willReturn(new RequestHandlerTest());

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver($container);
        $callable = $resolver->resolveRoute('a_requesthandler');
        $callable($request);

        $this->assertSame(1, RequestHandlerTest::$CalledCount);
    }

    public function testMiddlewareObjPsrRequestHandlerClassInContainer(): void
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

    public function testResolutionToAPsrRequestHandlerClassWithCustomMethod(): void
    {
        $resolver = new CallableResolver(); // No container injected
        $callable = $resolver->resolve(RequestHandlerTest::class . ':custom');
        $callableRoute = $resolver->resolveRoute(RequestHandlerTest::class . ':custom');
        $callableMiddleware = $resolver->resolveMiddleware(RequestHandlerTest::class . ':custom');

        $this->assertIsArray($callable);
        $this->assertInstanceOf(RequestHandlerTest::class, $callable[0]);
        $this->assertSame('custom', $callable[1]);

        $this->assertIsArray($callableRoute);
        $this->assertInstanceOf(RequestHandlerTest::class, $callableRoute[0]);
        $this->assertSame('custom', $callableRoute[1]);

        $this->assertIsArray($callableMiddleware);
        $this->assertInstanceOf(RequestHandlerTest::class, $callableMiddleware[0]);
        $this->assertSame('custom', $callableMiddleware[1]);
    }

    public function testObjMiddlewareClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('{} is not resolvable');

        $obj = new MiddlewareTest();
        $resolver = new CallableResolver(); // No container injected
        $resolver->resolve($obj);
    }

    public function testRouteObjMiddlewareClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('{} is not resolvable');

        $obj = new MiddlewareTest();
        $resolver = new CallableResolver(); // No container injected
        $resolver->resolveRoute($obj);
    }

    public function testMiddlewareObjMiddlewareClass(): void
    {
        $obj = new MiddlewareTest();
        $request = $this->createServerRequest('/', 'GET');
        $resolver = new CallableResolver(); // No container injected
        $callableRouteMiddleware = $resolver->resolveMiddleware($obj);
        $callableRouteMiddleware($request, $this->createMock(RequestHandlerInterface::class));
        $this->assertSame(1, MiddlewareTest::$CalledCount);
    }

    public function testNotObjectInContainerThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('callable_service container entry is not an object');

        $this->containerProphecy->has('callable_service')->willReturn(true);
        $this->containerProphecy->get('callable_service')->willReturn('NOT AN OBJECT');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('callable_service');
    }

    public function testMethodNotFoundThrowException(): void
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

    public function testRouteMethodNotFoundThrowException(): void
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

    public function testMiddlewareMethodNotFoundThrowException(): void
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

    public function testFunctionNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable notFound does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('notFound');
    }

    public function testRouteFunctionNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable notFound does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveRoute('notFound');
    }

    public function testMiddlewareFunctionNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable notFound does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware('notFound');
    }

    public function testClassNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve('Unknown:notFound');
    }

    public function testRouteClassNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveRoute('Unknown:notFound');
    }

    public function testMiddlewareClassNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware('Unknown:notFound');
    }

    public function testCallableClassNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolve(['Unknown', 'notFound']);
    }

    public function testRouteCallableClassNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveRoute(['Unknown', 'notFound']);
    }

    public function testMiddlewareCallableClassNotFoundThrowException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown::notFound() does not exist');

        /** @var ContainerInterface $container */
        $container = $this->containerProphecy->reveal();
        $resolver = new CallableResolver($container);
        $resolver->resolveMiddleware(['Unknown', 'notFound']);
    }
}
