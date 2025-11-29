<?php

/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/5.x/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Tests\Container;

use Closure;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Container\ContainerResolver;
use Slim\Factory\AppFactory;
use Slim\Interfaces\ContainerResolverInterface;
use Slim\Tests\Mocks\CallableTester;
use Slim\Tests\Mocks\InvokableTester;
use Slim\Tests\Mocks\MiddlewareTester;
use Slim\Tests\Mocks\RequestHandlerTester;
use Slim\Tests\Traits\AppTestTrait;
use TypeError;

final class ContainerResolverTest extends TestCase
{
    use AppTestTrait;

    public function testClosure(): void
    {
        $test = function () {
            return true;
        };

        $app = AppFactory::create();
        $resolver = $app->getContainer()->get(ContainerResolver::class);

        $callable = $resolver->resolveCallable($test);

        $this->assertTrue($callable());
    }

    public function testClosureContainer(): void
    {
        $definitions
            = [
                'ultimateAnswer' => fn() => 42,
            ];
        $app = $this->createApp($definitions);
        $container = $app->getContainer();

        $that = $this;
        $test = function () use ($that, $container) {
            $that->assertInstanceOf(ContainerInterface::class, $this);
            $that->assertSame($container, $this);

            /** @var ContainerInterface $this */
            return $this->get('ultimateAnswer');
        };

        $resolver = $container->get(ContainerResolverInterface::class);
        $callable = $resolver->resolveRoute($test);

        $this->assertSame(42, $callable());
    }

    public function testClosureFromCallable(): void
    {
        $app = AppFactory::create();
        $container = $app->getContainer();

        $that = $this;
        $class = Closure::fromCallable(
            function () use ($that, $container) {
                $that->assertSame($container, $this);

                return 42;
            },
        );

        $test = [$class, '__invoke'];

        $resolver = $container->get(ContainerResolverInterface::class);
        $callable = $resolver->resolveRoute($test);

        $this->assertSame(42, $callable());
    }

    public function testFunctionName(): void
    {
        $app = AppFactory::create();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $callable = $resolver->resolveCallable(__NAMESPACE__ . '\testAdvancedCallable');

        $this->assertTrue($callable());
    }

    public function testObjMethodArray(): void
    {
        $obj = new CallableTester();
        $app = AppFactory::create();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $callable = $resolver->resolveCallable([$obj, 'toCall']);
        $this->assertSame(true, $callable());
    }

    public function testSlimCallable(): void
    {
        $app = AppFactory::create();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $callable = $resolver->resolveCallable('Slim\Tests\Mocks\CallableTester:toCall');
        $this->assertSame(true, $callable());
    }

    public function testSlimCallableAsArray(): void
    {
        $app = AppFactory::create();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $callable = $resolver->resolveCallable([CallableTester::class, 'toCall']);

        $this->assertSame(true, $callable());
    }

    public function testContainer(): void
    {
        $definitions
            = [
                'callable_service' => fn() => new CallableTester(),
            ];
        $app = $this->createApp($definitions);
        $resolver = $app->getContainer()->get(ContainerResolver::class);

        $callable = $resolver->resolveCallable('callable_service:toCall');
        $this->assertSame(true, $callable());
    }

    public function testResolutionToAnInvokableClassInContainer(): void
    {
        $definitions
            = [
                'an_invokable' => fn() => new InvokableTester(),
            ];
        $app = $this->createApp($definitions);

        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $callable = $resolver->resolveCallable('an_invokable');

        $this->assertSame(true, $callable());
    }

    public function testResolutionToAnInvokableClass(): void
    {
        $app = AppFactory::create();
        $resolver = $app->getContainer()->get(ContainerResolverInterface::class);
        $callable = $resolver->resolveCallable(InvokableTester::class);
        $this->assertSame(true, $callable());
    }

    public function testResolutionToRequestHandler(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The definition "Slim\Tests\Mocks\RequestHandlerTester" is not a callable');

        $app = AppFactory::create();
        $resolver = $app->getContainer()->get(ContainerResolverInterface::class);

        $resolver->resolveCallable(RequestHandlerTester::class);
    }

    public function testObjRequestHandlerInContainer(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The definition "a_requesthandler" is not a callable');

        $definitions
            = [
                'a_requesthandler' => function ($container) {
                    return new RequestHandlerTester($container->get(ResponseFactoryInterface::class));
                },
            ];
        $app = $this->createApp($definitions);
        $resolver = $app->getContainer()->get(ContainerResolverInterface::class);

        $resolver->resolveCallable('a_requesthandler');
    }

    public function testResolutionToAPsrRequestHandlerClassWithCustomMethod(): void
    {
        $app = AppFactory::create();
        $resolver = $app->getContainer()->get(ContainerResolverInterface::class);
        $callable = $resolver->resolveCallable(RequestHandlerTester::class . ':custom');

        $this->assertIsArray($callable);
        $this->assertInstanceOf(RequestHandlerTester::class, $callable[0]);
        $this->assertSame('custom', $callable[1]);
    }

    public function testObjMiddlewareClass(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('must be of type callable|array|string');

        $obj = new MiddlewareTester();
        $app = AppFactory::create();
        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $resolver->resolveCallable($obj);
    }

    public function testNotObjectInContainerThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The definition "callable_service" is not a callable');

        $definitions
            = [
                'callable_service' => fn() => 'NOT AN OBJECT',
            ];
        $app = $this->createApp($definitions);

        $resolver = $app->getContainer()->get(ContainerResolverInterface::class);
        $resolver->resolveCallable('callable_service');
    }

    public function testMethodNotFoundThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The method "notFound" does not exists');

        $definitions
            = [
                'callable_service' => fn() => new CallableTester(),
            ];
        $app = $this->createApp($definitions);

        $resolver = $app->getContainer()->get(ContainerResolver::class);
        $resolver->resolveCallable('callable_service:notFound');
    }

    public function testFunctionNotFoundThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No entry or class found for 'notFound'");

        $app = AppFactory::create();
        $resolver = $app->getContainer()->get(ContainerResolverInterface::class);
        $resolver->resolveCallable('notFound');
    }

    public function testClassNotFoundThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No entry or class found for 'Unknown'");

        $app = AppFactory::create();
        $resolver = $app->getContainer()->get(ContainerResolverInterface::class);
        $resolver->resolveCallable('Unknown:notFound');
    }

    public function testCallableClassNotFoundThrowException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No entry or class found for 'Unknown'");

        $app = AppFactory::create();
        $resolver = $app->getContainer()->get(ContainerResolverInterface::class);
        $resolver->resolveCallable(['Unknown', 'notFound']);
    }

    public function testResolveStackWithFifoOrder()
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $resolver = $container->get(ContainerResolverInterface::class);

        $middleware1 = $this->createCallableMiddleware();
        $middleware2 = $this->resolveMiddleware();

        $queue = [$middleware1, $middleware2];

        $resolved1 = $resolver->resolveMiddleware($middleware1);
        $this->assertInstanceOf(MiddlewareInterface::class, $resolved1);

        $resolved2 = $resolver->resolveMiddleware($middleware2);
        $this->assertInstanceOf(MiddlewareInterface::class, $resolved2);

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $response = $resolved1->process($request, $handler);
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $response = $resolved2->process($request, $handler);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testResolveMiddlewareWithValidMiddleware()
    {
        $app = AppFactory::create();
        $container = $app->getContainer();
        $resolver = $container->get(ContainerResolverInterface::class);

        $middleware = $this->resolveMiddleware();

        $resolvedMiddleware = $resolver->resolveMiddleware($middleware);

        $this->assertInstanceOf(MiddlewareInterface::class, $resolvedMiddleware);
    }

    public function testResolveStackWithException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'A middleware must be an object or callable that implements "MiddlewareInterface".',
        );

        $app = AppFactory::create();
        $container = $app->getContainer();
        $resolver = $container->get(ContainerResolverInterface::class);

        $resolver->resolveMiddleware([[null]]);
    }

    private function createCallableMiddleware(): callable
    {
        $response = $this->createMock(ResponseInterface::class);

        return function () use ($response): ResponseInterface {
            return $response;
        };
    }

    private function resolveMiddleware(): MiddlewareInterface
    {
        $response = $this->createMock(ResponseInterface::class);

        return new class ($response) implements MiddlewareInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return $this->response;
            }
        };
    }
}

function testAdvancedCallable()
{
    return true;
}
